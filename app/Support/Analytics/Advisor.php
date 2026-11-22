<?php

namespace App\Support\Analytics;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\PromoCodes\PromoCodeResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\ContactInquiry;
use App\Models\Invitation;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\Review;
use App\Models\ServicePlan;
use Carbon\Carbon;
use Throwable;

/**
 * The dashboard's «Action center»: what needs doing now (todos) and ideas to grow, worked out from the data.
 * Each item: ['tone' => danger|warning|success|info, 'icon', 'title', 'body', 'url' => ?, 'action' => ?].
 * Rules are plain thresholds a solo owner can read — tune them here.
 */
class Advisor
{
    public function __construct(private BusinessMetrics $metrics) {}

    /** @return list<array<string, mixed>> */
    public function todos(): array
    {
        $items = [];

        // payments waiting for confirmation
        $pending = Order::where('status', 'pending_payment');
        $pendingCount = (clone $pending)->count();
        if ($pendingCount > 0) {
            $oldest = (clone $pending)->oldest()->value('created_at');
            $stale = (clone $pending)->where('created_at', '<', now()->subDays(3))->count();
            $items[] = $this->item(
                $stale > 0 ? 'danger' : 'warning',
                'heroicon-o-banknotes',
                "{$pendingCount} ".str('order')->plural($pendingCount).' waiting for payment — $'.number_format((float) (clone $pending)->sum('total'), 2),
                'Check ABA / Wing and mark them paid. Oldest: '.Carbon::parse($oldest)->diffForHumans().'.'
                    .($stale > 0 ? " {$stale} older than 3 days — follow up on Telegram or cancel to keep the list clean." : ''),
                $this->url(fn () => OrderResource::getUrl('index', ['filters' => ['status' => ['value' => 'pending_payment']]])),
                'Open orders',
            );
        }

        // paid but not delivered (non-invitation services need a manual hand-off)
        $toFulfil = Order::where('status', 'paid')->where('type', Order::TYPE_PURCHASE)->whereDoesntHave('invitations')->count();
        if ($toFulfil > 0) {
            $items[] = $this->item('warning', 'heroicon-o-truck', "{$toFulfil} paid ".str('order')->plural($toFulfil).' to deliver',
                'Send the account / code, then set the order to Fulfilled.',
                $this->url(fn () => OrderResource::getUrl('index', ['filters' => ['status' => ['value' => 'paid']]])), 'Open orders');
        }

        // reviews
        $flagged = Review::whereNotNull('reported_at')->count();
        $waiting = Review::where('is_approved', false)->whereNull('reported_at')->count();
        if ($flagged + $waiting > 0) {
            $items[] = $this->item($flagged ? 'danger' : 'info', 'heroicon-o-star',
                trim(($waiting ? "{$waiting} ".str('review')->plural($waiting).' to approve' : '').($flagged && $waiting ? ' · ' : '').($flagged ? "{$flagged} flagged" : '')),
                'Approve the good ones so they show on the homepage; check flagged ones.',
                $this->url(fn () => ReviewResource::getUrl('index')), 'Open reviews');
        }

        // customers who paid but never added a guest (they may need help)
        $stuck = Invitation::whereHas('order', fn ($q) => $q->whereIn('status', BusinessMetrics::PAID))
            ->where('created_at', '<', now()->subDays(2))
            ->where('created_at', '>', now()->subDays(60))
            ->whereDoesntHave('recipients')
            ->count();
        if ($stuck > 0) {
            $items[] = $this->item('info', 'heroicon-o-lifebuoy', "{$stuck} paid ".str('invitation')->plural($stuck).' with no guests yet',
                'Paid 2+ days ago but no guest link made. Send a short «need help?» message — happy customers come back.',
                $this->url(fn () => CustomerResource::getUrl('index')), 'Open customers');
        }

        // invitations expiring soon (customers got the reminder email — a personal nudge sells «extend»)
        $expiring = Invitation::whereBetween('expires_at', [now(), now()->addDays(7)])->count();
        if ($expiring > 0) {
            $items[] = $this->item('info', 'heroicon-o-clock', "{$expiring} ".str('invitation')->plural($expiring).' expire in the next 7 days',
                'They got an email reminder. A quick Telegram message offering «extend» often turns into a sale.',
                $this->url(fn () => InvitationTemplateResource::getUrl('index')), 'Templates');
        }

        // messages from the contact form
        $inquiries = ContactInquiry::where('created_at', '>=', now()->subDays(7))->count();
        if ($inquiries > 0) {
            $items[] = $this->item('info', 'heroicon-o-chat-bubble-left-right', "{$inquiries} contact ".str('message')->plural($inquiries).' this week',
                'Reply within a day — fast replies win orders.', null, null);
        }

        // promo codes running out
        $promos = PromoCode::where('is_active', true)
            ->where(fn ($q) => $q->whereBetween('expires_at', [now(), now()->addDays(7)])
                ->orWhereColumn('times_used', '>=', 'usage_limit'))
            ->pluck('code');
        if ($promos->isNotEmpty()) {
            $items[] = $this->item('info', 'heroicon-o-ticket', 'Promo '.str('code')->plural($promos->count()).' ending: '.$promos->take(3)->implode(', '),
                'Expiring this week or used up. Extend it or plan the next one.',
                $this->url(fn () => PromoCodeResource::getUrl('index')), 'Promo codes');
        }

        // products customers can't buy
        $outOfStock = ServicePlan::where('in_stock', false)->whereHas('service', fn ($q) => $q->where('is_active', true))->count();
        if ($outOfStock > 0) {
            $items[] = $this->item('warning', 'heroicon-o-archive-box-x-mark', "{$outOfStock} ".str('plan')->plural($outOfStock).' out of stock',
                'Customers can\'t buy these. Restock or hide them.', $this->url(fn () => ServiceResource::getUrl('index')), 'Services');
        }

        return $items;
    }

    /** @return list<array<string, mixed>> */
    public function ideas(): array
    {
        $now = $this->metrics->summary();
        $before = $this->metrics->previousSummary();
        $products = $this->metrics->products();
        $items = [];

        // revenue trend
        if ($before && ($change = BusinessMetrics::change($now['revenue'], $before['revenue'])) !== null) {
            if ($change <= -20) {
                $items[] = $this->item('danger', 'heroicon-o-arrow-trending-down', "Revenue down {$change}% ".$this->metrics->period->compareLabel(),
                    'Post your best-selling template on Facebook / TikTok, or run a short promo code (e.g. 10% for 3 days).',
                    $this->url(fn () => PromoCodeResource::getUrl('index')), 'Create promo');
            } elseif ($change >= 20) {
                $items[] = $this->item('success', 'heroicon-o-arrow-trending-up', "Revenue up +{$change}% ".$this->metrics->period->compareLabel(),
                    'Nice! Whatever you did this period — keep doing it. Ask happy customers for a review.', null, null);
            }
        }

        // best seller not featured on the homepage
        $best = $products->firstWhere(fn ($p) => $p['revenue'] > 0);
        if ($best && ! $best['is_featured']) {
            $items[] = $this->item('info', 'heroicon-o-fire', "«{$best['name']}» is your best seller ({$best['share']}% of revenue)",
                'It isn\'t featured on the homepage yet. Feature it so more visitors see it first.',
                $this->url(fn () => $best['is_invitation'] ? InvitationTemplateResource::getUrl('index') : ServiceResource::getUrl('index')), 'Feature it');
        }

        // looked at a lot, bought rarely → the listing is the problem (price, photos, text)
        $leaky = $products->filter(fn ($p) => $p['visitors'] >= 30 && ($p['conversion'] ?? 0) < 2)->sortByDesc('visitors')->first();
        if ($leaky) {
            $items[] = $this->item('warning', 'heroicon-o-eye', "«{$leaky['name']}»: {$leaky['visitors']} visitors, only {$leaky['orders']} ".str('order')->plural($leaky['orders']),
                'People are interested but not buying. Check the price, cover photo, description and demo.', null, null);
        }

        // converts best → deserves more traffic
        $star = $products->filter(fn ($p) => $p['visitors'] >= 15 && ($p['conversion'] ?? 0) >= 8)->sortByDesc('conversion')->first();
        if ($star) {
            $items[] = $this->item('success', 'heroicon-o-rocket-launch', "«{$star['name']}» turns {$star['conversion']}% of visitors into orders",
                'Your best converter — send more people to it: post it, pin it, feature it.', null, null);
        }

        // products that never sell
        $sleeping = $products->filter(fn ($p) => $p['is_active']
            && ($p['created_at'] === null || $p['created_at']->lt(now()->subDays(30)))
            && ($p['last_sold'] === null || $p['last_sold']->lt(now()->subDays(60))));
        if ($sleeping->isNotEmpty()) {
            $items[] = $this->item('warning', 'heroicon-o-moon', $sleeping->count().' '.str('product')->plural($sleeping->count()).' with no sale in 60 days',
                $sleeping->take(3)->pluck('name')->implode(', ').($sleeping->count() > 3 ? '…' : '').'. Try new cover photos, a lower price, or hide them to keep the shop clean.',
                null, null);
        }

        // listings without a cover image
        $noCover = $products->filter(fn ($p) => $p['is_active'] && ! $p['has_cover'])->count();
        if ($noCover > 0) {
            $items[] = $this->item('info', 'heroicon-o-photo', "{$noCover} ".str('product')->plural($noCover).' without a cover image',
                'Products with a picture sell much better in the shop grid.', $this->url(fn () => ServiceResource::getUrl('index')), 'Services');
        }

        // guests not opening letters
        if ($now['letters_sent'] >= 10 && $now['open_rate'] !== null && $now['open_rate'] < 50) {
            $items[] = $this->item('warning', 'heroicon-o-envelope-open', "Only {$now['open_rate']}% of guest letters were opened",
                'Remind customers to send each link by Telegram / Messenger (not just copy it). A short how-to video helps.', null, null);
        }

        // loyal customers
        if ($now['returning_buyers'] > 0) {
            $items[] = $this->item('success', 'heroicon-o-heart', "{$now['returning_buyers']} returning ".str('customer')->plural($now['returning_buyers']).' this period',
                'Thank them with a personal loyalty code — returning customers cost nothing to win.', $this->url(fn () => PromoCodeResource::getUrl('index')), 'Create code');
        }

        // add-ons barely sold
        if ($now['revenue'] > 0 && $now['addon_revenue'] / max($now['revenue'], 0.01) < 0.05) {
            $noAddons = ServicePlan::whereNull('extra_guest_price')->whereNull('extra_photo_price')->whereNull('extend_price')
                ->whereHas('service.invitationTemplates')->count();
            $items[] = $this->item('info', 'heroicon-o-puzzle-piece', 'Add-ons are under 5% of revenue',
                ($noAddons ? "{$noAddons} invitation ".str('plan')->plural($noAddons).' sell no add-ons at all. ' : '')
                .'Extra guests, photos and «extend» are easy extra money — make sure each plan has prices.', null, null);
        }

        // when to post
        $peak = $this->metrics->peakTimes();
        if (array_sum($peak['weekday']) >= 10) {
            $day = array_search(max($peak['weekday']), $peak['weekday'], true);
            $hour = array_search(max($peak['hour']), $peak['hour'], true);
            $items[] = $this->item('info', 'heroicon-o-calendar-days', "Most orders come on {$day}, around {$hour}",
                'Post promos and new templates a little before then.', null, null);
        }

        // ratings
        $low = Review::where('rating', '<=', 3)->where('created_at', '>=', $this->metrics->period->start)->count();
        if ($low > 0) {
            $items[] = $this->item('warning', 'heroicon-o-face-frown', "{$low} low ".str('rating')->plural($low).' (3★ or less) this period',
                'Contact these customers — fixing a problem often turns them into fans.', $this->url(fn () => ReviewResource::getUrl('index')), 'Reviews');
        }

        if ($items === []) {
            $items[] = $this->item('success', 'heroicon-o-check-badge', 'All looks healthy',
                'No warnings for this period. Keep posting your templates and asking for reviews.', null, null);
        }

        return $items;
    }

    private function item(string $tone, string $icon, string $title, string $body, ?string $url, ?string $action): array
    {
        return compact('tone', 'icon', 'title', 'body', 'url', 'action');
    }

    private function url(\Closure $make): ?string
    {
        try {
            return $make();
        } catch (Throwable) {
            return null;
        }
    }
}
