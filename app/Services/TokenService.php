<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class TokenService
{
    // Call auth service to get a fresh anonymous token
    // Call this BEFORE every feedback submission
    public static function refreshAnonToken(): bool
    {
        $jwtToken = session('jwt_token');

        if (!$jwtToken) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withToken($jwtToken)
                ->post(
                    config('services.auth_service.url') . '/api/auth/refresh-anon-token'
                );

            if ($response->successful()) {
                $data = $response->json();
                session([
                    'anonymous_token' => $data['anonymous_token'],
                    'token_issued_at' => now()->timestamp,
                    'expires_in'      => $data['expires_in'],
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    // Check if anon token is still fresh (within 25 min to be safe)
    public static function anonTokenIsValid(): bool
    {
        $issuedAt  = session('token_issued_at', 0);
        $expiresIn = session('expires_in', 1800);
        $elapsed   = now()->timestamp - $issuedAt;

        // Refresh if more than 25 minutes old
        return $elapsed < ($expiresIn - 300);
    }
}