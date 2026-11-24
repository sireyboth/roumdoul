<?php

namespace App\Filament\Resources\InvitationTemplates\Schemas;

use App\Filament\Resources\Services\Schemas\PlanFields;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Models\InvitationTemplate;
use App\Support\InvitationDefaults;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Opened in a wide modal (create + edit) — tabs: Basics · Fields · Plans · Shop page.
 *
 * The stored `fields` array is flat; the «Fields» tab shows one card + checkbox list per FIELD_SECTIONS group
 * (most used first, premium fields tagged),
 * so the create/edit actions split it on load and merge it back on save (InvitationTemplateActions).
 * Plans live on the linked shop Service: on create they're a plain repeater turned into a Service by
 * InvitationTemplate::createServiceFor(); on edit they're edited in place through the `service` relationship.
 */
class InvitationTemplateForm
{
    /**
     * Heading, icon and one-line hint for each InvitationTemplate::FIELD_SECTIONS group on the «Fields» tab,
     * in display order. `wide` = full row with more columns (the most-used group).
     */
    public const GROUPS = [
        'essentials' => ['label' => 'Most used — almost every design', 'icon' => Heroicon::OutlinedStar, 'description' => 'Message, date, place, photos, music. Every plan gets these.', 'wide' => true],
        'premium' => ['label' => 'Extras — map, RSVP, countdown…', 'icon' => Heroicon::OutlinedPuzzlePiece, 'description' => 'Ticking one marks it ★ below by default — untick the ★ to give it to every plan.', 'wide' => true],
        'wedding' => ['label' => 'Wedding couple & family', 'icon' => Heroicon::OutlinedHeart, 'description' => 'Names, parents, phones and portraits of the couple.'],
        'gifts' => ['label' => 'Gifts & contact', 'icon' => Heroicon::OutlinedGift, 'description' => 'Gift account details, text above the QR, a phone to call.'],
        'wording' => ['label' => 'More details & guest notes', 'icon' => Heroicon::OutlinedPencilSquare, 'description' => 'End time, extra texts, dress code, what to bring, reply-by date.'],
        'photos' => ['label' => 'More photos', 'icon' => Heroicon::OutlinedPhoto, 'description' => 'Captions, background, title picture, guest photo feed.'],
        'anniversary' => ['label' => 'Anniversary / love letter', 'icon' => Heroicon::OutlinedSparkles, 'description' => 'Together since, reasons, song, closing line.'],
        'birthday' => ['label' => 'Birthday', 'icon' => Heroicon::OutlinedCake, 'description' => 'Who is celebrating and the age.'],
        'ceremony' => ['label' => 'Buddhist ceremony', 'icon' => Heroicon::OutlinedBuildingLibrary, 'description' => 'Hosts and the opening line.'],
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Template')
                    ->columnSpanFull()
                    ->tabs([
                        self::basicsTab(),
                        self::fieldsTab(),
                        self::plansTab(),
                        self::shopTab(),
                    ]),
            ]);
    }

    protected static function basicsTab(): Tab
    {
        return Tab::make('Basics')
            ->icon(Heroicon::OutlinedInformationCircle)
            ->columns(3)
            ->components([
                Section::make('Template')
                    ->columns(2)
                    ->columnSpan(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Product name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in the demo URL: /templates/{slug}/demo'),
                        TextInput::make('category')
                            ->required()
                            ->datalist(fn () => InvitationTemplate::query()->distinct()->orderBy('category')->pluck('category')
                                ->merge(collect(InvitationDefaults::get('category_presets') ?? [])->pluck('category'))
                                ->filter()->unique()->values()->all())
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (?string $state, callable $get, callable $set) => self::applyCategoryPreset($state, $get, $set))
                            ->helperText('A grouping label, e.g. "wedding", "birthday". "wedding" ticks the usual wedding fields for you (Invitation defaults).'),
                        Select::make('view')
                            ->label('Design (Blade view)')
                            ->options(self::availableViews())
                            ->searchable()
                            ->required()
                            ->helperText('The Blade file that renders this design — a developer adds new options here by creating the file under resources/views/invitations/templates/.'),
                        Toggle::make('is_premium')
                            ->label('Premium')
                            ->helperText('Only shown to customers with a plan that unlocks it.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Off hides it from the template picker and demo page.'),
                    ]),
                Section::make('Thumbnail')
                    ->columnSpan(1)
                    ->components([
                        FileUpload::make('thumbnail')
                            ->hiddenLabel()
                            ->image()
                            ->disk('s3')
                            ->directory('invitation-templates')
                            ->maxSize(3072)
                            ->helperText('Preview for the template picker. Max 3MB. Also used as the shop cover image when Cover image (Shop page) is left blank.'),
                    ]),
            ]);
    }

    protected static function fieldsTab(): Tab
    {
        $groups = collect(self::GROUPS)
            ->filter(fn (array $group, string $key) => isset(InvitationTemplate::FIELD_SECTIONS[$key]));

        return Tab::make('Fields')
            ->icon(Heroicon::OutlinedListBullet)
            ->badge(fn (callable $get) => collect(array_keys(InvitationTemplate::FIELD_SECTIONS))
                ->sum(fn (string $section) => count($get("fields_{$section}") ?? [])) ?: null)
            ->columns(2)
            ->components([
                Callout::make(new HtmlString(
                    'Tick what this design shows. Hover '.self::infoIcon().' to see what a field does.'
                ))
                    ->info()
                    ->columnSpanFull(),
                ...$groups->map(function (array $group, string $key) {
                    $wide = $group['wide'] ?? false;

                    return Section::make($group['label'])
                        ->icon($group['icon'])
                        ->description($group['description'])
                        ->compact()
                        ->columnSpan($wide ? 'full' : 1)
                        // the most-used card stays open; the others fold away while nothing in them is ticked
                        ->collapsible(! $wide)
                        ->collapsed($wide ? false : fn (callable $get) => blank($get("fields_{$key}")))
                        ->components([
                            self::fieldsCheckboxList($key)->columns($wide ? 3 : 2),
                        ]);
                })->values()->all(),
                self::premiumSection(),
            ]);
    }

    /**
     * «★ Needs a plan»: which of the ticked fields a customer only gets when their plan includes them (template ›
     * Plans) or they buy the unlock. Options follow the ticks above live; newly ticked fields from the catalog's
     * suggested ★ list start ticked here (syncPremium()). Saved as `premium_fields`.
     */
    protected static function premiumSection(): Section
    {
        return Section::make('★ Needs a plan (premium)')
            ->icon(Heroicon::OutlinedLockClosed)
            ->description('Ticked = only plans that include it (Plans tab) get it, or the customer buys the unlock. Unticked = every plan gets it. Invitations already sold never lose a field.')
            ->compact()
            ->columnSpanFull()
            ->components([
                CheckboxList::make('premium_fields')
                    ->hiddenLabel()
                    ->options(fn (callable $get) => collect(array_keys(InvitationTemplate::FIELD_SECTIONS))
                        ->flatMap(fn (string $section) => $get("fields_{$section}") ?? [])
                        ->unique()
                        ->filter(fn ($key) => isset(InvitationTemplate::markableFieldOptions()[$key]))
                        ->mapWithKeys(fn (string $key) => [$key => self::fieldOptionLabel($key, tag: false)])
                        ->all())
                    ->default([])
                    ->allowHtml()
                    ->gridDirection('row')
                    ->columns(3)
                    ->bulkToggleable(),
            ]);
    }

    /**
     * Category typed on Basics → tick the fields of its «Invitation defaults» preset (e.g. wedding: most used,
     * premium extras, couple & family, dress code). Only adds ticks, never removes; ★ follows syncPremium().
     */
    public static function applyCategoryPreset(?string $category, callable $get, callable $set): void
    {
        $preset = InvitationDefaults::presetFieldsFor($category);

        if ($preset === []) {
            return;
        }

        foreach (InvitationTemplate::FIELD_SECTIONS as $section => $keys) {
            $old = $get("fields_{$section}") ?? [];
            $new = array_values(array_unique([...$old, ...array_intersect($keys, $preset)]));

            if ($new !== $old) {
                $set("fields_{$section}", $new);
                self::syncPremium($new, $old, $get, $set);
            }
        }
    }

    /** Keep `premium_fields` in step with the ticks: unticked fields drop out, newly ticked suggested-★ ones join. */
    public static function syncPremium(?array $state, ?array $old, callable $get, callable $set): void
    {
        $added = array_diff($state ?? [], $old ?? []);
        $removed = array_diff($old ?? [], $state ?? []);
        $premium = array_diff($get('premium_fields') ?? [], $removed);

        $set('premium_fields', array_values(array_unique([
            ...$premium,
            ...array_intersect($added, InvitationTemplate::defaultPremiumFields()),
        ])));
    }

    protected static function fieldsCheckboxList(string $section): CheckboxList
    {
        return CheckboxList::make("fields_{$section}")
            ->live()
            ->afterStateUpdated(fn (?array $state, ?array $old, callable $get, callable $set) => self::syncPremium($state, $old, $get, $set))
            ->hiddenLabel()
            ->options(collect(InvitationTemplate::sectionFieldOptions($section))
                ->map(fn (string $label, string $key) => self::fieldOptionLabel($key, tag: false))
                ->all())
            ->allowHtml()
            ->gridDirection('row')
            ->bulkToggleable();
    }

    /**
     * A field's checkbox label for admin lists: its name, the «★ Premium» tag when a plan must unlock it, and an
     * ⓘ that shows InvitationTemplate::adminHint() on hover (clicking the ⓘ doesn't tick the box). Also used by
     * the plan's «Unlocked fields» list (pass $tag = false there: everything on it is premium).
     */
    public static function fieldOptionLabel(string $key, bool $tag = true): HtmlString
    {
        $premium = isset(InvitationTemplate::premiumFieldOptions()[$key]) || isset(InvitationTemplate::UNLOCKED_WITH[$key]); // the catalog's suggestion
        $hint = json_encode(InvitationTemplate::adminHint($key), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);

        return new HtmlString(
            '<span style="display:inline-flex;align-items:center;gap:.3rem;white-space:nowrap">'
            .e(InvitationTemplate::FIELD_CATALOG[$key]['label'] ?? $key)
            .($tag && $premium ? self::premiumBadge() : '')
            .'<span x-on:click.prevent.stop x-tooltip="{ content: '.e($hint).', theme: $store.theme }">'.self::infoIcon().'</span>'
            .'</span>'
        );
    }

    /** The small amber «★ Premium» tag (inline styles: the admin's compiled CSS has no arbitrary classes). */
    protected static function premiumBadge(): string
    {
        return '<span style="display:inline-block;padding:0 .35rem;border-radius:9999px;font-size:.65rem;font-weight:600;'
            .'line-height:1.1rem;color:#b45309;background:rgba(245,158,11,.15);white-space:nowrap">★ Premium</span>';
    }

    protected static function infoIcon(): string
    {
        return '<svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor" style="width:.9rem;height:.9rem;opacity:.4;cursor:help;vertical-align:-2px;display:inline">'
            .'<path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd"/></svg>';
    }

    protected static function plansTab(): Tab
    {
        return Tab::make('Plans')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->components([
                // discount on the whole product (every plan) — create: applied with the «Shop page» fields
                Group::make()
                    ->statePath('shop')
                    ->visibleOn('create')
                    ->columns(3)
                    ->components(ServiceForm::discountFields()),
                Group::make()
                    ->relationship('service')
                    ->visible(fn (string $operation, ?InvitationTemplate $record) => $operation !== 'create' && $record?->service_id !== null)
                    ->columns(3)
                    ->components(ServiceForm::discountFields()),

                // create: plain repeater, turned into the Service + plans on save
                Repeater::make('plans')
                    ->label('Pricing plans')
                    ->helperText('What customers actually buy. Each plan sets how many guest links they get, how long it stays online, which extras it unlocks, and which add-ons they can buy later.')
                    ->visibleOn('create')
                    ->addActionLabel('Add plan')
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(fn (array $state) => $state['label'] ?? 'New plan')
                    ->columns(4)
                    ->components(PlanFields::components(invitationsOnly: true))
                    // starting plans + add-on prices from Admin › Invitation defaults
                    ->default(fn () => InvitationDefaults::planItems())
                    ->minItems(1)
                    ->required(),

                // edit: the linked shop Service's plans, saved straight to it
                Section::make()
                    ->relationship('service')
                    ->visible(fn (string $operation, ?InvitationTemplate $record) => $operation !== 'create' && $record?->service_id !== null)
                    ->components([
                        Repeater::make('plans')
                            ->relationship()
                            ->label('Pricing plans')
                            ->helperText('What customers buy. Changing a plan does not change invitations already sold — each one keeps its own limits (edit those under «Invitations»). Click a plan to open it.')
                            ->addActionLabel('Add plan')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state) => ($state['label'] ?? 'New plan')
                                .(filled($state['price'] ?? null) ? ' — $'.$state['price'] : '')
                                .(($state['in_stock'] ?? true) ? '' : ' (out of stock)'))
                            ->columns(4)
                            ->components(PlanFields::components(invitationsOnly: true))
                            ->orderColumn('sort_order')
                            ->defaultItems(0),
                    ]),

                Callout::make('Not sold in the shop')
                    ->description('This template isn\'t linked to a shop product, so customers can\'t buy it. Close this window and use «Set up pricing» on the template to add it to the shop.')
                    ->warning()
                    ->visible(fn (string $operation, ?InvitationTemplate $record) => $operation !== 'create' && $record?->service_id === null),
            ]);
    }

    /**
     * The template's product in the shop: which category filter it's under, display order, featured,
     * texts, cover image + gallery, «How to use» + FAQ. The same fields as the Services form
     * (ServiceForm helpers). Edit saves them straight to the linked Service; create collects them
     * under `shop` and InvitationTemplateActions::create() applies them to the new Service.
     */
    protected static function shopTab(): Tab
    {
        return Tab::make('Shop page')
            ->icon(Heroicon::OutlinedShoppingBag)
            ->components([
                Group::make()
                    ->statePath('shop')
                    ->visibleOn('create')
                    ->columns(3)
                    ->components(self::shopComponents(creating: true)),

                Group::make()
                    ->relationship('service')
                    ->visible(fn (string $operation, ?InvitationTemplate $record) => $operation !== 'create' && $record?->service_id !== null)
                    ->columns(3)
                    ->components(self::shopComponents()),

                Callout::make('Not sold in the shop')
                    ->description('This template isn\'t linked to a shop product, so it has no shop page. Close this window and use «Set up pricing» on the template to add it to the shop.')
                    ->warning()
                    ->visible(fn (string $operation, ?InvitationTemplate $record) => $operation !== 'create' && $record?->service_id === null),
            ]);
    }

    protected static function shopComponents(bool $creating = false): array
    {
        return [
            Section::make('Listing')
                ->description($creating ? 'Leave blank to use the product name and the «Digital Invitations» category.' : null)
                ->columns(2)
                ->columnSpan(2)
                ->components([
                    ServiceForm::categoryField(required: ! $creating)
                        ->placeholder($creating ? 'Digital Invitations (default)' : null),
                    ...ServiceForm::descriptionFields(required: ! $creating, withDefaults: $creating),
                ]),
            Section::make('Settings')
                ->columnSpan(1)
                ->components(ServiceForm::settingsFields(withDefaults: $creating)),
            Section::make('Pictures')
                ->columns(3)
                ->columnSpanFull()
                ->components(ServiceForm::mediaFields(coverHint: 'Blank = uses the template thumbnail (Basics).')),
            Grid::make(2)
                ->columnSpanFull()
                ->components(ServiceForm::detailsSections(withDefaults: $creating)),
        ];
    }

    /**
     * Every Blade file under resources/views/invitations/templates/, as dot-notation
     * view names a developer has already built and is ready to be assigned to a template.
     */
    protected static function availableViews(): array
    {
        $path = resource_path('views/invitations/templates');

        if (! File::isDirectory($path)) {
            return [];
        }

        // subfolders too: anniversary-category/rose-bloom -> invitations.templates.anniversary-category.rose-bloom
        return collect(File::allFiles($path))
            ->filter(fn ($file) => Str::endsWith($file->getFilename(), '.blade.php'))
            ->mapWithKeys(function ($file) {
                $name = str_replace([DIRECTORY_SEPARATOR, '/'], '.', Str::before($file->getRelativePathname(), '.blade.php'));

                return ["invitations.templates.{$name}" => $name];
            })
            ->sort()
            ->toArray();
    }
}
