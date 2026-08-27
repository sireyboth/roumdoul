<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paidStatuses = ['paid', 'fulfilled'];

        $totalRevenue = Order::whereIn('status', $paidStatuses)->sum('total');

        $ordersThisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $ordersLastMonth = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $paidOrderCount = Order::whereIn('status', $paidStatuses)->count();
        $avgOrderValue = $paidOrderCount > 0 ? $totalRevenue / $paidOrderCount : 0;

        $pendingCount = Order::where('status', 'pending_payment')->count();

        $avgRating = Review::avg('rating');
        $reviewCount = Review::count();

        $revenueTrend = collect(range(6, 0))
            ->map(function (int $daysAgo) use ($paidStatuses) {
                $date = now()->subDays($daysAgo)->toDateString();

                return (float) Order::whereIn('status', $paidStatuses)
                    ->whereDate('created_at', $date)
                    ->sum('total');
            })
            ->all();

        $ordersTrend = collect(range(6, 0))
            ->map(fn (int $daysAgo) => Order::whereDate('created_at', now()->subDays($daysAgo)->toDateString())->count())
            ->all();

        return [
            Stat::make('Total Revenue', '$'.number_format($totalRevenue, 2))
                ->description('From paid & fulfilled orders')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->chart($revenueTrend)
                ->color('success'),

            Stat::make('Orders This Month', (string) $ordersThisMonth)
                ->description($this->trendLabel($ordersThisMonth, $ordersLastMonth))
                ->descriptionIcon($ordersThisMonth >= $ordersLastMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($ordersTrend)
                ->color($ordersThisMonth >= $ordersLastMonth ? 'success' : 'danger'),

            Stat::make('Avg. Order Value', '$'.number_format($avgOrderValue, 2))
                ->description('Across paid & fulfilled orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Awaiting Payment', (string) $pendingCount)
                ->description($pendingCount > 0 ? 'Needs your attention' : 'All caught up')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingCount > 0 ? 'warning' : 'success'),

            Stat::make('Customer Rating', $avgRating ? number_format($avgRating, 1).' / 5' : '—')
                ->description($reviewCount.' review'.($reviewCount === 1 ? '' : 's'))
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }

    protected function trendLabel(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'New this month' : 'No orders last month either';
        }

        $change = round((($current - $previous) / $previous) * 100);

        return $change >= 0
            ? "+{$change}% vs last month"
            : "{$change}% vs last month";
    }
}
