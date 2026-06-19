<?php

namespace App\Http\Controllers;

use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class FeedbackChatController extends Controller
{
    public function lecturer(Request $request): Response
    {
        $code = strtoupper(trim((string) $request->query('code', '')));
        $thread = null;
        $error = null;

        if ($code !== '') {
            if (!TokenService::refreshAnonToken()) {
                $error = 'Your secure session expired. Please sign in again.';
            } else {
                try {
                    $response = Http::timeout(10)->get(
                        $this->feedbackUrl("feedback/lecturer-thread/{$code}"),
                        [
                            'anonymous_token' => session('anonymous_token'),
                            'sender_department_id' => session('department_id'),
                        ]
                    );

                    if ($response->successful()) {
                        $thread = $response->json('thread');
                    } else {
                        $error = $response->json('message', 'The tracking code could not be opened.');
                    }
                } catch (\Throwable) {
                    $error = 'The feedback service is temporarily unavailable.';
                }
            }
        }

        return Inertia::render('FeedbackChat', [
            'currentRole' => 'lecturer',
            'selectedCode' => $code,
            'threads' => [],
            'thread' => $thread,
            'error' => $error,
        ]);
    }

    public function lecturerSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        if (!TokenService::refreshAnonToken()) {
            return back()->withErrors(['message' => 'Your secure session expired. Please sign in again.']);
        }

        try {
            $response = Http::timeout(10)->post($this->feedbackUrl('feedback/followup'), [
                'tracking_code' => strtoupper($data['tracking_code']),
                'message' => $data['message'],
                'direction' => 'sender_to_recipient',
                'sender_role' => 'lecturer',
                'sender_department_id' => session('department_id'),
                'anonymous_token' => session('anonymous_token'),
            ]);
        } catch (\Throwable) {
            return back()->withErrors(['message' => 'The feedback service is temporarily unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'message' => $response->json('message', 'The message could not be sent.'),
            ]);
        }

        return back()->with('success', 'Message sent securely.');
    }

    public function rector(Request $request): Response
    {
        $threads = [];
        $thread = null;
        $error = null;

        try {
            $response = $this->serviceClient()->get($this->feedbackUrl('rector/lecturer-threads'));
            if ($response->successful()) {
                $threads = $response->json('threads', []);
            }
        } catch (\Throwable) {
            $error = 'The feedback service is temporarily unavailable.';
        }

        $code = strtoupper(trim((string) $request->query('code', $threads[0]['tracking_code'] ?? '')));
        if ($code !== '') {
            try {
                $response = $this->serviceClient()->get(
                    $this->feedbackUrl('rector/lecturer-threads/' . urlencode($code))
                );
                if ($response->successful()) {
                    $thread = $response->json('thread');
                } else {
                    $error = $response->json('message', 'The selected thread could not be opened.');
                }
            } catch (\Throwable) {
                $error = 'The selected thread could not be loaded.';
            }
        }

        return Inertia::render('FeedbackChat', [
            'currentRole' => 'rector',
            'selectedCode' => $code,
            'threads' => $threads,
            'thread' => $thread,
            'error' => $error,
        ]);
    }

    public function rectorSend(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tracking_code' => ['required', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        try {
            $response = $this->serviceClient()->post(
                $this->feedbackUrl('rector/lecturer-threads/' . urlencode(strtoupper($data['tracking_code'])) . '/reply'),
                ['message' => $data['message']]
            );
        } catch (\Throwable) {
            return back()->withErrors(['message' => 'The feedback service is temporarily unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'message' => $response->json('message', 'The reply could not be sent.'),
            ]);
        }

        return back()->with('success', 'Reply sent securely.');
    }

    private function serviceClient()
    {
        return Http::timeout(10)->withHeaders([
            'X-View-Service-Key' => (string) config('services.feedback_service.key'),
        ]);
    }

    private function feedbackUrl(string $path): string
    {
        return rtrim(config('services.feedback_service.url'), '/') . '/api/' . ltrim($path, '/');
    }
}
