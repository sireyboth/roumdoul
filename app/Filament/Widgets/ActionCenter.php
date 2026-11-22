<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\UsesDashboardPeriod;
use App\Support\Analytics\Advisor;
use Filament\Widgets\Widget;

/** «Do now» (things waiting on you) + «Ideas to grow» (suggestions from the numbers). */
class ActionCenter extends Widget
{
    use UsesDashboardPeriod;

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.action-center';

    protected function getViewData(): array
    {
        $advisor = new Advisor($this->metrics());

        return [
            'todos' => $advisor->todos(),
            'ideas' => $advisor->ideas(),
        ];
    }
}
