<?php
namespace App\Http\Controllers\Rector;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class RectorController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    // ── Dashboard with full stats ──────────────────────────────
    public function dashboard(): Response
    {
        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl('rector/feedbacks'));

            $feedbacks = $response->successful()
                ? $response->json('feedbacks', [])
                : [];
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        $stats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
            'from_student' => count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'student')),
            'from_lecturer'=> count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'lecturer')),
        ];

        // Recent 5 feedbacks for dashboard preview
        $recent = array_slice(
            array_filter($feedbacks, fn($f) => in_array($f['status'], ['submitted', 'under_review', 'escalated'])),
            0, 5
        );

        return Inertia::render('Rector/Dashboard', [
            'stats'  => $stats,
            'recent' => array_values($recent),
            'user'   => session('user'),
        ]);
    }

    // ── All feedbacks campus-wide ──────────────────────────────
    public function feedbacks(Request $request): Response
    {
        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl('rector/feedbacks'));

            $feedbacks = $response->successful()
                ? $response->json('feedbacks', [])
                : [];
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        $stats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
            'from_student' => count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'student')),
            'from_lecturer'=> count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'lecturer')),
        ];

        return Inertia::render('Rector/Feedbacks', [
            'feedbacks' => $feedbacks,
            'stats'     => $stats,
            'user'      => session('user'),
        ]);
    }

    // ── View single feedback ───────────────────────────────────
    public function show(int $id): Response
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

        return Inertia::render('Rector/FeedbackDetail', [
            'feedback' => $feedback,
            'user'     => session('user'),
        ]);
    }

    // ── Respond ───────────────────────────────────────────────
    public function respond(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'response' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl("rector/feedbacks/{$id}/respond"), [
                    'response'       => $request->response,
                    'responder_role' => 'rector',
                    'department_id'  => null,
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

    // ── Resolve ───────────────────────────────────────────────
    public function resolve(int $id): RedirectResponse
    {
        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("rector/feedbacks/{$id}/resolve"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback marked as resolved.');
    }

    public function analytics(): Response
    {
        try {
            // Get all windows
            $windowsResp = Http::timeout(5)
                ->get(config('services.feedback_service.url') . '/api/evaluation-windows');
            $windows = $windowsResp->successful() ? $windowsResp->json('windows', []) : [];

            // Get active window
            $activeWindow = collect($windows)->firstWhere('is_open', true)
                ?? collect($windows)->firstWhere('is_active', true);

            $overview    = [];
            $byFaculty   = [];
            $trends      = [];

            if ($activeWindow) {
                $overviewResp = Http::timeout(5)
                    ->get(config('services.feedback_service.url') . '/api/analytics/overview', [
                        'window_id' => $activeWindow['id'],
                    ]);
                $overview = $overviewResp->successful() ? $overviewResp->json('overview', []) : [];

                $facultyResp = Http::timeout(5)
                    ->get(config('services.feedback_service.url') . '/api/analytics/by-faculty', [
                        'window_id' => $activeWindow['id'],
                    ]);
                $byFaculty = $facultyResp->successful() ? $facultyResp->json('faculties', []) : [];

                $trendsResp = Http::timeout(5)
                    ->get(config('services.feedback_service.url') . '/api/analytics/trends', [
                        'window_id' => $activeWindow['id'],
                    ]);
                $trends = $trendsResp->successful() ? $trendsResp->json('trends', []) : [];
            }

            // Feedback stats
            $feedbackResp = Http::timeout(5)
                ->get(config('services.feedback_service.url') . '/api/rector/feedbacks');
            $feedbacks = $feedbackResp->successful() ? $feedbackResp->json('feedbacks', []) : [];
        } catch (\Exception $e) {
            $windows = $overview = $byFaculty = $trends = $feedbacks = [];
            $activeWindow = null;
        }

        $feedbackStats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
            'from_student' => count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'student')),
            'from_lecturer' => count(array_filter($feedbacks, fn($f) => $f['sender_role'] === 'lecturer')),
        ];

        return Inertia::render('Rector/Analytics', [
            'windows'       => $windows,
            'activeWindow'  => $activeWindow,
            'overview'      => $overview,
            'byFaculty'     => $byFaculty,
            'trends'        => $trends,
            'feedbackStats' => $feedbackStats,
            'user'          => session('user'),
        ]);
    }
}