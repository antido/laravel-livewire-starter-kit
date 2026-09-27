<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers and a nonce-based Content-Security-Policy (config/security.php).
 *
 * The nonce is created before the page renders, so @vite, the inline theme script and Livewire's own
 * scripts all carry it. Two documented exceptions:
 *  - script-src 'unsafe-eval': Alpine (bundled with Livewire) evaluates x-on / x-data expressions with
 *    the Function constructor. Livewire's CSP-safe build does not support the inline expressions the views use.
 *  - style-src 'unsafe-inline': Alpine and Livewire set inline styles (x-show, transitions, wire:loading).
 *    Inline styles cannot run code; scripts stay nonce-only.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.headers')) {
            return $next($request);
        }

        // One nonce per request, even if this middleware runs twice (Laravel renders some error pages
        // through a second pass). It is kept on the request, never shared between requests.
        $nonce = $request->attributes->get('csp_nonce');

        if (! is_string($nonce)) {
            $nonce = Vite::useCspNonce();
            $request->attributes->set('csp_nonce', $nonce);
        } else {
            Vite::useCspNonce($nonce);
        }

        $response = $next($request);

        $headers = [
            'Content-Security-Policy' => $this->policy($nonce),
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if (config('security.hsts') && $request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age='.(int) config('security.hsts_max_age').'; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
