<?php
namespace App\Http\Controllers\Lecture;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class LectureController extends Controller
{
    private function feedbackApiUrl(string $path): string
    {
        return config('services.feedback_service.url') . '/api/' . $path;
    }

    public function dashboard(): Response
    {
        $user = session('user') ?? [];
        $departmentId = session('department_id')
            ?? ($user['profile']['department_id'] ?? null);

        $categories = [];
        $recentFeedbacks = [];

        try {
            $catResponse = Http::timeout(5)
                ->get($this->feedbackApiUrl('categories'), ['role' => 'lecturer']);
            if ($catResponse->successful()) {
                $categories = $catResponse->json('categories', []);
            }
        } catch (\Exception $e) {
            $categories = [];
        }

        if ($departmentId) {
            try {
                $recentResponse = Http::timeout(5)
                    ->get($this->feedbackApiUrl('rector/feedbacks'), [
                        'department_id' => $departmentId,
                    ]);
                if ($recentResponse->successful()) {
                    $recentFeedbacks = collect($recentResponse->json('feedbacks', []))
                        ->take(8)
                        ->values()
                        ->all();
                }
            } catch (\Exception $e) {
                $recentFeedbacks = [];
            }
        }

        return Inertia::render('Lecture/Dashboard', [
            'user' => $user,
            'categories' => $categories,
            'recentFeedbacks' => $recentFeedbacks,
            'departmentId' => $departmentId ? (int) $departmentId : null,
            'profile' => $user['profile'] ?? [],
        ]);
    }

    public function FeedBack(): Response
    {
        $response = Http::timeout(5)
            ->get($this->feedbackApiUrl('categories'), ['role' => 'lecturer']);

        $categories = $response->successful()
            ? $response->json('categories', [])
            : [];

        return Inertia::render('Lecture/Feedback', [
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

        // ✅ ALWAYS get a fresh anonymous token before submitting
        $refreshed = TokenService::refreshAnonToken();

        if (!$refreshed) {
            return back()->withErrors([
                'content' => 'Could not generate anonymous token. Please logout and login again.',
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('feedback/submit'), [
                    'anonymous_token'      => session('anonymous_token'), // ✅ fresh token
                    'category_id'          => $request->category_id,
                    'content'              => $request->content,
                    'priority'             => $request->priority,
                    'sender_role'          => 'lecturer',
                    'sender_department_id' => session('department_id'),
                    'recipient_faculty_id' => null,
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
                        'sender_role' => 'lecturer', //  lecturer can only see their own
                    ]);

                if ($response->successful()) {
                    $feedback = $response->json('feedback');
                } else {
                    $data  = $response->json();
                    $error = match ($data['reason'] ?? '') {
                        'role_mismatch'  => 'This tracking code was not submitted by a lecturer.',
                        'route_mismatch' => 'Lecturers cannot track student feedback.',
                        default          => $data['message'] ?? 'Tracking code not found.',
                    };
                }
            } catch (\Exception $e) {
                $error = 'Tracking service unavailable.';
            }
        }

        return Inertia::render('Lecture/TrackFeedback', [
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
                    'sender_role'          => 'lecturer',
                    'sender_department_id' => session('department_id'),
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



    public function evaluationResults(): Response
{
    $user         = session('user');
    $departmentId = session('department_id');

    // ✅ Get the current lecturer's user ID
    // This is the same ID stored as lecturer_id in course_evaluations
    $lecturerId = $user['id'] ?? null;

    // If not in session root, try to get from auth service
    if (!$lecturerId) {
        try {
            $meResp = Http::withHeaders(['Authorization' => 'Bearer ' . session('jwt_token')])
                ->timeout(5)
                ->get(config('services.auth_service.url') . '/api/auth/me');
            if ($meResp->successful()) {
                $lecturerId = $meResp->json('user.id') ?? null;
            }
        } catch (\Exception $e) {
            $lecturerId = null;
        }
    }

    // Get all windows
    try {
        $windowsResp = Http::timeout(5)
            ->get($this->feedbackApiUrl('evaluation-windows'));
        $windows = $windowsResp->successful() ? $windowsResp->json('windows', []) : [];
    } catch (\Exception $e) {
        $windows = [];
    }

    // Get results filtered by THIS lecturer's ID only
    $results = [];
    if ($departmentId && !empty($windows)) {
        try {
            $activeWindow = collect($windows)->firstWhere('is_open', true);
            if ($activeWindow) {
                $resp = Http::timeout(5)
                    ->get($this->feedbackApiUrl('evaluations/lecturer'), [
                        'department_id' => $departmentId,
                        'lecturer_id'   => $lecturerId,  // ✅ filter by THIS lecturer
                        'window_id'     => $activeWindow['id'],
                    ]);
                $results = $resp->successful() ? $resp->json('analytics', []) : [];
            }
        } catch (\Exception $e) {
            $results = [];
        }
    }

    return Inertia::render('Lecture/EvaluationResults', [
        'windows'       => $windows,
        'results'       => $results,
        'department_id' => $departmentId,
        'lecturer_id'   => $lecturerId,
        'user'          => $user,
    ]);
}
}
