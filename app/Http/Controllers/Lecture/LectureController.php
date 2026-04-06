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
}