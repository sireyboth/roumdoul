<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\ChartWidget;

class TopServicesChart extends ChartWidget
{
    protected ?string $heading = 'Top Services by Revenue';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
    ];

    protected function getData(): array
    {
        $top = OrderItem::whereHas('order', fn ($q) => $q->whereIn('status', ['paid', 'fulfilled']))
            ->selectRaw('service_name_snapshot, SUM(line_total) as revenue')
            ->groupBy('service_name_snapshot')
            ->orderByDesc('revenue')
            ->limit(5)
            ->pluck('revenue', 'service_name_snapshot');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'data' => $top->values()->map(fn ($v) => (float) $v)->all(),
                    'backgroundColor' => '#b02361',
                ],
            ],
            'labels' => $top->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
