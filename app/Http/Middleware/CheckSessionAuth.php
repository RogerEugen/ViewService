<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSessionAuth
{
    public function handle(Request $request, Closure $next)
    {
        // No JWT in session — redirect to login
        if (!session()->has('jwt_token')) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Please login to continue.']);
        }

        // Check if token has expired (30 min = 1800 seconds)
        $issuedAt  = session('token_issued_at', 0);
        $expiresIn = session('expires_in', 1800);

        if ((now()->timestamp - $issuedAt) >= $expiresIn) {
            session()->flush();
            return redirect()->route('login')
                ->withErrors(['email' => 'Your session has expired. Please login again.']);
        }

        // Block access to dashboards if must_change_password is still true
        if (session('must_change_password') && !$request->routeIs('password.*')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}