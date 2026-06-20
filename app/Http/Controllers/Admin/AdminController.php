<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    // ─────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────

    private function authHeaders(): array
    {
        return ['Authorization' => 'Bearer ' . session('jwt_token')];
    }

    private function apiUrl(string $path): string
    {
        // ✅ Use the old key — this is what Auth Service actually returns
        return config('services.auth_service.url') . '/api/admin/' . $path;
    }

    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    // ─────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────
    public function dashboard(): Response
    {
        // ✅ Use old JSON keys ('faculties', 'departments', 'programs')
        // because that's what the Auth Service actually returns
        try {
            $faculties   = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('faculties'))
                ->json('faculties', []);

            $departments = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('departments'))
                ->json('departments', []);

            $programs    = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('programs'))
                ->json('programs', []);

            $feedbackResp = Http::timeout(5)
                ->get($this->feedbackApiUrl('rector/feedbacks'));

            $feedbackData = $feedbackResp->successful()
                ? $feedbackResp->json('feedbacks', [])
                : [];

        } catch (\Exception $e) {
            $faculties    = [];
            $departments  = [];
            $programs     = [];
            $feedbackData = [];
        }

        $feedbackStats = [
            'total'        => count($feedbackData),
            'submitted'    => count(array_filter($feedbackData, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbackData, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbackData, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbackData, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbackData, fn($f) => $f['priority'] === 'urgent')),
        ];

        // Recent 5 feedbacks routed to admin
        $adminFeedbacks = array_values(
            array_slice(
                array_filter($feedbackData, fn($f) => $f['routed_to'] === 'admin'),
                0, 5
            )
        );

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'faculties'   => count($faculties),
                'departments' => count($departments),
                'programs'    => count($programs),
                'feedback'    => $feedbackStats,
            ],
            'adminFeedbacks' => $adminFeedbacks,
            'user'           => session('user'),
        ]);
    }

    // ─────────────────────────────────────────────
    // MANAGE DATA — faculties, departments, programs
    // ─────────────────────────────────────────────
    public function ManageData(): Response
    {
        // ✅ Use old JSON keys that actually work
        try {
            $faculties   = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('faculties'))
                ->json('faculties', []);

            $departments = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('departments'))
                ->json('departments', []);

            $programs    = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('programs'))
                ->json('programs', []);

            $categories = Http::timeout(5)
                ->get($this->feedbackApiUrl('categories'), ['include_inactive' => true, 'role' => 'all'])
                ->json('categories', []);

        } catch (\Exception $e) {
            $faculties   = [];
            $departments = [];
            $programs    = [];
            $categories  = [];
        }

        return Inertia::render('Admin/ManageData', [
            'faculties'   => $faculties,
            'departments' => $departments,
            'programs'    => $programs,
            'categories'  => $categories,
            'user'        => session('user'),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'routes_to' => ['required', 'in:hod,dean,rector,admin'],
            'sender_role' => ['required', 'in:student,lecturer,any'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $response = Http::timeout(10)->post($this->feedbackApiUrl('categories'), $request->only([
                'name', 'routes_to', 'sender_role', 'description',
            ]));
        } catch (\Exception $e) {
            return back()->withErrors(['category' => 'Feedback service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', ['category' => 'Failed to create category.']));
        }

        return redirect()->route('admin.ManageData', ['tab' => 'categories'])
            ->with('success', 'Feedback category created successfully.');
    }

    public function updateCategory(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'routes_to' => ['required', 'in:hod,dean,rector,admin'],
            'sender_role' => ['required', 'in:student,lecturer,any'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        try {
            $response = Http::timeout(10)->put($this->feedbackApiUrl("categories/{$id}"), $request->only([
                'name', 'routes_to', 'sender_role', 'description', 'is_active',
            ]));
        } catch (\Exception $e) {
            return back()->withErrors(['category' => 'Feedback service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', ['category' => 'Failed to update category.']));
        }

        return redirect()->route('admin.ManageData', ['tab' => 'categories'])
            ->with('success', 'Feedback category updated successfully.');
    }

    public function deleteCategory(int $id): RedirectResponse
    {
        try {
            $response = Http::timeout(10)->delete($this->feedbackApiUrl("categories/{$id}"));
        } catch (\Exception $e) {
            return back()->withErrors(['category' => 'Feedback service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors(['category' => $response->json('message', 'Failed to delete category.')]);
        }

        return redirect()->route('admin.ManageData', ['tab' => 'categories'])
            ->with('success', $response->json('message', 'Feedback category removed.'));
    }

    // ─────────────────────────────────────────────
    // STORE FACULTY
    // ─────────────────────────────────────────────
    public function storeFaculty(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:20'],
        ]);

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->post($this->apiUrl('faculties'), [
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Auth service unavailable.'])->withInput();
        }

        if (!$response->successful()) {
            return back()
                ->withErrors($response->json('errors', ['name' => $response->json('message', 'Failed to create faculty.')]))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Faculty created successfully.');
    }

    // ─────────────────────────────────────────────
    // STORE DEPARTMENT
    // ─────────────────────────────────────────────
    public function storeDepartment(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'code'       => ['required', 'string', 'max:20'],
            'faculty_id' => ['required', 'integer'],
        ]);

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->post($this->apiUrl('departments'), [
                    'name'       => $request->name,
                    'code'       => strtoupper($request->code),
                    'faculty_id' => $request->faculty_id,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Auth service unavailable.'])->withInput();
        }

        if (!$response->successful()) {
            return back()
                ->withErrors($response->json('errors', ['name' => $response->json('message', 'Failed to create department.')]))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Department created successfully.');
    }

    // ─────────────────────────────────────────────
    // STORE PROGRAM
    // ─────────────────────────────────────────────
    public function storeProgram(Request $request): RedirectResponse
    {
        $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'code'           => ['required', 'string', 'max:20'],
            'department_id'  => ['required', 'integer'],
            // 'level'          => ['required', 'in:certificate,diploma,degree,masters,phd'],
            'level' => ['required', 'in:basic_certificate,certificate,diploma,higher_diploma,postgraduate_diploma,bachelors,masters,phd'],
            'duration_years' => ['required', 'numeric', 'min:1', 'max:7'],
        ]);

        $durationYears   = $request->duration_years;
        $durationDisplay = $durationYears . ' year' . ($durationYears > 1 ? 's' : '');

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->post($this->apiUrl('programs'), [
                    'name'             => $request->name,
                    'code'             => strtoupper($request->code),
                    'department_id'    => (int) $request->department_id,
                    'level'            => strtolower($request->level), // always lowercase
                    'duration_years'   => (float) $durationYears,
                    'duration_display' => $durationDisplay,            //  always send this
                    'is_active'        => true,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Auth service unavailable: ' . $e->getMessage()])->withInput();
        }

        $data = $response->json();

        if (!$response->successful()) {
            //  Show the actual validation errors from Auth Service
            $errors = $data['errors'] ?? [];
            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }
            return back()->withErrors([
                'name' => $data['message'] ?? 'Failed to create program. Status: ' . $response->status(),
            ])->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Program "' . $request->name . '" created successfully.');
    }
    // ─────────────────────────────────────────────
    // FEEDBACKS — routed to admin only
    // ─────────────────────────────────────────────
    public function feedbacks(): Response
    {
        try {
            $response  = Http::timeout(10)
                ->get($this->feedbackApiUrl('rector/feedbacks'));

            $all       = $response->successful() ? $response->json('feedbacks', []) : [];
            $feedbacks = array_values(
                array_filter($all, fn($f) => $f['routed_to'] === 'admin')
            );
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        $stats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
        ];

        return Inertia::render('Admin/Feedbacks', [
            'feedbacks' => $feedbacks,
            'stats'     => $stats,
            'user'      => session('user'),
        ]);
    }

    // ─────────────────────────────────────────────
    // SHOW SINGLE FEEDBACK
    // ─────────────────────────────────────────────
    public function showFeedback(int $id): Response
    {
        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl("rector/feedbacks/{$id}"));

            $feedback = $response->successful()
                ? $response->json('feedback')
                : null;
        } catch (\Exception $e) {
            $feedback = null;
        }

        $suggestions = [];
        if ($feedback && !empty($feedback['content'])) {
            try {
                $resp = Http::timeout(10)->post($this->feedbackApiUrl('feedback/suggestions'), [
                    'content' => $feedback['content'],
                    'category_id' => $feedback['category_id'] ?? null,
                    'limit' => 3,
                ]);
                if ($resp->successful()) {
                    $suggestions = $resp->json('suggestions', []);
                }
            } catch (\Exception $e) {
                $suggestions = [];
            }
        }

        return Inertia::render('Admin/FeedbackDetail', [
            'feedback' => $feedback,
            'suggestions' => $suggestions,
            'user'     => session('user'),
        ]);
    }

    // ─────────────────────────────────────────────
    // RESPOND TO FEEDBACK
    // ─────────────────────────────────────────────
    public function respondFeedback(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'response' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("rector/feedbacks/{$id}/respond"), [
                    'response'       => $request->response,
                    'responder_role' => 'admin',
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['response' => 'Service unavailable.']);
        }

        return back()->with('success', 'Response submitted successfully.');
    }

    // ─────────────────────────────────────────────
    // RESOLVE FEEDBACK
    // ─────────────────────────────────────────────
    public function resolveFeedback(int $id): RedirectResponse
    {
        $resolution = request('resolution');
        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("rector/feedbacks/{$id}/resolve"), [
                    'responder_role' => 'admin',
                    'resolution' => $resolution,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback resolved successfully.');
    }


    // ── Evaluation Windows ─────────────────────────────────────────
    public function evaluationWindows(): Response
    {
        try {
            $response = Http::timeout(5)
                ->get($this->feedbackApiUrl('evaluation-windows'));
            $windows = $response->successful() ? $response->json('windows', []) : [];
        } catch (\Exception $e) {
            $windows = [];
        }

        return Inertia::render('Admin/EvaluationWindows', [
            'windows' => $windows,
            'user'    => session('user'),
        ]);
    }

    public function storeEvaluationWindow(Request $request): RedirectResponse
    {
        $request->validate([
            'title'         => ['required', 'string'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester'      => ['required', 'integer', 'in:1,2'],
            'opens_at'      => ['required', 'date'],
            'closes_at'     => ['required', 'date', 'after:opens_at'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('evaluation-windows'), [
                    'title'         => $request->title,
                    'academic_year' => $request->academic_year,
                    'semester'      => (int) $request->semester,
                    'opens_at'      => $request->opens_at,
                    'closes_at'     => $request->closes_at,
                    'is_active'     => true,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['title' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors($response->json('errors', [
                'title' => $response->json('message', 'Failed to create window.')
            ]));
        }

        return back()->with('success', 'Evaluation window created successfully.');
    }

    public function toggleEvaluationWindow(int $id): RedirectResponse
    {
        try {
            $response = Http::timeout(10)->post($this->feedbackApiUrl("evaluation-windows/{$id}/toggle"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'error' => $response->json('message', 'Window status could not be updated.'),
            ]);
        }

        return back()->with('success', $response->json('message', 'Window status updated.'));
    }

    public function deleteEvaluationWindow(int $id): RedirectResponse
    {
        try {
            $response = Http::timeout(10)
                ->delete($this->feedbackApiUrl("evaluation-windows/{$id}"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors(['error' => $response->json('message', 'Cannot delete this window.')]);
        }

        return back()->with('success', 'Window deleted.');
    }

    // ── Get departments with HOD info ──────────────────────────────
    public function getDepartmentsWithHod(): \Illuminate\Http\JsonResponse
    {
        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(5)
                ->get($this->apiUrl('departments'));

            $departments = $response->successful()
                ? $response->json('departments', [])
                : [];
        } catch (\Exception $e) {
            $departments = [];
        }

        return response()->json(['departments' => $departments]);
    }

    // ── Create HOD for department ──────────────────────────────────
    public function storeHod(Request $request, int $departmentId): RedirectResponse
    {
        $request->validate([
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email'],
            'phone'          => ['nullable', 'string'],
            'staff_number'   => ['nullable', 'string'],
            'title'          => ['nullable', 'string'],
            'gender'         => ['nullable', 'in:Male,Female,Other'],
            'specialization' => ['nullable', 'string'],
            'action'         => ['required', 'in:assign,replace'],
        ]);

        $endpoint = $request->action === 'replace'
            ? "departments/{$departmentId}/replace-hod"
            : "departments/{$departmentId}/assign-hod";

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(15)
                ->post($this->apiUrl($endpoint), [
                    'first_name'     => $request->first_name,
                    'last_name'      => $request->last_name,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'staff_number'   => $request->staff_number,
                    'title'          => $request->title,
                    'gender'         => $request->gender,
                    'specialization' => $request->specialization,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'first_name' => 'Auth service unavailable: ' . $e->getMessage(),
            ])->withInput();
        }

        $data = $response->json();

        Log::info('HOD creation response', [
            'status' => $response->status(),
            'body'   => $data,
        ]);

        if (!$response->successful()) {
            // ✅ Show the actual errors from Auth Service
            $errors = $data['errors'] ?? [];

            if (!empty($errors)) {
                // Map auth service errors to form fields
                $formErrors = [];
                foreach ($errors as $field => $messages) {
                    $formErrors[$field] = is_array($messages) ? $messages[0] : $messages;
                }
                return back()->withErrors($formErrors)->withInput();
            }

            return back()->withErrors([
                'first_name' => $data['message'] ?? 'Failed to create HOD. HTTP: ' . $response->status(),
            ])->withInput();
        }

        $hodName  = $data['hod']['name'] ?? 'HOD';
        $tempPass = $data['hod']['temp_password'] ?? $request->last_name;

        return redirect()->route('admin.ManageData')
            ->with('success', "HOD '{$hodName}' created. Login: {$request->email} / Password: {$tempPass}");
    }

    // ── Create/Replace Dean for faculty ───────────────────────────
    public function storeDean(Request $request, int $facultyId): RedirectResponse
    {
        $request->validate([
            'first_name'     => ['required', 'string', 'max:100'],
            'last_name'      => ['required', 'string', 'max:100'],
            'email'          => ['required', 'email'],
            'phone'          => ['nullable', 'string'],
            'staff_number'   => ['nullable', 'string'],
            'title'          => ['nullable', 'string'],
            'gender'         => ['nullable', 'in:Male,Female,Other'],
            'specialization' => ['nullable', 'string'],
            'action'         => ['required', 'in:assign,replace'],
        ]);

        $endpoint = $request->action === 'replace'
            ? "faculties/{$facultyId}/replace-dean"
            : "faculties/{$facultyId}/assign-dean";

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(15)
                ->post($this->apiUrl($endpoint), [
                    'first_name'     => $request->first_name,
                    'last_name'      => $request->last_name,
                    'email'          => $request->email,
                    'phone'          => $request->phone,
                    'staff_number'   => $request->staff_number,
                    'title'          => $request->title,
                    'gender'         => $request->gender,
                    'specialization' => $request->specialization,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'first_name' => 'Auth service unavailable: ' . $e->getMessage(),
            ])->withInput();
        }

        $data = $response->json();

        if (!$response->successful()) {
            $errors = $data['errors'] ?? [];
            if (!empty($errors)) {
                $formErrors = [];
                foreach ($errors as $field => $messages) {
                    $formErrors[$field] = is_array($messages) ? $messages[0] : $messages;
                }
                return back()->withErrors($formErrors)->withInput();
            }

            return back()->withErrors([
                'first_name' => $data['message'] ?? 'Failed to create Dean. HTTP: ' . $response->status(),
            ])->withInput();
        }

        $deanName = $data['dean']['name'] ?? 'Dean';
        $tempPass = $data['dean']['temp_password'] ?? $request->last_name;

        return redirect()->route('admin.ManageData', ['tab' => 'deans'])
            ->with('success', "Dean '{$deanName}' created. Login: {$request->email} / Password: {$tempPass}");
    }

    public function analytics(): Response
{
    try {
        $windowsResp  = Http::timeout(5)->get($this->feedbackApiUrl('evaluation-windows'));
        $windows      = $windowsResp->successful() ? $windowsResp->json('windows', []) : [];
        $activeWindow = collect($windows)->firstWhere('is_open', true)
            ?? collect($windows)->firstWhere('is_active', true);

        $overview = $byFaculty = $trends = [];

        if ($activeWindow) {
            $overviewResp = Http::timeout(5)->get($this->feedbackApiUrl('analytics/overview'), [
                'window_id' => $activeWindow['id'],
            ]);
            $overview = $overviewResp->successful() ? $overviewResp->json('overview', []) : [];

            $facultyResp = Http::timeout(5)->get($this->feedbackApiUrl('analytics/by-faculty'), [
                'window_id' => $activeWindow['id'],
            ]);
            $byFaculty = $facultyResp->successful() ? $facultyResp->json('faculties', []) : [];

            $trendsResp = Http::timeout(5)->get($this->feedbackApiUrl('analytics/trends'), [
                'window_id' => $activeWindow['id'],
            ]);
            $trends = $trendsResp->successful() ? $trendsResp->json('trends', []) : [];
        }

        $feedbackResp = Http::timeout(5)->get($this->feedbackApiUrl('rector/feedbacks'));
        $feedbacks    = $feedbackResp->successful() ? $feedbackResp->json('feedbacks', []) : [];

        $faculties   = Http::withHeaders($this->authHeaders())->timeout(5)->get($this->apiUrl('faculties'))->json('faculties', []);
        $departments = Http::withHeaders($this->authHeaders())->timeout(5)->get($this->apiUrl('departments'))->json('departments', []);

    } catch (\Exception $e) {
        $windows = $overview = $byFaculty = $trends = $feedbacks = $faculties = $departments = [];
        $activeWindow = null;
    }

    $feedbackStats = [
        'total'        => count($feedbacks),
        'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
        'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
        'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
        'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
        'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
    ];

    return Inertia::render('Admin/Analytics', [
        'windows'       => $windows,
        'activeWindow'  => $activeWindow,
        'overview'      => $overview,
        'byFaculty'     => $byFaculty,
        'trends'        => $trends,
        'feedbackStats' => $feedbackStats,
        'faculties'     => $faculties,
        'departments'   => $departments,
        'user'          => session('user'),
    ]);
}
}
