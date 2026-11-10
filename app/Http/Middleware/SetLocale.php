<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's chosen interface language (stored in the session by
 * LocaleSwitchController) to every web request, including Livewire update requests,
 * which run through the same `web` middleware group. Only interface text is translated —
 * customer data (names, invitation text, product data) always prints as stored.
 */
class SetLocale
{
    public const DEFAULT = 'km';

    public const SUPPORTED = ['km', 'en'];

    public const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->hasSession() ? $request->session()->get(self::SESSION_KEY) : null;

        App::setLocale(self::isSupported($locale) ? $locale : self::DEFAULT);

        return $next($request);
    }

    public static function isSupported(mixed $locale): bool
    {
        return is_string($locale) && in_array($locale, self::SUPPORTED, true);
    }
}
