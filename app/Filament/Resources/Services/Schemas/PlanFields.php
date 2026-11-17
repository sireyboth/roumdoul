<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\InvitationTemplate;
use App\Models\ServicePlan;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;

/**
 * The fields of one pricing plan (a ServicePlan row) — shared by the Service form's plans repeater
 * and the Invitation template form's «Plans» tab, so both always offer the same options.
 */
class PlanFields
{
    /**
     * @param  bool  $invitationsOnly  true inside an invitation template, where every plan is a Digital
     *                                 Invitations plan (no «Digital Invitations only» reminders, recipients required).
     */
    public static function components(bool $invitationsOnly = false): array
    {
        $only = $invitationsOnly ? '' : 'Digital Invitations only — ';

        return [
            Grid::make(4)
                ->columnSpanFull()
                ->components([
                    TextInput::make('label')
                        ->required()
                        ->placeholder($invitationsOnly ? 'e.g. "Basic — 10 recipients, 3 months"' : 'e.g. "1 Month"')
                        ->columnSpan(2),
                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('$'),
                    Toggle::make('in_stock')
                        ->label('In stock')
                        ->helperText('Off = can\'t be bought.')
                        ->default(true)
                        ->inline(false),
                    TextInput::make('max_recipients')
                        ->label('Max recipients')
                        ->numeric()
                        ->minValue(1)
                        ->required($invitationsOnly)
                        ->helperText($only.'how many guest links the customer can make.'),
                    TextInput::make('max_gallery_photos')
                        ->label('Max gallery photos')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->placeholder('20')
                        ->helperText($only.'photos allowed in the gallery. Blank = 20.'),
                    TextInput::make('retention_months')
                        ->label('Retention (months)')
                        ->numeric()
                        ->minValue(1)
                        ->helperText($only.'how long it stays online. Blank = never expires.'),
                ]),
            CheckboxList::make('features')
                ->label('Unlocked fields (premium extras)')
                ->helperText($only.'the essentials (names, headline, message, date, venue name, music, photo, colour) are always included. Tick the extras this plan also unlocks.')
                ->options(InvitationTemplate::premiumFieldOptions())
                ->columns(4)
                ->columnSpanFull(),
            Fieldset::make('Add-ons (Digital Invitations only)')
                ->columns(4)
                ->columnSpanFull()
                ->components([
                    TextInput::make('extra_guest_price')
                        ->label('Price per extra guest')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extra_photo_price')
                        ->label('Price per extra gallery photo')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extend_price')
                        ->label('Price to extend')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extend_months')
                        ->label('Extend by (months)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->placeholder((string) ServicePlan::DEFAULT_EXTEND_MONTHS)
                        ->helperText('How long one «extend» adds. Blank = '.ServicePlan::DEFAULT_EXTEND_MONTHS.'.'),
                    self::featurePricesRepeater(),
                ]),
        ];
    }

    /**
     * Per-feature unlock prices. Stored on the plan as one JSON object {field_key: price}; edited here as
     * friendly rows (feature picked from a list + price), converted on the way in and out.
     */
    public static function featurePricesRepeater(): Repeater
    {
        return Repeater::make('feature_prices')
            ->label('Unlock one extra on its own')
            ->helperText('Sell a single premium extra (e.g. map link, RSVP) to customers whose plan doesn\'t include it. Leave empty = not sold for this plan.')
            ->table([
                TableColumn::make('Extra'),
                TableColumn::make('Price')->width('12rem'),
            ])
            ->components([
                Select::make('feature')
                    ->options(InvitationTemplate::premiumFieldOptions())
                    ->required()
                    ->distinct()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('$')
                    ->required(),
            ])
            ->addActionLabel('Add an extra to sell')
            ->defaultItems(0)
            ->afterStateHydrated(function (Repeater $component, $state): void {
                // stored as {key: price} -> rows [{feature, price}]
                if (is_array($state) && collect($state)->contains(fn ($value) => ! is_array($value))) {
                    $component->rawState(collect($state)
                        ->map(fn ($price, $key) => ['feature' => $key, 'price' => $price])
                        ->values()
                        ->all());
                }

                $component->hydrateItems();
            })
            // rows (keyed by uuid) -> {key: price}, or null when nothing is sold
            ->mutateDehydratedStateUsing(fn (?array $state): ?array => InvitationTemplate::normalizeFeaturePrices(array_values($state ?? [])))
            ->columnSpanFull();
    }
}
