<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected ?string $heading = 'Orders by Status';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getData(): array
    {
        $labels = [
            'pending_payment' => 'Pending payment',
            'paid' => 'Paid',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
        ];

        $counts = Order::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'datasets' => [
                [
                    'data' => collect($labels)->keys()->map(fn ($status) => $counts[$status] ?? 0)->all(),
                    'backgroundColor' => ['#c79a44', '#2a9d5c', '#b02361', '#94a3b8'],
                ],
            ],
            'labels' => array_values($labels),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
