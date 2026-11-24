<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Widgets\ChartWidget;

/** Orders placed in the period by status — a big «Cancelled» or «Pending» slice means follow-up work. */
class OrderStatusChart extends ChartWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected ?string $maxHeight = '260px';

    public function getHeading(): ?string
    {
        return 'Orders by status';
    }

    public function getDescription(): ?string
    {
        $counts = $this->metrics()->statusCounts();
        $total = array_sum($counts);

        return $total > 0
            ? $total.' placed · '.round(($counts['cancelled'] ?? 0) / $total * 100).'% cancelled'
            : 'No orders in this period';
    }

    protected function getData(): array
    {
        $counts = $this->metrics()->statusCounts();

        return [
            'datasets' => [[
                'data' => collect(OrderForm::STATUSES)->keys()->map(fn ($status) => $counts[$status] ?? 0)->all(),
                'backgroundColor' => ['#c79a44', '#2a9d5c', '#b02361', '#94a3b8'],
                'borderWidth' => 0,
            ]],
            'labels' => array_values(OrderForm::STATUSES),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '62%',
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
