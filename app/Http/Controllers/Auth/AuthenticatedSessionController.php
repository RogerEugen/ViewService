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
        // If already logged in redirect to their dashboard
        if (session()->has('jwt_token')) {
            return redirect($this->dashboardRoute(session('user_role')));
        }

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    // ─────────────────────────────────────────────
    // HANDLE LOGIN FORM SUBMISSION
    // ─────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        // 1. Validate input locally first
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        // 2. Call Auth Service API
        try {
            $response = Http::timeout(10)
                ->post(
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

        // 3. Handle failed login
        if (!$response->successful()) {
            return back()->withErrors([
                'email' => $data['message'] ?? 'Invalid credentials.',
            ])->withInput($request->only('email'));
        }

        // 4. Handle account deactivated (403)
        if ($response->status() === 403) {
            return back()->withErrors([
                'email' => $data['message'] ?? 'Your account has been deactivated.',
            ])->withInput($request->only('email'));
        }

        // 5. Handle first login — must change password
        if (!empty($data['must_change_password'])) {
            // Store JWT only — no anon token yet until password is changed
            session([
                'jwt_token'            => $data['jwt'],
                'must_change_password' => true,
                'user'                 => $data['user'],
                'user_role'            => $data['user']['role'],
                'token_issued_at'      => now()->timestamp,   // ✅ ADD THIS
                'expires_in'           => 1800,               // ✅ ADD THIS
            ]);

            return redirect()->route('password.change');
        }

        // 6. Successful login — store everything in session
        $request->session()->regenerate();

        session([
            'jwt_token'       => $data['jwt'],
            'anonymous_token' => $data['anonymous_token'],
            'expires_in'      => $data['expires_in'],
            'token_issued_at' => now()->timestamp,
            'user'            => $data['user'],
            'user_role'       => $data['user']['role'],
            'user_name'       => $data['user']['name'],
            'user_email'      => $data['user']['email'],
            'department_id'   => $data['user']['department_id'],
        ]);

        // 7. Redirect to correct dashboard by role
        return redirect()->intended(
            $this->dashboardRoute($data['user']['role'])
        );
    }

    // ─────────────────────────────────────────────
    // HANDLE CHANGE PASSWORD (first login)
    // ─────────────────────────────────────────────
    public function showChangePassword(): Response |RedirectResponse
    {
        // Guard — only accessible if must_change_password is set
        if (!session('must_change_password')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/ChangePassword');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
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
            'new_password.regex'     => 'Password must have at least one uppercase letter and one number.',
        ]);

        // Call Auth Service change password endpoint
        try {
            $response = Http::timeout(10)
                ->withToken(session('jwt_token'))
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

        if (!$response->successful()) {
            return back()->withErrors([
                'current_password' => $data['message'] ?? 'Password change failed.',
            ]);
        }

        // Password changed — now we have the anonymous token
        // Update session with anonymous token and clear the force flag
        $request->session()->regenerate();

        session([
            'anonymous_token'      => $data['anonymous_token'],
            'expires_in'           => $data['expires_in'],
            'token_issued_at'      => now()->timestamp,
            'must_change_password' => false,
        ]);

        return redirect()->intended(
            $this->dashboardRoute(session('user_role'))
        );
    }

    // ─────────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        // Tell Auth Service to invalidate the JWT
        if (session()->has('jwt_token')) {
            try {
                Http::timeout(5)
                    ->withToken(session('jwt_token'))
                    ->post(
                        config('services.auth_service.url') . '/api/auth/logout'
                    );
            } catch (\Exception $e) {
                // Even if auth service is down — still clear local session
            }
        }

        // Clear everything from session
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // ─────────────────────────────────────────────
    // PRIVATE HELPER — map role to dashboard route
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
            default     => '/',
        };
    }
}