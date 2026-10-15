<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers Laravel doesn't set by default. Deliberately does NOT set
 * enforcing Content-Security-Policy — this app loads Google Fonts, the Google Translate widget,
 * Three.js, and various inline Alpine/Livewire bindings, and a CSP tight enough to matter
 * needs to be built and tested against that exact set of sources rather than added
 * blind, or it risks silently breaking the page instead of protecting it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('Content-Security-Policy-Report-Only', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://translate.google.com https://translate.googleapis.com https://www.youtube.com https://www.gstatic.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://www.gstatic.com",
            "font-src 'self' data: https://fonts.gstatic.com",
            "img-src 'self' data: blob: https:",
            "media-src 'self' https:",
            "frame-src https://www.youtube.com https://www.google.com",
            "connect-src 'self' https:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ]));

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
