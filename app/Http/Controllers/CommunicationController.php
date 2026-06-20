<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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
                $response = $this->client()->get($this->url($selected), [
                    'sender_role' => $role,
                    'actor_id' => $this->userId(),
                ]);
                if ($response->successful()) {
                    $payload = $response->json();
                }
            } catch (\Throwable) {
                // The page remains available while the real-time service restarts.
            }
        }

        $roomMeta = [];
        try {
            $overviewResponse = $this->client()->post(
                rtrim(config('services.feedback_service.url'), '/').'/api/communications/overview',
                [
                    'sender_role' => $role,
                    'actor_id' => $this->userId(),
                    'rooms' => array_keys($rooms),
                ]
            );
            if ($overviewResponse->successful()) {
                $roomMeta = collect($overviewResponse->json('rooms', []))->keyBy('key');
            }
        } catch (\Throwable) {
        }

        return Inertia::render('Communication', [
            'rooms' => collect($rooms)->map(
                fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                    'participant_role' => str_contains($key, '.hod.') ? 'hod' : 'dean',
                    'unread_count' => $key === $selected
                        ? 0
                        : (int) data_get($roomMeta->get($key), 'unread_count', 0),
                    'realtime_channel' => data_get($roomMeta->get($key), 'realtime_channel'),
                ]
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
                'actor_id' => $this->userId(),
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

    public function markRead(Request $request): JsonResponse
    {
        $role = $this->role();
        $rooms = $this->roomsFor($role);
        $data = $request->validate([
            'room' => ['required', 'string'],
        ]);

        if (!array_key_exists($data['room'], $rooms)) {
            return response()->json(['message' => 'Conversation not available.'], 403);
        }

        try {
            $response = $this->client()->post($this->url($data['room']).'/read', [
                'sender_role' => $role,
                'actor_id' => $this->userId(),
            ]);
        } catch (\Throwable) {
            return response()->json(['message' => 'Read state could not be updated.'], 503);
        }

        return response()->json(
            $response->json() ?: ['success' => $response->successful()],
            $response->status()
        );
    }

    private function roomsFor(string $role): array
    {
        $userId = $this->userId();

        return match ($role) {
            'hod' => $userId > 0
                ? ["leadership.hod.{$userId}" => 'Private Rector Conversation']
                : [],
            'dean' => $userId > 0
                ? ["leadership.dean.{$userId}" => 'Private Rector Conversation']
                : [],
            'rector' => $this->rectorRooms(),
            default => [],
        };
    }

    private function rectorRooms(): array
    {
        try {
            $client = Http::withToken((string) session('jwt_token'))->timeout(10);
            $baseUrl = rtrim(config('services.auth_service.url'), '/').'/api/admin';
            $facultyResponse = $client->get("{$baseUrl}/faculties");
            $departmentResponse = $client->get("{$baseUrl}/departments");

            $rooms = [];
            foreach ($facultyResponse->successful() ? $facultyResponse->json('faculties', []) : [] as $faculty) {
                if (!empty($faculty['dean_user_id'])) {
                    $rooms["leadership.dean.{$faculty['dean_user_id']}"] =
                        'Dean '.($faculty['dean_name'] ?: 'Faculty').' · '.$faculty['name'];
                }
            }
            foreach ($departmentResponse->successful() ? $departmentResponse->json('departments', []) : [] as $department) {
                if (!empty($department['hod_user_id'])) {
                    $rooms["leadership.hod.{$department['hod_user_id']}"] =
                        'HOD '.($department['hod_name'] ?: 'Department').' · '.$department['name'];
                }
            }

            return $rooms;
        } catch (\Throwable) {
            return [];
        }
    }

    private function role(): string
    {
        $role = (string) session('user_role', data_get(session('user'), 'role', ''));

        return $role === 'lecture' ? 'lecturer' : $role;
    }

    private function userId(): int
    {
        return (int) (
            data_get(session('user'), 'id')
            ?? session('user_id')
            ?? 0
        );
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
