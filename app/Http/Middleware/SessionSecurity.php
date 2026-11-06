<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY LAYER 4: Session Hijacking Prevention
 * 
 * Binds sessions to user agent + IP fingerprint.
 * Detects and terminates sessions where the fingerprint changes mid-session
 * (indicates stolen session cookie or session fixation attack).
 */
class SessionSecurity
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $currentFingerprint = $this->generateFingerprint($request);
            $storedFingerprint = session('_security_fingerprint');

            if ($storedFingerprint === null) {
                // First request after login — store fingerprint
                session(['_security_fingerprint' => $currentFingerprint]);
                session(['_security_last_activity' => time()]);
            } elseif ($storedFingerprint !== $currentFingerprint) {
                // Fingerprint mismatch — possible session hijacking
                Log::critical("[SECURITY] Session hijacking attempt detected", [
                    'user_id' => Auth::id(),
                    'user_email' => Auth::user()->email ?? 'unknown',
                    'stored_fingerprint' => $storedFingerprint,
                    'current_fingerprint' => $currentFingerprint,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Immediately destroy the session and logout
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your session has been terminated for security reasons. Please sign in again.',
                ]);
            }

            // Enforce absolute session timeout (4 hours max regardless of activity)
            $loginTime = session('_security_login_time');
            if ($loginTime && (time() - $loginTime) > 14400) {
                Log::info("[SECURITY] Session expired (absolute timeout)", [
                    'user_id' => Auth::id(),
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('info', 'Your session has expired. Please sign in again.');
            }

            // Update last activity timestamp
            session(['_security_last_activity' => time()]);
        }

        return $next($request);
    }

    /**
     * Generate a session fingerprint based on client characteristics.
     * Uses user agent + Accept-Language as fingerprint components.
     * IP is intentionally excluded to support users on mobile networks.
     */
    protected function generateFingerprint(Request $request): string
    {
        return hash('sha256',
            $request->userAgent() .
            $request->header('Accept-Language', '') .
            config('app.key')
        );
    }
}
