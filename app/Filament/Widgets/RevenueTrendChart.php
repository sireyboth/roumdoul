<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use Filament\Widgets\ChartWidget;

/** Revenue over the period (by day / week / month), with the comparison period as a dashed line. */
class RevenueTrendChart extends ChartWidget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 4];

    protected ?string $maxHeight = '300px';

    public function getHeading(): ?string
    {
        return 'Revenue';
    }

    public function getDescription(): ?string
    {
        $m = $this->metrics();
        $now = $m->summary()['revenue'];
        $was = $m->previousSummary()['revenue'] ?? null;

        return '$'.number_format($now, 2).' this period'
            .($was !== null ? ' · $'.number_format($was, 2).' '.$this->period()->compareLabel() : '')
            .' · by '.$this->period()->bucket();
    }

    protected function getData(): array
    {
        $m = $this->metrics();
        $period = $this->period();
        $current = $m->revenueSeries();
        $datasets = [[
            'label' => 'This period ($)',
            'data' => array_values($current),
            'borderColor' => '#b02361',
            'backgroundColor' => 'rgba(176, 35, 97, 0.12)',
            'fill' => true,
            'tension' => 0.35,
            'pointRadius' => count($current) > 40 ? 0 : 3,
        ]];

        if ($previous = $period->previous()) {
            // the comparison period lined up bucket by bucket with this one
            $prev = array_values($m->revenueSeries($previous));
            $datasets[] = [
                'label' => ucfirst(str_replace('vs ', '', $period->compareLabel())).' ($)',
                'data' => array_pad(array_slice($prev, 0, count($current)), count($current), 0),
                'borderColor' => '#9ca3af',
                'borderDash' => [6, 4],
                'backgroundColor' => 'transparent',
                'fill' => false,
                'tension' => 0.35,
                'pointRadius' => 0,
            ];
        }

        return ['datasets' => $datasets, 'labels' => array_values($period->buckets())];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
