<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Filament\Resources\Services\ServiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvitationTemplate extends EditRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('managePricing')
                ->label('Manage pricing & plans')
                ->icon('heroicon-o-currency-dollar')
                ->visible(fn () => $this->record->service_id !== null)
                ->url(fn () => ServiceResource::getUrl('edit', ['record' => $this->record->service_id])),
            DeleteAction::make(),
        ];
    }

    /**
     * Keep the underlying Service's name/status in sync — the admin only ever edits
     * the template's own name/active toggle here, not the Service directly.
     */
    protected function afterSave(): void
    {
        $this->record->service?->update([
            'name_en' => $this->record->name,
            'name_km' => $this->record->name,
            'is_active' => $this->record->is_active,
        ]);
    }
}
