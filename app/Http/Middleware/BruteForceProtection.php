<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY LAYER 2: Brute-Force & Suspicious Activity Monitor
 * 
 * Tracks failed login attempts per IP and enforces progressive lockout.
 * Logs suspicious access patterns for forensic auditing.
 */
class BruteForceProtection
{
    /**
     * Maximum failed login attempts before lockout.
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout duration in minutes.
     */
    protected int $lockoutMinutes = 15;

    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $cacheKey = 'bruteforce_' . md5($ip);
        $lockKey = 'bruteforce_lock_' . md5($ip);

        // Check if IP is currently locked out
        if (cache()->has($lockKey)) {
            $remainingSeconds = cache()->get($lockKey) - time();
            $remainingMinutes = max(1, ceil($remainingSeconds / 60));

            Log::warning("[SECURITY] Locked-out IP attempted access", [
                'ip' => $ip,
                'path' => $request->path(),
                'remaining_minutes' => $remainingMinutes,
            ]);

            abort(429, "Too many failed attempts. Your IP has been temporarily blocked. Please try again in {$remainingMinutes} minutes.");
        }

        $response = $next($request);

        // After response, check if this was a failed login attempt
        if ($request->is('login') && $request->isMethod('post') && $response->getStatusCode() === 302) {
            // Check if redirect is back to login (failed attempt) vs. dashboard (success)
            $location = $response->headers->get('Location');
            if ($location && str_contains($location, 'login')) {
                $attempts = (int) cache()->get($cacheKey, 0) + 1;
                cache()->put($cacheKey, $attempts, now()->addMinutes($this->lockoutMinutes));

                if ($attempts >= $this->maxAttempts) {
                    cache()->put($lockKey, time() + ($this->lockoutMinutes * 60), now()->addMinutes($this->lockoutMinutes));

                    Log::critical("[SECURITY] Brute-force lockout triggered", [
                        'ip' => $ip,
                        'attempts' => $attempts,
                        'lockout_minutes' => $this->lockoutMinutes,
                        'user_agent' => $request->userAgent(),
                    ]);
                } else {
                    Log::notice("[SECURITY] Failed login attempt", [
                        'ip' => $ip,
                        'attempt_count' => $attempts,
                        'email_tried' => $request->input('email'),
                    ]);
                }
            } else {
                // Successful login — clear failed attempt counter
                cache()->forget($cacheKey);
                cache()->forget($lockKey);
            }
        }

        return $response;
    }
}
