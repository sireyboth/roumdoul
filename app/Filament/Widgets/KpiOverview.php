<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Support\Analytics\BusinessMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** The headline numbers for the period, each with its change vs the comparison period. */
class KpiOverview extends StatsOverviewWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '120s';

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return ['default' => 1, 'sm' => 2, 'xl' => 4];
    }

    protected function getStats(): array
    {
        $m = $this->metrics();
        $now = $m->summary();
        $was = $m->previousSummary();
        $vs = $this->period()->compareLabel();
        $revenueSeries = array_values($m->revenueSeries());
        $ordersSeries = array_values($m->ordersSeries());

        return [
            $this->stat('Revenue', '$'.number_format($now['revenue'], 2), $now['revenue'], $was['revenue'] ?? null, $vs,
                'Add-ons $'.number_format($now['addon_revenue'], 2).($now['discounts'] > 0 ? ' · discounts −$'.number_format($now['discounts'], 2) : ''))
                ->icon('heroicon-o-banknotes')->chart($revenueSeries),

            $this->stat('Paid orders', number_format($now['paid_orders']), $now['paid_orders'], $was['paid_orders'] ?? null, $vs,
                $now['placed_orders'].' placed in this period')
                ->icon('heroicon-o-shopping-bag')->chart($ordersSeries),

            $this->stat('Avg. order value', '$'.number_format($now['avg_order'], 2), $now['avg_order'], $was['avg_order'] ?? null, $vs,
                'Revenue ÷ paid orders')
                ->icon('heroicon-o-calculator'),

            $this->stat('Shop visitors', number_format($now['visitors']), $now['visitors'], $was['visitors'] ?? null, $vs,
                ($now['view_conversion'] === null ? 'No visits tracked yet' : $now['view_conversion'].'% ordered')
                .' · '.number_format($now['demo_views']).' demos · '.($now['conversion'] ?? 0).'% of orders paid')
                ->icon('heroicon-o-eye'),

            $this->stat('Customers who bought', number_format($now['buyers']), $now['buyers'], $was['buyers'] ?? null, $vs,
                $now['returning_buyers'].' returning · '.$now['new_customers'].' new '.str('account')->plural($now['new_customers']))
                ->icon('heroicon-o-users'),

            $this->stat('Letters sent', number_format($now['letters_sent']), $now['letters_sent'], $was['letters_sent'] ?? null, $vs,
                $now['invitations_sold'].' '.str('invitation')->plural($now['invitations_sold']).' sold')
                ->icon('heroicon-o-paper-airplane'),

            $this->stat('Letters opened', $now['open_rate'] === null ? '—' : $now['open_rate'].'%', $now['open_rate'], $was['open_rate'] ?? null, $vs,
                number_format($now['letters_opened']).' opened · '.number_format($now['rsvp_yes']).' attending', points: true)
                ->icon('heroicon-o-envelope-open'),

            $this->stat('Rating', $now['rating'] === null ? '—' : $now['rating'].' / 5', $now['rating'], $was['rating'] ?? null, $vs,
                $now['reviews'].' new '.str('review')->plural($now['reviews']), points: true)
                ->icon('heroicon-o-star'),
        ];
    }

    /** A stat whose description shows the change (green up / red down) or, without a comparison, $fallback. */
    protected function stat(string $label, string $value, float|int|null $current, float|int|null $previous, string $vs, string $fallback, bool $points = false): Stat
    {
        $stat = Stat::make($label, $value);

        if ($previous === null || $current === null) {
            return $stat->description($fallback)->color('gray');
        }

        $diff = $points ? round($current - $previous, 1) : BusinessMetrics::change($current, $previous);

        if ($diff === null) {
            return $stat->description('New · '.$fallback)->descriptionIcon('heroicon-m-sparkles')->color('success');
        }

        $up = $diff >= 0;
        $text = ($up ? '+' : '').$diff.($points ? ' pts' : '%').' '.$vs;

        return $stat
            ->description($text.' · '.$fallback)
            ->descriptionIcon($diff == 0 ? 'heroicon-m-minus' : ($up ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'))
            ->color($diff == 0 ? 'gray' : ($up ? 'success' : 'danger'));
    }
}
