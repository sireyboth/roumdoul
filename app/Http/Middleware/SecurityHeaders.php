<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers Laravel doesn't set by default. Deliberately does NOT set
 * Content-Security-Policy — this app loads Google Fonts, the Google Translate widget,
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

        if (app()->environment('production') && $request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
