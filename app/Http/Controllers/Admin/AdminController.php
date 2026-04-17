<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

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

        } catch (\Exception $e) {
            $faculties   = [];
            $departments = [];
            $programs    = [];
        }

        return Inertia::render('Admin/ManageData', [
            'faculties'   => $faculties,
            'departments' => $departments,
            'programs'    => $programs,
            'user'        => session('user'),
        ]);
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
            'level'          => ['required', 'in:certificate,diploma,degree,masters,phd'],
            'duration_years' => ['required', 'numeric', 'min:1', 'max:7'],
        ]);

        try {
            $response = Http::withHeaders($this->authHeaders())
                ->timeout(10)
                ->post($this->apiUrl('programs'), [
                    'name'             => $request->name,
                    'code'             => strtoupper($request->code),
                    'department_id'    => $request->department_id,
                    'level'            => $request->level,
                    'duration_years'   => $request->duration_years,
                    'duration_display' => $request->duration_years . ' year' . ($request->duration_years > 1 ? 's' : ''),
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Auth service unavailable.'])->withInput();
        }

        if (!$response->successful()) {
            return back()
                ->withErrors($response->json('errors', ['name' => $response->json('message', 'Failed to create program.')]))
                ->withInput();
        }

        return redirect()->route('admin.ManageData')
            ->with('success', 'Program created successfully.');
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

        return Inertia::render('Admin/FeedbackDetail', [
            'feedback' => $feedback,
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
        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("rector/feedbacks/{$id}/resolve"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback resolved successfully.');
    }
}