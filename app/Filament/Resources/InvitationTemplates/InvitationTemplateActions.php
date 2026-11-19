<?php

namespace App\Filament\Resources\InvitationTemplates;

use App\Filament\Resources\InvitationTemplates\Tables\InvitationTemplatesTable;
use App\Models\InvitationTemplate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

/**
 * The template's create / edit / set-up-pricing / delete actions, shared by the list table and the
 * template's page (which only exists to show the «Invitations using this template» table).
 */
class InvitationTemplateActions
{
    public static function create(): CreateAction
    {
        return CreateAction::make()
            ->label('New template')
            ->icon(Heroicon::OutlinedPlus)
            ->modalHeading('New invitation template')
            ->modalWidth(Width::SevenExtraLarge)
            // creating a template also creates its shop Service + plans, so admin never visits that screen
            ->using(function (array $data): InvitationTemplate {
                return DB::transaction(function () use ($data) {
                    $data = InvitationTemplate::mergeFieldSections($data);

                    $plans = array_values($data['plans'] ?? []);
                    $shop = $data['shop'] ?? [];
                    unset($data['plans'], $data['shop']);

                    $service = InvitationTemplate::createServiceFor($data['name'], $data['is_active'] ?? true, $plans);

                    // «Shop page» tab: category, order, texts, pictures, FAQ — blanks keep createServiceFor()'s defaults
                    $service->update(array_filter($shop, fn ($value) => $value !== null && $value !== '' && $value !== []));

                    // createServiceFor() copies everything but the stock toggle — set that here
                    foreach ($service->plans()->orderBy('sort_order')->get() as $index => $plan) {
                        if (array_key_exists('in_stock', $plans[$index] ?? [])) {
                            $plan->update(['in_stock' => (bool) $plans[$index]['in_stock']]);
                        }
                    }

                    $data['service_id'] = $service->id;

                    return InvitationTemplate::create($data);
                });
            });
    }

    public static function edit(): EditAction
    {
        return EditAction::make()
            ->modalHeading(fn (InvitationTemplate $record) => "Edit template: {$record->name}")
            ->modalWidth(Width::SevenExtraLarge)
            // the stored flat `fields` array is shown as one checkbox list per section...
            ->mutateRecordDataUsing(fn (array $data) => array_merge($data, InvitationTemplate::splitFieldsBySection($data['fields'] ?? [])))
            // ...and merged back before saving
            ->mutateDataUsing(fn (array $data) => InvitationTemplate::mergeFieldSections($data))
            // keep the underlying Service's name/status in sync with the template
            ->after(fn (InvitationTemplate $record) => $record->service?->update([
                'name_en' => $record->name,
                'name_km' => $record->name,
                'is_active' => $record->is_active,
            ]));
    }

    /**
     * Shown when the linked Service was deleted (service_id nulled) — without one the template isn't
     * sold anywhere. Recreates it with a starter plan; the real price is then set in Edit › Plans.
     */
    public static function setUpPricing(): Action
    {
        return Action::make('setUpPricing')
            ->label('Set up pricing')
            ->tooltip('Not in the shop yet')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->color('warning')
            ->visible(fn (InvitationTemplate $record) => $record->service_id === null)
            ->requiresConfirmation()
            ->modalHeading('Add this template to the shop?')
            ->modalDescription('This template isn\'t linked to a shop product, so customers can\'t buy it. This creates one with a Basic plan at $0 — then open Edit › Plans to set the real price.')
            ->modalSubmitActionLabel('Add to shop')
            ->action(function (InvitationTemplate $record): void {
                $service = InvitationTemplate::createServiceFor(
                    $record->name,
                    $record->is_active,
                    [['label' => 'Basic', 'price' => 0]],
                );
                $record->update(['service_id' => $service->id]);
            })
            ->successNotificationTitle('Added to the shop with a Basic plan at $0. Open Edit › Plans to set the real price.');
    }

    public static function delete(): DeleteAction
    {
        return DeleteAction::make()
            ->modalDescription('The template is removed for good. Templates still used by customers\' invitations can\'t be deleted.')
            ->before(fn (DeleteAction $action, InvitationTemplate $record) => InvitationTemplatesTable::cancelIfInUse($action, collect([$record])));
    }
}
