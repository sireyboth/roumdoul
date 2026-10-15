<?php

namespace App\Filament\Resources\InvitationTemplates\Pages;

use App\Filament\Resources\InvitationTemplates\InvitationTemplateResource;
use App\Filament\Resources\InvitationTemplates\Tables\InvitationTemplatesTable;
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
            // Shown when the linked Service was deleted (service_id nulled) — without one the
            // template isn't sold anywhere. Recreate it with a starter plan, then go price it.
            Action::make('setUpPricing')
                ->label('Set up pricing (not in shop)')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->visible(fn () => $this->record->service_id === null)
                ->requiresConfirmation()
                ->modalDescription('This template isn\'t linked to a shop product, so customers can\'t buy it. Create one now with a Basic plan at $0, then set the real price on the next page.')
                ->action(function () {
                    $service = InvitationTemplate::createServiceFor(
                        $this->record->name,
                        $this->record->is_active,
                        [['label' => 'Basic', 'price' => 0]],
                    );
                    $this->record->update(['service_id' => $service->id]);

                    $this->redirect(ServiceResource::getUrl('edit', ['record' => $service->id]));
                }),
            DeleteAction::make()
                ->before(fn (DeleteAction $action) => InvitationTemplatesTable::cancelIfInUse($action, collect([$this->record]))),
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
