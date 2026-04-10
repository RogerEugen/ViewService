<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    public function MyInfo(): Response
    {
        return Inertia::render('Student/MyInfo', [
            'user' => session('user'),
        ]);
    }

    public function FeedBack(): Response
    {
        $response = Http::timeout(5)
            ->get($this->feedbackApiUrl('categories'), ['role' => 'student']);

        $categories = $response->successful()
            ? $response->json('categories', [])
            : [];

        return Inertia::render('Student/FeedBack', [
            'categories'    => $categories,
            'user'          => session('user'),
            'department_id' => session('department_id'),
        ]);
    }

    public function submitFeedback(Request $request): RedirectResponse
    {
        $request->validate([
            'category_id' => ['required', 'integer'],
            'content'     => ['required', 'string', 'min:10', 'max:5000'],
            'priority'    => ['required', 'in:low,medium,high,urgent'],
        ]);

        // ✅ Always get a fresh anonymous token before submitting
        $refreshed = TokenService::refreshAnonToken();

        if (!$refreshed) {
            return back()->withErrors([
                'content' => 'Session expired. Please logout and login again.',
            ]);
        }

        // Small delay to ensure token is saved in Auth Service DB
        usleep(100000); // 100ms

        $user = session('user');

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('feedback/submit'), [
                    'anonymous_token'      => session('anonymous_token'),
                    'category_id'          => (int) $request->category_id,
                    'content'              => $request->content,
                    'priority'             => $request->priority,
                    'sender_role'          => session('user_role'),
                    'sender_department_id' => session('department_id'),
                    'recipient_faculty_id' => $user['profile']['faculty_id'] ?? null,
                ]);
        } catch (\Exception $e) {
            return back()->withErrors([
                'content' => 'Feedback service unavailable. Please try again.',
            ]);
        }

        $data = $response->json();

        if (!$response->successful()) {
            return back()->withErrors([
                'content' => $data['message'] ?? 'Failed to submit feedback.',
            ]);
        }

        return back()->with([
            'success'       => 'Feedback submitted successfully.',
            'tracking_code' => $data['tracking_code'],
        ]);
    }

    public function trackFeedback(Request $request): Response
    {
        $code     = strtoupper($request->query('code', ''));
        $feedback = null;
        $error    = null;

        if ($code) {
            try {
                $response = Http::timeout(5)
                    ->get($this->feedbackApiUrl('feedback/track/' . $code), [
                        'sender_role' => 'student', // ✅ pass role for ownership check
                    ]);

                if ($response->successful()) {
                    $feedback = $response->json('feedback');
                } else {
                    $data  = $response->json();
                    $error = match ($data['reason'] ?? '') {
                        'role_mismatch'  => 'This tracking code was not submitted by a student.',
                        'route_mismatch' => 'This tracking code does not match your role.',
                        default          => $data['message'] ?? 'Tracking code not found.',
                    };
                }
            } catch (\Exception $e) {
                $error = 'Tracking service unavailable.';
            }
        }

        return Inertia::render('Student/TrackFeedback', [
            'feedback' => $feedback,
            'code'     => $code,
            'error'    => $error,
        ]);
    }

    public function sendFollowup(Request $request): RedirectResponse
    {
        $request->validate([
            'tracking_code' => ['required', 'string'],
            'message'       => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('feedback/followup'), [
                    'tracking_code'        => $request->tracking_code,
                    'message'              => $request->message,
                    'direction'            => 'sender_to_recipient',
                    'sender_role'          => 'student',
                    'sender_department_id' => session('department_id'), // ✅ dept check
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Service unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'message' => $response->json('message', 'Failed to send follow-up.'),
            ]);
        }

        return back()->with('followup_success', true);
    }
}