<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * SECURITY LAYER 1: HTTP Security Headers
 * 
 * Injects comprehensive security headers into every HTTP response
 * to mitigate XSS, clickjacking, MIME sniffing, and data leakage attacks.
 */
class SecurityHeaders
{
    /**
     * Security headers applied to all responses.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent clickjacking by disallowing framing
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Block MIME type sniffing (prevents browser from guessing content types)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable browser XSS protection filter
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy: only send origin on cross-origin, full URL on same-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Prevent browsers from caching sensitive pages
        if ($request->is('patient/*', 'staff/*', 'dentist/*', 'settings', 'login', 'register')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // Permissions Policy: disable unnecessary browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Strict Transport Security (HSTS) — enforced in production
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Remove server identification headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
