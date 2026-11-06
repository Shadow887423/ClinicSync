<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SECURITY LAYER 3: SQL Injection & Malicious Input Firewall
 * 
 * Scans all incoming request parameters for common SQL injection,
 * XSS, command injection, and path traversal attack patterns.
 * Blocks and logs malicious requests before they reach application logic.
 */
class InputFirewall
{
    /**
     * Dangerous patterns that indicate an attack attempt.
     */
    protected array $sqlInjectionPatterns = [
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bSELECT\b.*\bFROM\b.*\bWHERE\b)/i',
        '/(\bINSERT\b.*\bINTO\b)/i',
        '/(\bDELETE\b.*\bFROM\b)/i',
        '/(\bDROP\b\s+\b(TABLE|DATABASE|INDEX)\b)/i',
        '/(\bALTER\b\s+\bTABLE\b)/i',
        '/(\bEXEC\b\s*\()/i',
        '/(xp_cmdshell)/i',
        '/(LOAD_FILE|INTO\s+OUTFILE|INTO\s+DUMPFILE)/i',
        '/(BENCHMARK\s*\()/i',
        '/(SLEEP\s*\()/i',
        '/(WAITFOR\s+DELAY)/i',
        '/(0x[0-9a-fA-F]{8,})/i',       // Long hex encoding
        '/(CHAR\s*\(\s*\d+\s*(,\s*\d+\s*){3,}\))/i',  // CHAR() obfuscation
    ];

    protected array $xssPatterns = [
        '/<script[\s>]/i',
        '/javascript\s*:/i',
        '/on(error|load|click|mouseover|focus|blur)\s*=/i',
        '/<iframe/i',
        '/<object/i',
        '/<embed/i',
        '/data\s*:\s*text\/html/i',
        '/vbscript\s*:/i',
        '/expression\s*\(/i',
    ];

    protected array $pathTraversalPatterns = [
        '/\.\.\//i',
        '/\.\.\\\\/',
        '/%2e%2e/i',
        '/%252e/i',
        '/etc\/passwd/i',
        '/\/proc\//i',
        '/\/var\/log/i',
    ];

    protected array $commandInjectionPatterns = [
        '/;\s*(cat|ls|dir|whoami|id|uname|passwd|shadow|curl|wget)\b/i',
        '/\|\s*(cat|ls|dir|whoami|id)\b/i',
        '/`[^`]+`/',
        '/\$\([^)]+\)/',
    ];

    /**
     * Fields that should skip scanning (binary/encoded file uploads, CSRF tokens).
     */
    protected array $exemptFields = ['_token', 'password', 'password_confirmation', 'current_password'];

    public function handle(Request $request, Closure $next)
    {
        $allInput = $request->except($this->exemptFields);

        foreach ($allInput as $key => $value) {
            if (is_string($value) && $this->containsMaliciousPayload($value)) {
                Log::critical("[SECURITY FIREWALL] Malicious input blocked", [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'field' => $key,
                    'value_preview' => mb_substr($value, 0, 200),
                    'user_agent' => $request->userAgent(),
                    'user_id' => $request->user()?->id,
                ]);

                abort(403, 'Forbidden: Your request has been blocked by the security firewall.');
            }

            // Recursively check arrays (e.g., days[0][active])
            if (is_array($value)) {
                $this->scanArray($value, $key, $request);
            }
        }

        // Also scan query string parameters
        foreach ($request->query() as $key => $value) {
            if (is_string($value) && $this->containsMaliciousPayload($value)) {
                Log::critical("[SECURITY FIREWALL] Malicious query parameter blocked", [
                    'ip' => $request->ip(),
                    'path' => $request->fullUrl(),
                    'field' => $key,
                ]);

                abort(403, 'Forbidden: Suspicious query parameter detected.');
            }
        }

        return $next($request);
    }

    protected function scanArray(array $data, string $parentKey, Request $request): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && $this->containsMaliciousPayload($value)) {
                Log::critical("[SECURITY FIREWALL] Malicious nested input blocked", [
                    'ip' => $request->ip(),
                    'path' => $request->path(),
                    'field' => "{$parentKey}.{$key}",
                ]);

                abort(403, 'Forbidden: Your request has been blocked by the security firewall.');
            }

            if (is_array($value)) {
                $this->scanArray($value, "{$parentKey}.{$key}", $request);
            }
        }
    }

    protected function containsMaliciousPayload(string $value): bool
    {
        // Skip very short values (single chars, numbers)
        if (strlen($value) < 4) {
            return false;
        }

        $allPatterns = array_merge(
            $this->sqlInjectionPatterns,
            $this->xssPatterns,
            $this->pathTraversalPatterns,
            $this->commandInjectionPatterns
        );

        foreach ($allPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
