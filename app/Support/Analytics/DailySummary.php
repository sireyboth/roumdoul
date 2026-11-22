<?php

namespace App\Support\Analytics;

use App\Filament\Pages\Dashboard;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * The morning Telegram message (`php artisan report:daily`, scheduled in routes/console.php): yesterday's
 * numbers vs the day before, the month so far vs the same days last month, what needs doing, one idea, and a
 * link to the dashboard. Telegram HTML (only <b>, <i>, <a> are used).
 */
class DailySummary
{
    public function message(): string
    {
        $tz = Period::timezone();
        $yesterday = BusinessMetrics::for(Period::day(1));
        $y = $yesterday->summary();
        $before = $yesterday->previousSummary();

        // month so far vs the same number of days last month
        $now = CarbonImmutable::now($tz);
        $monthStart = $now->startOfMonth();
        $month = BusinessMetrics::for(new Period($monthStart->utc(), $now->utc(), 'none'))->summary();
        $lastMonthStart = $monthStart->subMonthNoOverflow();
        $lastMonth = BusinessMetrics::for(new Period(
            $lastMonthStart->utc(),
            $lastMonthStart->addSeconds((int) $monthStart->diffInSeconds($now))->min($monthStart->subSecond())->utc(),
            'none',
        ))->summary();

        // ideas look at the last 30 days (one day is too little to judge)
        $advisor = new Advisor(BusinessMetrics::for(Period::fromFilters(['period' => '30d'])));
        $todos = $advisor->todos();
        $idea = collect($advisor->ideas())->first(fn ($i) => $i['tone'] !== 'success') ?? $advisor->ideas()[0] ?? null;
        $best = $yesterday->products()->first(fn ($p) => $p['revenue'] > 0);

        $lines = [
            '📊 <b>ROUMDOUL — daily summary</b>',
            '<i>'.e(Period::day(1)->label()).' (yesterday)</i>',
            '',
            '💰 Revenue: <b>$'.number_format($y['revenue'], 2).'</b>'.$this->trend($y['revenue'], $before['revenue'] ?? null, 'vs day before'),
            '🛒 Paid orders: <b>'.$y['paid_orders'].'</b> · placed: '.$y['placed_orders'],
            '👥 Customers who bought: <b>'.$y['buyers'].'</b>'.($y['returning_buyers'] ? ' ('.$y['returning_buyers'].' returning)' : '')
                .($y['new_customers'] ? ' · '.$y['new_customers'].' new '.str('account')->plural($y['new_customers']) : ''),
            '👀 Shop visitors: <b>'.number_format($y['visitors']).'</b>'.($y['view_conversion'] !== null ? ' · '.$y['view_conversion'].'% ordered' : '')
                .($y['demo_views'] ? ' · '.$y['demo_views'].' demos' : ''),
            '✉️ Letters sent: <b>'.number_format($y['letters_sent']).'</b>'.($y['open_rate'] !== null ? ' · '.$y['open_rate'].'% opened' : ''),
        ];

        if ($best) {
            $lines[] = '🏆 Best seller: '.e($best['name']).' ($'.number_format($best['revenue'], 2).')';
        }

        $lines[] = '';
        $lines[] = '📅 <b>'.$now->format('F').' so far</b>: $'.number_format($month['revenue'], 2)
            .$this->trend($month['revenue'], $lastMonth['revenue'], 'vs same days last month')
            .' · '.$month['paid_orders'].' '.str('order')->plural($month['paid_orders']);

        $lines[] = '';
        if ($todos === []) {
            $lines[] = '✅ <b>Nothing waiting on you.</b>';
        } else {
            $lines[] = '⏳ <b>Do now</b>';
            foreach (array_slice($todos, 0, 6) as $todo) {
                $lines[] = '• '.e($todo['title']);
            }
        }

        if ($idea) {
            $lines[] = '';
            $lines[] = '💡 <b>Idea:</b> '.e($idea['title']).' — '.e($idea['body']);
        }

        try {
            $lines[] = '';
            $lines[] = '<a href="'.e(Dashboard::getUrl()).'">Open dashboard →</a>';
        } catch (Throwable) {
            // no panel URL outside a web context — fine without the link
        }

        return implode("\n", $lines);
    }

    private function trend(float|int|null $now, float|int|null $before, string $label): string
    {
        if ($before === null || ((float) $now === 0.0 && (float) $before === 0.0)) {
            return '';
        }

        $change = BusinessMetrics::change($now, $before);

        return match (true) {
            $change === null => ' 🆕',
            $change > 0 => " ▲ {$change}% {$label}",
            $change < 0 => ' ▼ '.abs($change)."% {$label}",
            default => " = {$label}",
        };
    }
}
