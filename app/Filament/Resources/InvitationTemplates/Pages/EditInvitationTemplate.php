<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\InvitationTemplate;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvitationTemplate extends EditRecord
{
    protected static string $resource = InvitationTemplateResource::class;

    /**
     * The stored `fields` array is flat; the form splits it into one checkbox list per
     * FIELD_SECTIONS group for readability, so it needs re-splitting on load...
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return array_merge($data, InvitationTemplate::splitFieldsBySection($data['fields'] ?? []));
    }

    /**
     * ...and re-merged back into one flat `fields` array before it's actually saved.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return InvitationTemplate::mergeFieldSections($data);
    }

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
