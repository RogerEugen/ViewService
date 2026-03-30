<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;


class AuthenticatedSessionController extends Controller
{
    // ─────────────────────────────────────────────
    // SHOW LOGIN PAGE
    // ─────────────────────────────────────────────
    public function create(): Response|RedirectResponse
    {
        if (session()->has('jwt_token') && !session('must_change_password')) {
            return redirect($this->dashboardRoute(session('user_role')));
        }

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    // ─────────────────────────────────────────────
    // LOGIN
    // ─────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        try {
            $response = Http::timeout(10)->post(
                config('services.auth_service.url') . '/api/auth/login',
                [
                    'email'    => $request->email,
                    'password' => $request->password,
                ]
            );
        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Auth service is unavailable. Please try again later.',
            ])->withInput($request->only('email'));
        }

        $data = $response->json();

        if ($response->status() === 403) {
            return back()->withErrors([
                'email' => $data['message'] ?? 'Your account has been deactivated.',
            ])->withInput($request->only('email'));
        }

        if (!$response->successful()) {
            return back()->withErrors([
                'email' => $data['message'] ?? 'Invalid credentials.',
            ])->withInput($request->only('email'));
        }

        // First login — must change password
        if (!empty($data['must_change_password'])) {
            session()->flush();
            session([
                'jwt_token'            => $data['jwt'],
                'must_change_password' => true,
                'user_role'            => $data['user']['role'],
                'user_name'            => $data['user']['name'],
                'user'                 => $data['user'],
                'token_issued_at'      => now()->timestamp,
                'expires_in'           => 1800,
            ]);
            return redirect()->route('password.change');
        }

        // Normal login
        $this->storeFullSession($request, $data);

        return redirect()->intended($this->dashboardRoute($data['user']['role']));
    }

    // ─────────────────────────────────────────────
    // SHOW CHANGE PASSWORD PAGE
    // ─────────────────────────────────────────────
    public function showChangePassword(): Response|RedirectResponse
    {
        if (!session('must_change_password')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/ChangePassword', [
            'userName' => session('user_name'),
            'userRole' => session('user_role'),
        ]);
    }

    // ─────────────────────────────────────────────
    // SUBMIT NEW PASSWORD
    // ─────────────────────────────────────────────
    public function updatePassword(Request $request): RedirectResponse
    {
        // Handle cancel — skip password change and logout
        if ($request->has('cancel')) {
            session()->flush();
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('login')
                ->with('status', 'Password change cancelled. Please login again.');
        }

        $request->validate([
            'current_password'          => ['required', 'string'],
            'new_password'              => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
            ],
            'new_password_confirmation' => ['required', 'string'],
        ], [
            'new_password.min'       => 'Password must be at least 8 characters.',
            'new_password.confirmed' => 'Passwords do not match.',
            'new_password.regex'     => 'Password must contain at least one uppercase letter and one number.',
        ]);

        $jwtToken = session('jwt_token');

        if (!$jwtToken) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Session expired. Please login again.']);
        }

        try {
            $response = Http::timeout(10)
                ->withToken($jwtToken)
                ->post(
                    config('services.auth_service.url') . '/api/auth/change-password',
                    [
                        'current_password'          => $request->current_password,
                        'new_password'              => $request->new_password,
                        'new_password_confirmation' => $request->new_password_confirmation,
                    ]
                );
        } catch (\Exception $e) {
            return back()->withErrors([
                'current_password' => 'Auth service unavailable. Please try again.',
            ]);
        }

        $data = $response->json();

        // Failed response from Auth Service
        if (!$response->successful()) {
            return back()->withErrors([
                'current_password' => $data['message'] ?? 'Password change failed.',
            ]);
        }

        // ✅ Success — fully rebuild session
        $this->storeFullSession($request, $data);

        return redirect()->intended($this->dashboardRoute($data['user']['role']));
    }

    // ─────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        if (session()->has('jwt_token')) {
            try {
                Http::timeout(5)
                    ->withToken(session('jwt_token'))
                    ->post(config('services.auth_service.url') . '/api/auth/logout');
            } catch (\Exception $e) {
                // Still clear local session even if API call fails
            }
        }

        session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ─────────────────────────────────────────────
    // PRIVATE — store full session after login or password change
    // ─────────────────────────────────────────────
    private function storeFullSession(Request $request, array $data): void
    {
        session()->flush();
        $request->session()->regenerate();

        session([
            'jwt_token'            => $data['jwt'],
            'anonymous_token'      => $data['anonymous_token'],
            'expires_in'           => $data['expires_in'],
            'token_issued_at'      => now()->timestamp,
            'must_change_password' => false,
            'user'                 => $data['user'],
            'user_role'            => $data['user']['role'],
            'user_name'            => $data['user']['name'],
            'user_email'           => $data['user']['email'],
            'department_id'        => $data['user']['department_id'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────
    // PRIVATE — role to dashboard route
    // ─────────────────────────────────────────────
    private function dashboardRoute(string $role): string
    {
        return match ($role) {
            'student'   => route('student.dashboard'),
            'lecturer'  => route('lecturer.dashboard'),
            'hod'       => route('hod.dashboard'),
            'dean'      => route('dean.dashboard'),
            'rector'    => route('rector.dashboard'),
            'registrar' => route('registrar.dashboard'),
            'admin'     => route('admin.dashboard'),
            default     => route('login'),
        };
    }
}