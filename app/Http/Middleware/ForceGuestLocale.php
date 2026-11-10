<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guest-facing invitation pages (real links, the owner's preview and template demos) are
 * always Khmer, whatever language the site visitor picked for the storefront/dashboard.
 */
class ForceGuestLocale
{
    public const LOCALE = 'km';

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(self::LOCALE);

        return $next($request);
    }
}
