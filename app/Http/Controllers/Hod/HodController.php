<?php
namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class HodController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    // ── Dashboard ──────────────────────────────────────────────
    public function dashboard(): Response
    {
        $departmentId = session('department_id');
        $user         = session('user');

        // Feedback stats
        try {
            $response = Http::timeout(5)
                ->get($this->feedbackApiUrl('hod/feedbacks'), [
                    'department_id' => $departmentId,
                ]);
            $feedbacks = $response->successful() ? $response->json('feedbacks', []) : [];
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        // Evaluation stats for this department
        $evalStats = ['total_courses' => 0, 'total_responses' => 0, 'avg_overall' => 0];
        try {
            $windowResp = Http::timeout(5)
                ->get($this->feedbackApiUrl('evaluation-windows/active'));
            $activeWindow = $windowResp->successful() ? $windowResp->json('window') : null;

            if ($activeWindow) {
                $evalResp = Http::timeout(5)
                    ->get($this->feedbackApiUrl('evaluations/department'), [
                        'department_id' => $departmentId,
                        'window_id'     => $activeWindow['id'],
                    ]);
                $analytics = $evalResp->successful() ? $evalResp->json('analytics', []) : [];

                $evalStats = [
                    'total_courses'   => count($analytics),
                    'total_responses' => array_sum(array_column($analytics, 'total_responses')),
                    'avg_overall'     => count($analytics)
                        ? round(array_sum(array_column($analytics, 'avg_overall')) / count($analytics), 2)
                        : 0,
                ];
            }
        } catch (\Exception $e) {
        }

        $stats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
        ];

        // Recent 5 feedbacks
        $recent = array_slice(
            array_filter($feedbacks, fn($f) => $f['status'] !== 'resolved'),
            0,
            5
        );

        return Inertia::render('Hod/Dashboard', [
            'stats'      => $stats,
            'evalStats'  => $evalStats,
            'recent'     => array_values($recent),
            'user'       => $user,
        ]);
    }
    // ── List all feedbacks for HOD's department ────────────────
    public function feedbacks(): Response
    {
        $departmentId = session('department_id');

        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl('hod/feedbacks'), [
                    'department_id' => $departmentId,
                ]);

            $feedbacks = $response->successful()
                ? $response->json('feedbacks', [])
                : [];
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        // Stats
        $stats = [
            'total'       => count($feedbacks),
            'submitted'   => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review'=> count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'   => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'      => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
        ];

        return Inertia::render('Hod/Feedbacks', [
            'feedbacks'     => $feedbacks,
            'stats'         => $stats,
            'department_id' => $departmentId,
            'user'          => session('user'),
        ]);
    }

    // ── View single feedback with decrypted content ────────────
    public function show(int $id): Response
    {
        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl("hod/feedbacks/{$id}"));

            $feedback = $response->successful()
                ? $response->json('feedback')
                : null;
        } catch (\Exception $e) {
            $feedback = null;
        }

        return Inertia::render('Hod/FeedbackDetail', [
            'feedback' => $feedback,
            'user'     => session('user'),
        ]);
    }

    // ── Respond to feedback ────────────────────────────────────
    public function respond(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'response' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl("hod/feedbacks/{$id}/respond"), [
                    'response'       => $request->response,
                    'responder_role' => 'hod',
                    'department_id'  => session('department_id'),
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['response' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'response' => $response->json('message', 'Failed to submit response.'),
            ]);
        }

        return back()->with('success', 'Response submitted successfully.');
    }

    // ── Escalate to Dean ───────────────────────────────────────
    public function escalate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl("hod/feedbacks/{$id}/escalate"), [
                    'escalate_to'    => 'dean',
                    'responder_role' => 'hod',
                    'note'           => $request->note ?? 'Escalated to Dean for further review.',
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['note' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'note' => $response->json('message', 'Failed to escalate.'),
            ]);
        }

        return back()->with('success', 'Feedback escalated to Dean successfully.');
    }

    // ── Resolve feedback ───────────────────────────────────────
    public function resolve(int $id): RedirectResponse
    {
        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("hod/feedbacks/{$id}/resolve"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback marked as resolved.');
    }

    public function evaluations(): Response
{
    $departmentId = session('department_id');
    $user         = session('user');

    try {
        $windowsResp = Http::timeout(5)->get($this->feedbackApiUrl('evaluation-windows'));
        $windows     = $windowsResp->successful() ? $windowsResp->json('windows', []) : [];
    } catch (\Exception $e) {
        $windows = [];
    }

    $results = [];
    $activeWindow = null;

    if (!empty($windows)) {
        try {
            $activeWindow = collect($windows)->firstWhere('is_open', true);
            if ($activeWindow) {
                $resp    = Http::timeout(5)->get($this->feedbackApiUrl('evaluations/department'), [
                    'department_id' => $departmentId,
                    'window_id'     => $activeWindow['id'],
                ]);
                $results = $resp->successful() ? $resp->json('analytics', []) : [];
            }
        } catch (\Exception $e) {}
    }

    return Inertia::render('Hod/Evaluations', [
        'windows'       => $windows,
        'results'       => $results,
        'activeWindow'  => $activeWindow,
        'department_id' => $departmentId,
        'user'          => $user,
    ]);
}

    
}