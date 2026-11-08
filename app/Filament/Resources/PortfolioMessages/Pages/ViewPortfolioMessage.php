<?php

namespace App\Filament\Resources\PortfolioMessages\Pages;

use App\Filament\Resources\PortfolioMessages\PortfolioMessageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPortfolioMessage extends ViewRecord
{
    protected static string $resource = PortfolioMessageResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->read_at === null) {
            $this->record->forceFill(['read_at' => now()])->save();
        }
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
