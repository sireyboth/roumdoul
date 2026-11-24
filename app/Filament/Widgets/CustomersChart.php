<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Widgets\ChartWidget;

/** Buyers per day/week/month: first-time vs returning. */
class CustomersChart extends ChartWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected ?string $maxHeight = '280px';

    public function getHeading(): ?string
    {
        return 'Customers who bought';
    }

    public function getDescription(): ?string
    {
        $s = $this->metrics()->summary();

        return $s['buyers'].' '.str('buyer')->plural($s['buyers']).' · '.$s['returning_buyers'].' came back · '.$s['new_customers'].' new '.str('account')->plural($s['new_customers']);
    }

    protected function getData(): array
    {
        $series = $this->metrics()->buyersSeries();

        return [
            'datasets' => [
                ['label' => 'First time', 'data' => array_values($series['new']), 'backgroundColor' => '#b02361', 'borderRadius' => 4],
                ['label' => 'Returning', 'data' => array_values($series['returning']), 'backgroundColor' => '#d9b66a', 'borderRadius' => 4],
            ],
            'labels' => array_values($this->period()->buckets()),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom']],
            'scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
