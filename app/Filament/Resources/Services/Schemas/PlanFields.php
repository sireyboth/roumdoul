<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Filament\Resources\InvitationTemplates\Schemas\InvitationTemplateForm;
use App\Models\InvitationTemplate;
use App\Models\ServicePlan;
use App\Support\InvitationDefaults;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

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

        // invitation plans: dropdown suggestions (you can still type) + the admin's «Invitation defaults» for a new
        // plan row. A plain service plan gets neither, so selling a non-invitation product stays blank.
        $suggest = fn (string $key) => $invitationsOnly ? fn () => InvitationDefaults::suggestions($key) : null;
        $default = fn (string $key) => $invitationsOnly ? fn () => InvitationDefaults::addons()[$key] ?? null : null;
        $planDefault = fn (string $key) => $invitationsOnly ? fn () => InvitationDefaults::planItems()[0][$key] ?? null : null;

        return [
            Grid::make(4)
                ->columnSpanFull()
                ->components([
                    TextInput::make('label')
                        ->required()
                        ->placeholder($invitationsOnly ? 'e.g. "Basic — 10 recipients, 3 months"' : 'e.g. "1 Month"')
                        ->datalist($suggest('labels'))
                        ->columnSpan(2),
                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->datalist($suggest('prices')),
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
                        ->datalist($suggest('recipients'))
                        ->default($planDefault('max_recipients'))
                        ->helperText($only.'how many guest links the customer can make.'),
                    TextInput::make('max_gallery_photos')
                        ->label('Max gallery photos')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->placeholder('20')
                        ->datalist($suggest('photos'))
                        ->default($planDefault('max_gallery_photos'))
                        ->helperText($only.'photos allowed in the gallery. Blank = 20.'),
                    TextInput::make('retention_months')
                        ->label('Retention (months)')
                        ->numeric()
                        ->minValue(1)
                        ->datalist($suggest('months'))
                        ->default($planDefault('retention_months'))
                        ->helperText($only.'how long it stays online. Blank = never expires.'),
                    TextInput::make('badge')
                        ->label('Badge (optional)')
                        ->maxLength(40)
                        ->placeholder('e.g. Best value')
                        ->helperText('A small tag on this plan in the shop.'),
                ]),
            // which ★ fields (marked in the template's Fields tab) this plan includes; stored as catalog keys in
            // `features`. A sold invitation keeps its own copy, so changing this only affects new buyers.
            CheckboxList::make('features')
                ->label('★ Fields this plan includes')
                ->helperText($only.'Every plan gets the free fields. Tick the ★ fields this plan also gets. Mark fields ★ in the Fields tab.')
                ->options(fn (Component $component, $state, ?ServicePlan $record) => self::premiumOptions($component, $record, (array) $state))
                ->formatStateUsing(fn ($state) => array_values(is_string($state) ? (json_decode($state, true) ?: []) : (array) ($state ?? [])))
                ->dehydrateStateUsing(fn ($state) => array_values((array) ($state ?? [])))
                ->allowHtml()
                ->gridDirection('row')
                ->columns(3)
                ->bulkToggleable()
                ->default([])
                ->columnSpanFull(),
            Fieldset::make('Add-ons (Digital Invitations only)')
                ->columns(4)
                ->columnSpanFull()
                ->components([
                    TextInput::make('extra_guest_price')
                        ->default($default('extra_guest_price'))
                        ->label('Price per extra guest')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extra_photo_price')
                        ->default($default('extra_photo_price'))
                        ->label('Price per extra gallery photo')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extend_price')
                        ->default($default('extend_price'))
                        ->label('Price to extend')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('$')
                        ->helperText('Leave blank = not sold for this plan.'),
                    TextInput::make('extend_months')
                        ->default($default('extend_months'))
                        ->label('Extend by (months)')
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->placeholder((string) ServicePlan::DEFAULT_EXTEND_MONTHS)
                        ->helperText('How long one «extend» adds. Blank = '.ServicePlan::DEFAULT_EXTEND_MONTHS.'.'),
                    self::featurePricesRepeater()
                        ->when($invitationsOnly, fn (Repeater $repeater) => $repeater->default($default('feature_prices'))),
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
            ->helperText('Sell a single ★ field (e.g. map link, RSVP) to customers whose plan doesn\'t include it. Leave empty = not sold for this plan.')
            ->table([
                TableColumn::make('Extra'),
                TableColumn::make('Price')->width('12rem'),
            ])
            ->components([
                Select::make('feature')
                    ->options(fn (Component $component, $state) => collect(self::premiumOptions($component, null, array_filter([$state])))
                        ->map(fn ($label, string $key) => InvitationTemplate::FIELD_CATALOG[$key]['label'] ?? $key)
                        ->all())
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

    /**
     * The ★ fields a plan can include, as checkbox labels: read live from the template form's Fields tab
     * (`premium_fields`) when the plan is edited inside it, else from the plan's saved template, else the
     * catalog's default ★ list (the plain Services form). Keys already ticked are always kept as options, so
     * un-marking a field ★ never makes an existing plan fail validation.
     *
     * @param  list<string>  $selected
     * @return array<string, HtmlString>
     */
    public static function premiumOptions(Component $component, ?ServicePlan $record, array $selected = []): array
    {
        $root = $component->getRootContainer()->getStatePath();
        $live = data_get($component->getLivewire(), ($root ? $root.'.' : '').'premium_fields');

        $template = $record?->service?->invitationTemplates()->first();
        $keys = is_array($live) ? $live : ($template?->premiumFields() ?? InvitationTemplate::defaultPremiumFields());

        return collect([...$keys, ...$selected])
            ->filter(fn ($key) => is_string($key) && isset(InvitationTemplate::FIELD_CATALOG[$key]))
            ->unique()
            ->mapWithKeys(fn (string $key) => [$key => InvitationTemplateForm::fieldOptionLabel($key, tag: false)])
            ->all();
    }
}
