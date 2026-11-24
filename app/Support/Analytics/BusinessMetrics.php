<?php

namespace App\Support\Analytics;

use App\Models\Customer;
use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Services\InvitationAddons;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every number on the admin dashboard, for one Period. Kept in one place so the cards, charts, product
 * table, comparison and suggestions always agree.
 *
 * Definitions:
 * - a sale = an order marked paid (status paid / fulfilled); its date is when it was paid (paid_at, else created_at)
 * - revenue = the paid orders' totals (after discounts); add-on revenue = paid add-on orders
 * - buyer = a customer account, or the order email for guest checkouts
 * - letter = one guest link (InvitationRecipient); opened = the guest opened it at least once
 * - visitor = a person who opened a product page or demo (ProductViews: one per person per product per day);
 *   view → order = new paid orders ÷ visitors (add-ons don't count — they don't come from the shop page)
 */
class BusinessMetrics
{
    public const PAID = ['paid', 'fulfilled'];

    public const MIX_LABELS = [
        'purchase' => 'New invitations & services',
        InvitationAddons::GUESTS => 'Add-on: more guests',
        InvitationAddons::PHOTOS => 'Add-on: more photos',
        InvitationAddons::FEATURE => 'Add-on: unlock a feature',
        InvitationAddons::UPGRADE => 'Add-on: upgrade plan',
        InvitationAddons::EXTEND => 'Add-on: extend',
    ];

    /** @var array<string, mixed> per-request memo */
    private array $memo = [];

    public function __construct(public readonly Period $period) {}

    public static function for(Period $period): self
    {
        return new self($period);
    }

    private function memo(string $key, \Closure $fn): mixed
    {
        return $this->memo[$key] ??= $fn();
    }

    /** Paid orders whose payment date falls in $period. */
    public static function paidOrders(Period $period): Builder
    {
        return Order::query()
            ->whereIn('status', self::PAID)
            ->whereRaw('COALESCE(paid_at, created_at) BETWEEN ? AND ?', [$period->start, $period->end]);
    }

    /** @return Collection<int, object{id:int,total:float,type:?string,customer_id:?int,customer_email:?string,discount_amount:?float,sold_at:string}> */
    private function sales(Period $period): Collection
    {
        return $this->memo('sales:'.$period->start.$period->end, fn () => self::paidOrders($period)
            ->get(['id', 'total', 'type', 'customer_id', 'customer_email', 'customer_name', 'customer_phone', 'discount_amount', 'paid_at', 'created_at'])
            ->toBase()
            ->map(fn (Order $o) => (object) [
                'id' => $o->id,
                'total' => (float) $o->total,
                'type' => $o->type,
                'buyer' => self::buyerKey($o->customer_id, $o->customer_email),
                'name' => $o->customer_name,
                'contact' => $o->customer_phone ?: $o->customer_email,
                'customer_id' => $o->customer_id,
                'discount' => (float) $o->discount_amount,
                'sold_at' => CarbonImmutable::parse($o->paid_at ?? $o->created_at),
            ]));
    }

    public static function buyerKey(?int $customerId, ?string $email): string
    {
        return $customerId ? 'c'.$customerId : 'e'.mb_strtolower(trim((string) $email));
    }

    // ───────────────────────────── headline numbers ─────────────────────────────

    /** @return array<string, float|int|null> */
    public function summary(?Period $period = null): array
    {
        $period ??= $this->period;

        return $this->memo('summary:'.$period->start.$period->end, function () use ($period) {
            $sales = $this->sales($period);
            $revenue = $sales->sum('total');
            $paid = $sales->count();
            $placed = Order::whereBetween('created_at', [$period->start, $period->end])->count();
            $placedPaid = Order::whereBetween('created_at', [$period->start, $period->end])->whereIn('status', self::PAID)->count();

            $buyers = $sales->pluck('buyer')->unique();
            $returning = $buyers->isEmpty() ? 0 : $this->buyersBefore($period->start)->intersect($buyers)->count();

            $views = DB::table('product_views')->whereBetween('created_at', [$period->start, $period->end]);
            $visitors = (clone $views)->distinct()->count('visitor');
            $newOrders = $sales->where('type', '!=', Order::TYPE_ADDON)->count();

            $letters = InvitationRecipient::whereBetween('created_at', [$period->start, $period->end]);
            $lettersSent = (clone $letters)->count();
            $opened = (clone $letters)->whereNotNull('viewed_at')->count();

            return [
                'revenue' => round($revenue, 2),
                'addon_revenue' => round($sales->where('type', Order::TYPE_ADDON)->sum('total'), 2),
                'paid_orders' => $paid,
                'placed_orders' => $placed,
                'conversion' => $placed > 0 ? round($placedPaid / $placed * 100, 1) : null,
                'avg_order' => $paid > 0 ? round($revenue / $paid, 2) : 0,
                'discounts' => round($sales->sum('discount'), 2),
                'buyers' => $buyers->count(),
                'returning_buyers' => $returning,
                'new_customers' => Customer::whereBetween('created_at', [$period->start, $period->end])->count(),
                'invitations_sold' => Invitation::whereBetween('created_at', [$period->start, $period->end])->count(),
                'letters_sent' => $lettersSent,
                'letters_opened' => $opened,
                'open_rate' => $lettersSent > 0 ? round($opened / $lettersSent * 100, 1) : null,
                'rsvp_yes' => (clone $letters)->where('rsvp_status', 'yes')->count(),
                'rsvp_no' => (clone $letters)->where('rsvp_status', 'no')->count(),
                'visitors' => $visitors,
                'page_views' => (clone $views)->where('kind', 'page')->count(),
                'demo_views' => (clone $views)->where('kind', 'demo')->count(),
                'view_conversion' => $visitors > 0 ? round(min($newOrders, $visitors) / $visitors * 100, 1) : null,
                'rating' => ($r = Review::whereBetween('created_at', [$period->start, $period->end])->avg('rating')) ? round((float) $r, 1) : null,
                'reviews' => Review::whereBetween('created_at', [$period->start, $period->end])->count(),
            ];
        });
    }

    /** The comparison period's summary, or null for «No comparison». */
    public function previousSummary(): ?array
    {
        return ($previous = $this->period->previous()) ? $this->summary($previous) : null;
    }

    /** % change, or null when there is nothing to compare with. */
    public static function change(float|int|null $current, float|int|null $previous): ?float
    {
        if ($current === null || $previous === null) {
            return null;
        }
        if ((float) $previous === 0.0) {
            return (float) $current === 0.0 ? 0.0 : null;
        }

        return round(($current - $previous) / abs($previous) * 100, 1);
    }

    /** Buyers with a paid order before $date. */
    private function buyersBefore(CarbonImmutable $date): Collection
    {
        return $this->memo('before:'.$date, fn () => Order::whereIn('status', self::PAID)
            ->whereRaw('COALESCE(paid_at, created_at) < ?', [$date])
            ->get(['customer_id', 'customer_email'])
            ->toBase()
            ->map(fn ($o) => self::buyerKey($o->customer_id, $o->customer_email))
            ->unique()
            ->values());
    }

    // ───────────────────────────── time series ─────────────────────────────

    /** @return array<string, float> bucket => revenue (for any period, bucketed like $this->period) */
    public function revenueSeries(?Period $period = null): array
    {
        $period ??= $this->period;
        $series = array_fill_keys(array_keys($period->buckets()), 0.0);

        foreach ($this->sales($period) as $sale) {
            $key = $period->bucketKey($sale->sold_at);
            if (array_key_exists($key, $series)) {
                $series[$key] += $sale->total;
            }
        }

        return array_map(fn ($v) => round($v, 2), $series);
    }

    /** @return array<string, int> bucket => paid orders */
    public function ordersSeries(): array
    {
        $series = array_fill_keys(array_keys($this->period->buckets()), 0);
        foreach ($this->sales($this->period) as $sale) {
            $key = $this->period->bucketKey($sale->sold_at);
            if (array_key_exists($key, $series)) {
                $series[$key]++;
            }
        }

        return $series;
    }

    /** @return array{new: array<string,int>, returning: array<string,int>} buyers per bucket, first-time vs came back */
    public function buyersSeries(): array
    {
        $buckets = array_keys($this->period->buckets());
        $new = array_fill_keys($buckets, 0);
        $returning = array_fill_keys($buckets, 0);
        $seen = $this->buyersBefore($this->period->start)->flip();

        foreach ($this->sales($this->period)->sortBy('sold_at') as $sale) {
            $key = $this->period->bucketKey($sale->sold_at);
            if (! array_key_exists($key, $new)) {
                continue;
            }
            if (isset($seen[$sale->buyer])) {
                $returning[$key]++;
            } else {
                $new[$key]++;
                $seen[$sale->buyer] = true;
            }
        }

        return ['new' => $new, 'returning' => $returning];
    }

    /** @return array<string, float> mix key (purchase / add-on type) => revenue */
    public function salesMix(): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::PAID)
            ->whereRaw('COALESCE(orders.paid_at, orders.created_at) BETWEEN ? AND ?', [$this->period->start, $this->period->end])
            ->selectRaw("COALESCE(order_items.addon_type, 'purchase') as kind, SUM(order_items.line_total) as revenue")
            ->groupBy('kind')
            ->pluck('revenue', 'kind');

        return collect(self::MIX_LABELS)
            ->map(fn ($label, $key) => round((float) ($rows[$key] ?? 0), 2))
            ->filter(fn ($v) => $v > 0)
            ->all();
    }

    /** @return array{weekday: array<string,int>, hour: array<string,int>} paid orders by weekday / hour of day */
    public function peakTimes(): array
    {
        $weekday = array_fill_keys(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], 0);
        $hour = [];
        foreach (range(0, 23) as $h) {
            $hour[sprintf('%02d:00', $h)] = 0;
        }

        // when customers ORDER (placed), not when the admin confirms payment
        Order::whereBetween('created_at', [$this->period->start, $this->period->end])
            ->pluck('created_at')
            ->each(function ($at) use (&$weekday, &$hour) {
                $at = CarbonImmutable::parse($at, 'UTC')->setTimezone(Period::timezone());
                $weekday[$at->format('D')]++;
                $hour[$at->format('H').':00']++;
            });

        return ['weekday' => $weekday, 'hour' => $hour];
    }

    /** @return array<string, int> order status => orders placed in the period */
    public function statusCounts(): array
    {
        return Order::whereBetween('created_at', [$this->period->start, $this->period->end])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    // ───────────────────────────── invitations ─────────────────────────────

    /** Invitations sold in the period → filled in → sent to guests; their letters → opened → replied. */
    public function funnel(): array
    {
        return $this->memo('funnel', function () {
            $invitations = Invitation::whereBetween('created_at', [$this->period->start, $this->period->end])
                ->withCount('recipients')
                ->get(['id', 'field_values']);

            $filled = $invitations->filter(fn (Invitation $i) => collect($i->field_values ?? [])->filter(fn ($v) => filled($v))->isNotEmpty());
            $ids = $invitations->pluck('id');

            $letters = InvitationRecipient::whereIn('invitation_id', $ids);

            return [
                'sold' => $invitations->count(),
                'filled' => $filled->count(),
                'with_guests' => $invitations->where('recipients_count', '>', 0)->count(),
                'letters' => (clone $letters)->count(),
                'opened' => (clone $letters)->whereNotNull('viewed_at')->count(),
                'replied' => (clone $letters)->whereIn('rsvp_status', ['yes', 'no'])->count(),
                'attending' => (clone $letters)->where('rsvp_status', 'yes')->count(),
            ];
        });
    }

    // ───────────────────────────── products ─────────────────────────────

    /**
     * One row per product (Service) for the period: sales, buyers, invitations, letters, change vs the
     * comparison period. Products with no activity are included (so «never sells» shows up) unless $onlyIds.
     *
     * @param  list<int>|null  $onlyIds
     * @return Collection<int, array<string, mixed>>
     */
    public function products(?array $onlyIds = null): Collection
    {
        return $this->memo('products:'.json_encode($onlyIds), function () use ($onlyIds) {
            $current = $this->productSales($this->period);
            $previous = ($p = $this->period->previous()) ? $this->productSales($p) : collect();
            $engagement = $this->productEngagement();
            $views = $this->productViews();
            $total = max(0.01, $current->sum('revenue'));
            $lastSold = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->whereIn('orders.status', self::PAID)
                ->selectRaw('order_items.service_id, MAX(COALESCE(orders.paid_at, orders.created_at)) as last_sold')
                ->groupBy('order_items.service_id')
                ->pluck('last_sold', 'service_id');

            return Service::query()
                ->with('category:id,name_en')
                ->withCount('invitationTemplates')
                ->when($onlyIds !== null, fn ($q) => $q->whereIn('id', $onlyIds))
                ->get(['id', 'name_en', 'category_id', 'is_active', 'is_featured', 'in_stock', 'image_path', 'created_at'])
                ->map(function (Service $s) use ($current, $previous, $engagement, $views, $total, $lastSold) {
                    $c = $current[$s->id] ?? null;
                    $e = $engagement[$s->id] ?? null;
                    $v = $views[$s->id] ?? null;
                    $visitors = (int) ($v->visitors ?? 0);
                    $newOrders = (int) ($c['new_orders'] ?? 0);
                    $revenue = (float) ($c['revenue'] ?? 0);
                    $prev = $previous[$s->id]['revenue'] ?? 0;

                    return [
                        'id' => $s->id,
                        'name' => $s->name_en,
                        'category' => $s->category?->name_en,
                        'is_invitation' => $s->invitation_templates_count > 0,
                        'is_active' => (bool) $s->is_active,
                        'is_featured' => (bool) $s->is_featured,
                        'in_stock' => (bool) $s->in_stock,
                        'has_cover' => filled($s->image_path),
                        'created_at' => $s->created_at,
                        'revenue' => round($revenue, 2),
                        'addon_revenue' => round((float) ($c['addon_revenue'] ?? 0), 2),
                        'orders' => (int) ($c['orders'] ?? 0),
                        'units' => (int) ($c['units'] ?? 0),
                        'buyers' => (int) ($c['buyers'] ?? 0),
                        'avg_order' => ($c['orders'] ?? 0) > 0 ? round($revenue / $c['orders'], 2) : 0,
                        'share' => round($revenue / $total * 100, 1),
                        'change' => $this->period->hasComparison() ? self::change($revenue, $prev) : null,
                        'prev_revenue' => round((float) $prev, 2),
                        'invitations' => (int) ($e['invitations'] ?? 0),
                        'letters' => (int) ($e['letters'] ?? 0),
                        'opened' => (int) ($e['opened'] ?? 0),
                        'open_rate' => ($e['letters'] ?? 0) > 0 ? round($e['opened'] / $e['letters'] * 100, 1) : null,
                        'attending' => (int) ($e['attending'] ?? 0),
                        'views' => (int) ($v->page_views ?? 0),
                        'demo_views' => (int) ($v->demo_views ?? 0),
                        'visitors' => $visitors,
                        'conversion' => $visitors > 0 ? round(min($newOrders, $visitors) / $visitors * 100, 1) : null,
                        'last_sold' => isset($lastSold[$s->id]) ? CarbonImmutable::parse($lastSold[$s->id]) : null,
                    ];
                })
                ->sortByDesc('revenue')
                ->values();
        });
    }

    /** @return Collection<int, array{revenue: float, addon_revenue: float, orders: int, units: int, buyers: int}> keyed by service id */
    private function productSales(Period $period): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('orders.status', self::PAID)
            ->whereRaw('COALESCE(orders.paid_at, orders.created_at) BETWEEN ? AND ?', [$period->start, $period->end])
            ->whereNotNull('order_items.service_id')
            ->get(['order_items.service_id', 'order_items.line_total', 'order_items.quantity', 'order_items.addon_type', 'orders.id as order_id', 'orders.customer_id', 'orders.customer_email'])
            ->groupBy('service_id')
            ->map(fn (Collection $rows) => [
                'revenue' => (float) $rows->sum('line_total'),
                'addon_revenue' => (float) $rows->whereNotNull('addon_type')->sum('line_total'),
                'orders' => $rows->pluck('order_id')->unique()->count(),
                'new_orders' => $rows->whereNull('addon_type')->pluck('order_id')->unique()->count(),
                'units' => (int) $rows->whereNull('addon_type')->sum('quantity'),
                'buyers' => $rows->map(fn ($r) => self::buyerKey($r->customer_id, $r->customer_email))->unique()->count(),
            ]);
    }

    /** Product page views, demo views and distinct visitors in the period, per product. */
    private function productViews(): Collection
    {
        return DB::table('product_views')
            ->whereBetween('created_at', [$this->period->start, $this->period->end])
            ->selectRaw("service_id,
                SUM(CASE WHEN kind = 'page' THEN 1 ELSE 0 END) as page_views,
                SUM(CASE WHEN kind = 'demo' THEN 1 ELSE 0 END) as demo_views,
                COUNT(DISTINCT visitor) as visitors")
            ->groupBy('service_id')
            ->get()
            ->keyBy('service_id');
    }

    /** Invitations sold and letters sent in the period, per product (through the template's service). */
    private function productEngagement(): Collection
    {
        $invitations = DB::table('invitations')
            ->join('invitation_templates', 'invitation_templates.id', '=', 'invitations.invitation_template_id')
            ->whereBetween('invitations.created_at', [$this->period->start, $this->period->end])
            ->whereNull('invitations.deleted_at')
            ->selectRaw('invitation_templates.service_id, COUNT(*) as n')
            ->groupBy('invitation_templates.service_id')
            ->pluck('n', 'service_id');

        $letters = DB::table('invitation_recipients')
            ->join('invitations', 'invitations.id', '=', 'invitation_recipients.invitation_id')
            ->join('invitation_templates', 'invitation_templates.id', '=', 'invitations.invitation_template_id')
            ->whereBetween('invitation_recipients.created_at', [$this->period->start, $this->period->end])
            ->selectRaw("invitation_templates.service_id,
                COUNT(*) as letters,
                SUM(CASE WHEN invitation_recipients.viewed_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN invitation_recipients.rsvp_status = 'yes' THEN 1 ELSE 0 END) as attending")
            ->groupBy('invitation_templates.service_id')
            ->get()
            ->keyBy('service_id');

        return collect($invitations->keys())->merge($letters->keys())->unique()->filter()
            ->mapWithKeys(fn ($id) => [$id => [
                'invitations' => (int) ($invitations[$id] ?? 0),
                'letters' => (int) ($letters[$id]->letters ?? 0),
                'opened' => (int) ($letters[$id]->opened ?? 0),
                'attending' => (int) ($letters[$id]->attending ?? 0),
            ]]);
    }

    // ───────────────────────────── customers ─────────────────────────────

    /** @return Collection<int, array<string, mixed>> top buyers in the period by spend */
    public function topBuyers(int $limit = 10): Collection
    {
        $lifetime = Order::whereIn('status', self::PAID)
            ->get(['customer_id', 'customer_email', 'total'])
            ->groupBy(fn ($o) => self::buyerKey($o->customer_id, $o->customer_email))
            ->map(fn ($orders) => ['orders' => $orders->count(), 'spent' => (float) $orders->sum('total')]);

        $letters = DB::table('invitation_recipients')
            ->join('invitations', 'invitations.id', '=', 'invitation_recipients.invitation_id')
            ->whereNotNull('invitations.customer_id')
            ->selectRaw('invitations.customer_id, COUNT(*) as n')
            ->groupBy('invitations.customer_id')
            ->pluck('n', 'customer_id');

        return $this->sales($this->period)
            ->groupBy('buyer')
            ->map(function (Collection $sales, string $key) use ($lifetime, $letters) {
                $last = $sales->sortByDesc('sold_at')->first();

                return [
                    'key' => $key,
                    'name' => $last->name ?: 'Guest',
                    'contact' => $last->contact,
                    'customer_id' => $last->customer_id,
                    'orders' => $sales->count(),
                    'spent' => round($sales->sum('total'), 2),
                    'lifetime_orders' => $lifetime[$key]['orders'] ?? $sales->count(),
                    'lifetime_spent' => round($lifetime[$key]['spent'] ?? $sales->sum('total'), 2),
                    'letters' => $last->customer_id ? (int) ($letters[$last->customer_id] ?? 0) : 0,
                    'last_order' => $last->sold_at,
                ];
            })
            ->sortByDesc('spent')
            ->take($limit)
            ->values();
    }
}
