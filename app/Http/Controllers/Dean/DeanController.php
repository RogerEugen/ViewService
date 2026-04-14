<?php
namespace App\Http\Controllers\Dean;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class DeanController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    private function authApiUrl(string $path): string
    {
        return config('services.auth_service.url') . '/api/' . $path;
    }

    // ── Dashboard ──────────────────────────────────────────────
    public function dashboard(): Response
    {
        return Inertia::render('Dean/Dashboard');
    }

    // ── Feedback list for Dean's faculty ──────────────────────
    public function feedbacks(): Response
    {
        $facultyId = session('user')['profile']['faculty_id'] ?? null;

        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl('dean/feedbacks'), [
                    'faculty_id' => $facultyId,
                ]);

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
        ];

        return Inertia::render('Dean/Feedbacks', [
            'feedbacks'  => $feedbacks,
            'stats'      => $stats,
            'faculty_id' => $facultyId,
            'user'       => session('user'),
        ]);
    }

    // ── View single feedback ──────────────────────────────────
    public function show(int $id): Response
    {
        try {
            $response = Http::timeout(10)
                ->get($this->feedbackApiUrl("dean/feedbacks/{$id}"));

            $feedback = $response->successful()
                ? $response->json('feedback')
                : null;
        } catch (\Exception $e) {
            $feedback = null;
        }

        return Inertia::render('Dean/FeedbackDetail', [
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
                ->post($this->feedbackApiUrl("dean/feedbacks/{$id}/respond"), [
                    'response'       => $request->response,
                    'responder_role' => 'dean',
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

    // ── Escalate to Rector ────────────────────────────────────
    public function escalate(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl("dean/feedbacks/{$id}/escalate"), [
                    'escalate_to'    => 'rector',
                    'responder_role' => 'dean',
                    'note'           => $request->note ?? 'Escalated to Rector for further review.',
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['note' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'note' => $response->json('message', 'Failed to escalate.'),
            ]);
        }

        return back()->with('success', 'Feedback escalated to Rector successfully.');
    }

    // ── Resolve ───────────────────────────────────────────────
    public function resolve(int $id): RedirectResponse
    {
        try {
            Http::timeout(10)
                ->post($this->feedbackApiUrl("dean/feedbacks/{$id}/resolve"));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Service unavailable.']);
        }

        return back()->with('success', 'Feedback marked as resolved.');
    }
}