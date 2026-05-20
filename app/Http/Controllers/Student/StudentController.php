<?php
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

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

    public function dashboard(): Response
    {
        $user = session('user') ?? [];
        $departmentId = session('department_id')
            ?? ($user['profile']['department_id'] ?? null);
        $facultyId = session('faculty_id')
            ?? ($user['profile']['faculty_id'] ?? null);

        $categories = [];
        $recentFeedbacks = [];
        $activeWindow = null;

        try {
            $catResponse = Http::timeout(5)
                ->get($this->feedbackApiUrl('categories'), ['role' => 'student']);
            if ($catResponse->successful()) {
                $categories = $catResponse->json('categories', []);
            }
        } catch (\Exception $e) {
            $categories = [];
        }

        if ($departmentId) {
            try {
                $recentResponse = Http::timeout(5)
                    ->get($this->feedbackApiUrl('hod/feedbacks'), [
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

        try {
            $windowResponse = Http::timeout(5)
                ->get($this->feedbackApiUrl('evaluation-windows/active'));
            if ($windowResponse->successful()) {
                $activeWindow = $windowResponse->json('window');
            }
        } catch (\Exception $e) {
            $activeWindow = null;
        }

        return Inertia::render('Student/Dashboard', [
            'user' => $user,
            'categories' => $categories,
            'recentFeedbacks' => $recentFeedbacks,
            'activeWindow' => $activeWindow,
            'departmentId' => $departmentId ? (int) $departmentId : null,
            'facultyId' => $facultyId ? (int) $facultyId : null,
            'profile' => $user['profile'] ?? [],
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

        //  Always get a fresh anonymous token before submitting
        $refreshed = TokenService::refreshAnonToken();

        if (!$refreshed) {
            return back()->withErrors([
                'content' => 'Session expired. Please logout and login again.',
            ]);
        }

        // Small delay to ensure token is saved in Auth Service DB
        usleep(100000); // 100ms

        $user = session('user');
        $profile = $user['profile'] ?? [];

        // ✅ Get faculty_id from multiple sources
        $facultyId = session('faculty_id')
            ?? $profile['faculty_id']
            ?? $user['faculty_id']
            ?? null;


        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('feedback/submit'), [
                    'anonymous_token'      => session('anonymous_token'),
                    'category_id'          => (int) $request->category_id,
                    'content'              => $request->content,
                    'priority'             => $request->priority,
                    'sender_role'          => session('user_role'),
                    'sender_department_id' => session('department_id'),
                    // 'recipient_faculty_id' => $user['profile']['faculty_id'] ?? null,
                    'recipient_faculty_id' => $facultyId, // ✅ CRITICAL
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
                        'sender_role' => 'student',
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
            'feedback'      => $feedback,
            'code'          => $code,
            'error'         => $error,
            'department_id' => session('department_id'), // ✅ pass dept for UI awareness
        ]);
    }
    public function sendFollowup(Request $request): RedirectResponse
    {
        $request->validate([
            'tracking_code' => ['required', 'string'],
            'message'       => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        Log::info('Student followup attempt', [
            'tracking_code' => $request->tracking_code,
            'department_id' => session('department_id'),
            'role'          => session('user_role'),
        ]);

        try {
            $response = Http::timeout(10)
                ->post($this->feedbackApiUrl('feedback/followup'), [
                    'tracking_code'        => strtoupper($request->tracking_code),
                    'message'              => $request->message,
                    'direction'            => 'sender_to_recipient',
                    'sender_role'          => 'student',
                    'sender_department_id' => session('department_id'),
                ]);
        } catch (\Exception $e) {
            return back()->withErrors(['message' => 'Service unavailable. Please try again.']);
        }

        $data = $response->json();

        if (!$response->successful()) {
            return back()->withErrors([
                'message' => $data['message'] ?? 'Failed to send follow-up.',
            ]);
        }

        return back()->with('followup_success', true);
    }

    // Add to StudentController.php

    public function evaluations(): Response
    {
        $window = null;
        $error  = null;

        try {
            $windowResp = Http::timeout(5)
                ->get($this->feedbackApiUrl('evaluation-windows/active'));

            if ($windowResp->successful()) {
                $window = $windowResp->json('window');
            } else {
                $error = $windowResp->json('message', 'No evaluation window is currently open.');
            }
        } catch (\Exception $e) {
            $error = 'Could not connect to evaluation service.';
        }

        $user         = session('user');
        $departmentId = session('department_id')
            ?? ($user['profile']['department_id'] ?? null);

        // Load lecturers for this department from Auth Service
        $lecturers = [];
        if ($departmentId) {
            try {
                $lecResp = Http::timeout(5)
                    ->get($this->feedbackApiUrl('lecturers/' . $departmentId));
                $lecturers = $lecResp->successful()
                    ? $lecResp->json('lecturers', [])
                    : [];
            } catch (\Exception $e) {
                $lecturers = [];
            }
        }

        return Inertia::render('Student/Evaluations', [
            'window'        => $window,
            'lecturers'     => $lecturers,
            'error'         => $error,
            'user'          => $user,
            'department_id' => $departmentId,
            'faculty_id'    => session('faculty_id') ?? ($user['profile']['faculty_id'] ?? null),
            'academic_year' => $user['profile']['academic_year'] ?? '',
            'semester'      => $user['profile']['semester'] ?? 1,
        ]);
    }

 public function submitEvaluation(Request $request): RedirectResponse
{
    $request->validate([
        'window_id'              => ['required', 'integer'],
        'course_code'            => ['required', 'string', 'max:20'],
        'subject_name'           => ['required', 'string', 'max:150'],
        'lecturer_id'            => ['required', 'integer'],
        'lecturer_name'          => ['required', 'string'],
        'teaching_quality'       => ['required', 'integer', 'min:1', 'max:5'],
        'course_content'         => ['required', 'integer', 'min:1', 'max:5'],
        'assessment_fairness'    => ['required', 'integer', 'min:1', 'max:5'],
        'resources_available'    => ['required', 'integer', 'min:1', 'max:5'],
        'lecturer_accessibility' => ['required', 'integer', 'min:1', 'max:5'],
        'overall_rating'         => ['required', 'integer', 'min:1', 'max:5'],
        'comments'               => ['nullable', 'string', 'max:2000'],
    ]);

    // ✅ Use existing session token — DO NOT refresh for evaluations
    $anonToken = session('anonymous_token');
    if (!$anonToken) {
        TokenService::refreshAnonToken();
        $anonToken = session('anonymous_token');
    }

    if (!$anonToken) {
        return back()->withErrors(['error' => 'Session expired. Please login again.']);
    }

    $user    = session('user');
    $profile = $user['profile'] ?? [];

    // ✅ Get department_id — check multiple sources
    $departmentId = session('department_id')
        ?? $profile['department_id']
        ?? null;

    // ✅ Get faculty_id — check multiple sources
    $facultyId = session('faculty_id')
        ?? $profile['faculty_id']
        ?? null;

    // If still null, try to fetch from auth service
    if (!$facultyId && $departmentId) {
        try {
            $resp = Http::withHeaders(['Authorization' => 'Bearer ' . session('jwt_token')])
                ->timeout(5)
                ->get(config('services.auth_service.url') . '/api/admin/departments/' . $departmentId);
            if ($resp->successful()) {
                $deptData  = $resp->json('department', []);
                $facultyId = $deptData['faculty_id'] ?? null;
                // Cache it in session
                if ($facultyId) {
                    session(['faculty_id' => $facultyId]);
                }
            }
        } catch (\Exception $e) {
            // Continue with null — feedback service will handle
        }
    }

    $academicYear = $profile['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1));
    $semester     = $profile['semester'] ?? 1;

    Log::info('Evaluation submit', [
        'course_code'   => $request->course_code,
        'department_id' => $departmentId,
        'faculty_id'    => $facultyId,
        'window_id'     => $request->window_id,
        'lecturer_id'   => $request->lecturer_id,
    ]);

    try {
        $response = Http::timeout(10)
            ->post($this->feedbackApiUrl('evaluations/submit'), [
                'anonymous_token'        => $anonToken,
                'window_id'              => (int) $request->window_id,
                'course_code'            => strtoupper(trim($request->course_code)),
                'subject_name'           => $request->subject_name,
                'lecturer_id'            => (int) $request->lecturer_id,
                'lecturer_name'          => $request->lecturer_name,
                'department_id'          => (int) ($departmentId ?? 0),
                'faculty_id'             => (int) ($facultyId ?? 0),
                'academic_year'          => $academicYear,
                'semester'               => (int) $semester,
                'teaching_quality'       => (int) $request->teaching_quality,
                'course_content'         => (int) $request->course_content,
                'assessment_fairness'    => (int) $request->assessment_fairness,
                'resources_available'    => (int) $request->resources_available,
                'lecturer_accessibility' => (int) $request->lecturer_accessibility,
                'overall_rating'         => (int) $request->overall_rating,
                'comments'               => $request->comments,
            ]);
    } catch (\Exception $e) {
        Log::error('Evaluation error: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Service unavailable. Please try again.']);
    }

    $data = $response->json();

    Log::info('Evaluation response', ['status' => $response->status(), 'data' => $data]);

    if (!$response->successful()) {
        return back()->withErrors([
            'error' => $data['message'] ?? 'Failed to submit evaluation.',
        ]);
    }

    return back()->with([
        'eval_success' => 'Evaluation for ' . strtoupper($request->course_code) . ' — ' . $request->subject_name . ' submitted successfully! ✓',
        'course_code'  => strtoupper($request->course_code),
    ]);
}
}
