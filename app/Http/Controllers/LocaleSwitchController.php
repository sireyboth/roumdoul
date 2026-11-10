<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Saves the visitor's interface language in the session and sends them back where they were.
 * Also expires the `googtrans` cookies left behind by the old Google Translate widget, which
 * otherwise kept some visitors stuck in machine-translated English.
 */
class LocaleSwitchController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(SetLocale::isSupported($locale), 404);

        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        $response = redirect()->to($this->safeBackUrl($request));

        foreach ($this->googtransDomains($request->getHost()) as $domain) {
            $response->withCookie(Cookie::forget('googtrans', '/', $domain));
        }

        return $response;
    }

    /** Back to the referring page, but only if it is on this site. */
    private function safeBackUrl(Request $request): string
    {
        $previous = (string) $request->headers->get('referer', '');

        if ($previous !== '' && parse_url($previous, PHP_URL_HOST) === $request->getHost()) {
            return $previous;
        }

        return url('/');
    }

    /**
     * Every domain the widget may have written the cookie for: host-only (null), the host
     * itself and each parent domain, with and without a leading dot — e.g. for
     * www.roumdoul.com: www.roumdoul.com, .www.roumdoul.com, roumdoul.com, .roumdoul.com.
     *
     * @return list<string|null>
     */
    private function googtransDomains(string $host): array
    {
        $domains = [null];
        $labels = explode('.', $host);
        // Stop before the bare TLD ("com"), but keep single-label hosts such as "localhost".
        $last = max(1, count($labels) - 1);

        for ($i = 0; $i < $last; $i++) {
            $domain = implode('.', array_slice($labels, $i));
            $domains[] = $domain;
            $domains[] = '.'.$domain;
        }

        return $domains;
    }
}
