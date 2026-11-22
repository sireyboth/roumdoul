<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Widgets\ChartWidget;

/** When customers place orders — by weekday or hour — so you know when to post. */
class PeakTimesChart extends ChartWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 3];

    protected ?string $maxHeight = '260px';

    public ?string $filter = 'weekday';

    public function getHeading(): ?string
    {
        return 'When customers order';
    }

    public function getDescription(): ?string
    {
        $data = $this->metrics()->peakTimes()[$this->filter === 'hour' ? 'hour' : 'weekday'];

        return array_sum($data) > 0
            ? 'Busiest: '.array_search(max($data), $data, true).' — post promos just before'
            : 'No orders in this period';
    }

    protected function getFilters(): ?array
    {
        return ['weekday' => 'By day of week', 'hour' => 'By hour of day'];
    }

    protected function getData(): array
    {
        $data = $this->metrics()->peakTimes()[$this->filter === 'hour' ? 'hour' : 'weekday'];
        $max = max($data ?: [0]);

        return [
            'datasets' => [[
                'label' => 'Orders placed',
                'data' => array_values($data),
                // the busiest slot stands out
                'backgroundColor' => array_map(fn ($v) => $max > 0 && $v === $max ? '#b02361' : 'rgba(176, 35, 97, 0.35)', array_values($data)),
                'borderRadius' => 4,
            ]],
            'labels' => array_keys($data),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
