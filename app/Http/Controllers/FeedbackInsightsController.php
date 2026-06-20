<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedbackInsightsController extends Controller
{
    private function feedbackUrl(string $path): string
    {
        return config('services.feedback_service.url').'/api/'.$path;
    }

    private function authClient()
    {
        return Http::withToken((string) session('jwt_token'))->timeout(10);
    }

    public function hod(Request $request): Response
    {
        return $this->recurringIssues($request, 'hod', 'Hod/RecurringIssues', [
            'department_id' => (int) session('department_id'),
        ]);
    }

    public function dean(Request $request): Response
    {
        $facultyId = (int) (session('faculty_id') ?: (session('user')['faculty_id'] ?? 0));

        return $this->recurringIssues($request, 'dean', 'Dean/RecurringIssues', [
            'faculty_id' => $facultyId,
        ]);
    }

    public function rector(Request $request): Response
    {
        return $this->recurringIssues($request, 'rector', 'Rector/RecurringIssues');
    }

    public function rectorReport(Request $request): Response
    {
        [$faculties, $departments] = $this->organisationUnits();
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'faculty_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
        ]);

        $report = $this->fetchReport($filters);

        return Inertia::render('Rector/Reports', [
            'report' => $report,
            'faculties' => $faculties,
            'departments' => $departments,
            'filters' => $filters,
        ]);
    }

    public function exportRectorReport(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'faculty_id' => ['nullable', 'integer'],
            'department_id' => ['nullable', 'integer'],
        ]);
        [$faculties, $departments] = $this->organisationUnits();
        $facultyNames = collect($faculties)->pluck('name', 'id');
        $departmentNames = collect($departments)->pluck('name', 'id');
        $report = $this->fetchReport($filters);

        return response()->streamDownload(function () use ($report, $facultyNames, $departmentNames) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Anonymous Feedback Management Report']);
            fputcsv($handle, ['Generated', $report['generated_at'] ?? now()->toIso8601String()]);
            fputcsv($handle, []);
            fputcsv($handle, ['Faculty', 'Total', 'Open', 'Resolved', 'Urgent', 'Escalated', 'Resolution Rate', 'Average Resolution Hours']);
            foreach ($report['by_faculty'] ?? [] as $row) {
                fputcsv($handle, [
                    $facultyNames[$row['id']] ?? 'Unassigned',
                    $row['total'], $row['open'], $row['resolved'], $row['urgent'], $row['escalated'],
                    $row['resolution_rate'].'%',
                    $row['average_resolution_hours'] ?? 'N/A',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Department', 'Total', 'Open', 'Resolved', 'Urgent', 'Escalated', 'Resolution Rate', 'Average Resolution Hours']);
            foreach ($report['by_department'] ?? [] as $row) {
                fputcsv($handle, [
                    $departmentNames[$row['id']] ?? 'Unassigned',
                    $row['total'], $row['open'], $row['resolved'], $row['urgent'], $row['escalated'],
                    $row['resolution_rate'].'%',
                    $row['average_resolution_hours'] ?? 'N/A',
                ]);
            }
            fclose($handle);
        }, 'rector-feedback-report-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function recurringIssues(
        Request $request,
        string $role,
        string $page,
        array $scope = []
    ): Response {
        $filters = [
            'status' => $request->query('status', 'all'),
            'category_id' => $request->integer('category_id') ?: null,
            'filter_department_id' => $request->integer('department_id') ?: null,
            'minimum_group_size' => max(1, $request->integer('minimum_group_size', 2)),
        ];
        $groups = [];
        $summary = [
            'feedbacks_analysed' => 0,
            'recurring_groups' => 0,
            'grouped_feedbacks' => 0,
            'groups_with_solution' => 0,
            'priority_investigations' => 0,
            'departments_affected' => 0,
        ];
        $categories = [];

        try {
            $response = Http::timeout(15)->get($this->feedbackUrl('feedback/recurring-groups'), [
                'role' => $role,
                ...$scope,
                ...array_filter($filters, fn ($value) => $value !== null),
            ]);
            if ($response->successful()) {
                $groups = $response->json('groups', []);
                $summary = $response->json('summary', $summary);
            }

            $categoryResponse = Http::timeout(5)->get($this->feedbackUrl('categories'), ['role' => 'all']);
            $categories = $categoryResponse->successful()
                ? $categoryResponse->json('categories', [])
                : [];
        } catch (\Throwable) {
        }

        [, $departments] = $this->organisationUnits();
        if ($role === 'dean' && !empty($scope['faculty_id'])) {
            $departments = collect($departments)
                ->where('faculty_id', (int) $scope['faculty_id'])
                ->values()
                ->all();
        }

        return Inertia::render($page, [
            'groups' => $groups,
            'summary' => $summary,
            'categories' => $categories,
            'departments' => $departments,
            'filters' => [
                ...$filters,
                'department_id' => $filters['filter_department_id'],
            ],
            'role' => $role,
        ]);
    }

    private function organisationUnits(): array
    {
        try {
            $facultiesResponse = $this->authClient()->get(
                config('services.auth_service.url').'/api/admin/faculties'
            );
            $departmentsResponse = $this->authClient()->get(
                config('services.auth_service.url').'/api/admin/departments'
            );

            return [
                $facultiesResponse->successful() ? $facultiesResponse->json('faculties', []) : [],
                $departmentsResponse->successful() ? $departmentsResponse->json('departments', []) : [],
            ];
        } catch (\Throwable) {
            return [[], []];
        }
    }

    private function fetchReport(array $filters): array
    {
        try {
            $response = Http::timeout(15)->get(
                $this->feedbackUrl('rector/reports/feedback'),
                array_filter($filters, fn ($value) => $value !== null && $value !== '')
            );

            return $response->successful() ? $response->json() : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
