<?php

namespace App\Filament\Widgets\Concerns;

use App\Support\Analytics\BusinessMetrics;
use App\Support\Analytics\Period;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/** Reads the dashboard's Period / Compare filters (reactive: widgets refresh when they change). */
trait UsesDashboardPeriod
{
    use InteractsWithPageFilters;

    protected ?BusinessMetrics $metricsInstance = null;

    protected function period(): Period
    {
        return Period::fromFilters($this->pageFilters);
    }

    protected function metrics(): BusinessMetrics
    {
        $period = $this->period();

        if (! $this->metricsInstance || $this->metricsInstance->period != $period) {
            $this->metricsInstance = BusinessMetrics::for($period);
        }

        return $this->metricsInstance;
    }
}
