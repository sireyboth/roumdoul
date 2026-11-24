<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Support\Analytics\BusinessMetrics;
use Filament\Widgets\ChartWidget;

/** Where the money comes from: new purchases vs each kind of add-on. */
class SalesMixChart extends ChartWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 2];

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return 'Where the money comes from';
    }

    public function getDescription(): ?string
    {
        return array_sum($this->metrics()->salesMix()) > 0 ? 'New sales vs add-ons, by revenue' : 'No paid sales in this period';
    }

    protected function getData(): array
    {
        $mix = $this->metrics()->salesMix();

        return [
            'datasets' => [[
                'data' => array_values($mix),
                'backgroundColor' => ['#b02361', '#d9b66a', '#6366f1', '#10b981', '#f59e0b', '#0ea5e9'],
                'borderWidth' => 0,
            ]],
            'labels' => array_map(fn ($k) => BusinessMetrics::MIX_LABELS[$k], array_keys($mix)),
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
