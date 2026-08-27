<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'md' => 2,
    ];

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7' => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 90 days',
        ];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;

        $daily = Order::whereIn('status', ['paid', 'fulfilled'])
            ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $revenue = [];

        foreach (range($days - 1, 0) as $daysAgo) {
            $date = now()->subDays($daysAgo);
            $labels[] = $date->format('M j');
            $revenue[] = (float) ($daily[$date->toDateString()] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $revenue,
                    'borderColor' => '#b02361',
                    'backgroundColor' => 'rgba(176, 35, 97, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
