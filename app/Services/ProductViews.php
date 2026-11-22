<?php

namespace App\Services;

use App\Support\Analytics\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Counts product interest for the admin dashboard: a visit to a product's shop page (`page`) and a demo opened
 * (`demo`). One row per visitor + product + kind + local day (unique index; repeats are ignored).
 *
 * Not counted: bots / link previews, logged-in admins, and requests without a user agent. The visitor is a
 * salted SHA-256 of IP + user agent — no raw IP or personal data is stored. Never throws: a failed write must
 * never break the page a customer is looking at.
 */
class ProductViews
{
    public const PAGE = 'page';

    public const DEMO = 'demo';

    /** User agents that aren't people (search engines, Telegram / Facebook link previews, monitors…). */
    protected const BOTS = 'bot|crawl|spider|slurp|preview|facebookexternalhit|telegram|whatsapp|discord|skype|curl|wget|python|headless|lighthouse|monitor|pingdom|uptime';

    public function record(?int $serviceId, string $kind, ?Request $request = null): void
    {
        $request ??= request();

        if (! $serviceId || ! $this->isHuman($request)) {
            return;
        }

        try {
            DB::table('product_views')->insertOrIgnore([
                'service_id' => $serviceId,
                'kind' => $kind,
                'visitor' => hash('sha256', config('app.key').'|'.$request->ip().'|'.$request->userAgent()),
                'viewed_on' => now(Period::timezone())->toDateString(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected function isHuman(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        if ($agent === '' || preg_match('/'.self::BOTS.'/i', $agent)) {
            return false;
        }

        // the owner / staff browsing their own shop shouldn't count
        if (auth('web')->check()) {
            return false;
        }

        // browser prefetch (link hover) isn't a real visit
        return ! Str::contains(strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose'))), 'prefetch');
    }
}
