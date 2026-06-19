<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    public function index(Request $request): Response
    {
        $role = $this->role();
        $rooms = $this->roomsFor($role);
        $selected = (string) $request->query('room', array_key_first($rooms) ?? '');

        if (!array_key_exists($selected, $rooms)) {
            $selected = array_key_first($rooms) ?? '';
        }

        $payload = ['messages' => [], 'realtime_channel' => null];
        if ($selected !== '') {
            try {
                $response = $this->client()->get($this->url($selected));
                if ($response->successful()) {
                    $payload = $response->json();
                }
            } catch (\Throwable) {
                // The page remains available while the real-time service restarts.
            }
        }

        return Inertia::render('Communication', [
            'rooms' => collect($rooms)->map(
                fn (string $label, string $key) => ['key' => $key, 'label' => $label]
            )->values(),
            'selectedRoom' => $selected,
            'messages' => $payload['messages'] ?? [],
            'realtimeChannel' => $payload['realtime_channel'] ?? null,
            'currentRole' => $role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $role = $this->role();
        $rooms = $this->roomsFor($role);
        $data = $request->validate([
            'room' => ['required', 'string'],
            'message' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        if (!array_key_exists($data['room'], $rooms)) {
            abort(403);
        }

        try {
            $response = $this->client()->post($this->url($data['room']), [
                'sender_role' => $role,
                'message' => $data['message'],
            ]);
        } catch (\Throwable) {
            return back()->withErrors(['message' => 'The communication service is unavailable.']);
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'message' => $response->json('message', 'Message could not be sent.'),
            ]);
        }

        return back()->with('success', 'Message sent securely.');
    }

    private function roomsFor(string $role): array
    {
        $facultyId = session('faculty_id')
            ?? data_get(session('user'), 'faculty_id')
            ?? data_get(session('user'), 'profile.faculty_id');

        return match ($role) {
            'hod', 'dean' => array_filter([
                $facultyId ? "faculty.{$facultyId}.leadership" : null
                    => $facultyId ? 'Faculty Leadership' : null,
                'campus.leadership' => 'Rector Instructions',
            ]),
            'rector' => [
                'campus.leadership' => 'Dean and HOD Leadership',
            ],
            'lecturer' => [],
            default => [],
        };
    }

    private function role(): string
    {
        $role = (string) session('user_role', data_get(session('user'), 'role', ''));

        return $role === 'lecture' ? 'lecturer' : $role;
    }

    private function client()
    {
        return Http::timeout(10)->withHeaders([
            'X-View-Service-Key' => (string) config('services.feedback_service.key'),
        ]);
    }

    private function url(string $room): string
    {
        return rtrim(config('services.feedback_service.url'), '/')
            . '/api/communications/' . urlencode($room);
    }
}
