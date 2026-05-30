<?php
namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class DeanController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    // ✅ SINGLE source of truth for faculty_id
    private function getFacultyId(): ?int
    {
        // Try session first (set during login)
        $id = session('faculty_id');
        if ($id) return (int) $id;

        // Fallback: get from user payload
        $user = session('user');
        $id   = $user['faculty_id'] ?? null;
        if ($id) {
            session(['faculty_id' => (int) $id]);
            return (int) $id;
        }

        return null;
    }

    // ── Dashboard ──────────────────────────────────────────────
    public function dashboard(): Response
    {
        $facultyId = $this->getFacultyId();
        $user      = session('user');

        Log::info('Dean dashboard', [
            'faculty_id'         => $facultyId,
            'session_faculty_id' => session('faculty_id'),
            'user_faculty_id'    => session('user')['faculty_id'] ?? null,
            'user_role'          => session('user_role'),
            'user_email'         => session('user_email'),
        ]);


        try {
            $response  = Http::timeout(5)->get($this->feedbackApiUrl('dean/feedbacks'), [
                'faculty_id' => $facultyId,
            ]);
            $feedbacks = $response->successful() ? $response->json('feedbacks', []) : [];
        } catch (\Exception $e) {
            $feedbacks = [];
        }

        // Evaluation stats
        $evalStats = ['total_courses' => 0, 'total_responses' => 0, 'avg_overall' => 0, 'departments' => 0];
        try {
            $windowResp   = Http::timeout(5)->get($this->feedbackApiUrl('evaluation-windows/active'));
            $activeWindow = $windowResp->successful() ? $windowResp->json('window') : null;

            if ($activeWindow && $facultyId) {
                $evalResp  = Http::timeout(5)->get($this->feedbackApiUrl('evaluations/faculty'), [
                    'faculty_id' => $facultyId,
                    'window_id'  => $activeWindow['id'],
                ]);
                $analytics = $evalResp->successful() ? $evalResp->json('analytics', []) : [];

                $depts = array_unique(array_column($analytics, 'department_id'));
                $evalStats = [
                    'total_courses'   => count($analytics),
                    'total_responses' => array_sum(array_column($analytics, 'total_responses')),
                    'avg_overall'     => count($analytics)
                        ? round(array_sum(array_column($analytics, 'avg_overall')) / count($analytics), 2)
                        : 0,
                    'departments' => count($depts),
                ];
            }
        } catch (\Exception $e) {}

        $stats = [
            'total'        => count($feedbacks),
            'submitted'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'submitted')),
            'under_review' => count(array_filter($feedbacks, fn($f) => $f['status'] === 'under_review')),
            'escalated'    => count(array_filter($feedbacks, fn($f) => $f['status'] === 'escalated')),
            'resolved'     => count(array_filter($feedbacks, fn($f) => $f['status'] === 'resolved')),
            'urgent'       => count(array_filter($feedbacks, fn($f) => $f['priority'] === 'urgent')),
        ];

        $recent = array_values(array_slice(
            array_filter($feedbacks, fn($f) => $f['status'] !== 'resolved'),
            0, 5
        ));

        return Inertia::render('Dean/Dashboard', [
            'stats'      => $stats,
            'evalStats'  => $evalStats,
            'recent'     => $recent,
            'faculty_id' => $facultyId,
            'user'       => $user,
        ]);
    }

    // ── Feedbacks for THIS dean's faculty only ─────────────────
    public function feedbacks(): Response
    {
        $facultyId = $this->getFacultyId(); // ✅ always uses correct faculty_id

        try {
            $response  = Http::timeout(10)->get($this->feedbackApiUrl('dean/feedbacks'), [
                'faculty_id' => $facultyId,
            ]);
            $feedbacks = $response->successful() ? $response->json('feedbacks', []) : [];
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
        ];

        return Inertia::render('Dean/Feedbacks', [
            'feedbacks'  => $feedbacks,
            'stats'      => $stats,
            'faculty_id' => $facultyId,
            'user'       => session('user'),
        ]);
    }

    // ── Show single feedback ───────────────────────────────────
    public function show(int $id): Response
    {
        try {
            $response = Http::timeout(10)->get($this->feedbackApiUrl("dean/feedbacks/{$id}"));
            $feedback = $response->successful() ? $response->json('feedback') : null;
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

        return Inertia::render('Dean/FeedbackDetail', [
            'feedback' => $feedback,
            'suggestions' => $suggestions,
            'user'     => session('user'),
        ]);
    }

    // ── Respond ───────────────────────────────────────────────
    public function respond(Request $request, int $id): RedirectResponse
    {
        $request->validate(['response' => ['required', 'string', 'min:5', 'max:3000']]);

        try {
            Http::timeout(10)->post($this->feedbackApiUrl("dean/feedbacks/{$id}/respond"), [
                'response'       => $request->response,
                'responder_role' => 'dean',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['response' => 'Service unavailable.']);
        }

        return back()->with('success', 'Response submitted successfully.');
    }

    // ── Escalate to Rector ────────────────────────────────────
    public function escalate(Request $request, int $id): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:1000']]);

        try {
            Http::timeout(10)->post($this->feedbackApiUrl("dean/feedbacks/{$id}/escalate"), [
                'escalate_to'    => 'rector',
                'responder_role' => 'dean',
                'note'           => $request->note ?? 'Escalated to Rector for further review.',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['note' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback escalated to Rector successfully.');
    }

    // ── Resolve ───────────────────────────────────────────────
    public function resolve(Request $request, int $id): RedirectResponse
    {
        try {
            Http::timeout(10)->post($this->feedbackApiUrl("dean/feedbacks/{$id}/resolve"), [
                'responder_role' => 'dean',
                'resolution' => $request->input('resolution'),
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback resolved successfully.');
    }

    // ── Evaluations for THIS dean's faculty ───────────────────
    public function evaluations(): Response
    {
        $facultyId = $this->getFacultyId();
        $user      = session('user');

        try {
            $windowsResp = Http::timeout(5)->get($this->feedbackApiUrl('evaluation-windows'));
            $windows     = $windowsResp->successful() ? $windowsResp->json('windows', []) : [];
        } catch (\Exception $e) {
            $windows = [];
        }

        $results      = [];
        $activeWindow = null;

        if (!empty($windows) && $facultyId) {
            $activeWindow = collect($windows)->firstWhere('is_open', true);
            if ($activeWindow) {
                try {
                    $resp    = Http::timeout(5)->get($this->feedbackApiUrl('evaluations/faculty'), [
                        'faculty_id' => $facultyId,
                        'window_id'  => $activeWindow['id'],
                    ]);
                    $results = $resp->successful() ? $resp->json('analytics', []) : [];
                } catch (\Exception $e) {}
            }
        }

        return Inertia::render('Dean/Evaluations', [
            'windows'      => $windows,
            'results'      => $results,
            'activeWindow' => $activeWindow,
            'faculty_id'   => $facultyId,
            'user'         => $user,
        ]);
    }
}
