<?php

namespace App\Filament\Resources\InvitationTemplates\Schemas;

use App\Filament\Resources\Services\Schemas\PlanFields;
use App\Filament\Resources\Services\Schemas\ServiceForm;
use App\Models\InvitationTemplate;
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
use Illuminate\Support\Str;

/**
 * Opened in a wide modal (create + edit) — tabs: Basics · Fields · Plans · Shop page.
 *
 * The stored `fields` array is flat; the «Fields» tab shows one checkbox list per FIELD_SECTIONS group,
 * so the create/edit actions split it on load and merge it back on save (InvitationTemplateActions).
 * Plans live on the linked shop Service: on create they're a plain repeater turned into a Service by
 * InvitationTemplate::createServiceFor(); on edit they're edited in place through the `service` relationship.
 */
class InvitationTemplateForm
{
    public const SECTION_LABELS = [
        'universal' => 'Universal — works for any event',
        'wedding' => 'Wedding-specific',
        'birthday' => 'Birthday-specific',
        'ceremony' => 'Buddhist ceremony-specific',
        'anniversary' => 'Anniversary / love letter-specific',
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
                            ->helperText('A grouping label, e.g. "wedding", "date-asking", "breakup".'),
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
                            ->helperText('Optional preview thumbnail for the template picker. Max 3MB.'),
                    ]),
            ]);
    }

    protected static function fieldsTab(): Tab
    {
        return Tab::make('Fields')
            ->icon(Heroicon::OutlinedListBullet)
            ->badge(fn (callable $get) => collect(array_keys(InvitationTemplate::FIELD_SECTIONS))
                ->sum(fn (string $section) => count($get("fields_{$section}") ?? [])) ?: null)
            ->columns(2)
            ->components([
                Callout::make('Which fields this design asks the customer to fill in')
                    ->description('Grouped by event type. Adding a genuinely new field means adding it to InvitationTemplate::FIELD_CATALOG (and FIELD_SECTIONS) in code first.')
                    ->info()
                    ->columnSpanFull(),
                self::fieldsCheckboxList('universal')
                    ->columns(4)
                    ->columnSpanFull(),
                self::fieldsCheckboxList('wedding')
                    ->columns(2),
                Section::make()
                    ->columnSpan(1)
                    ->components([
                        self::fieldsCheckboxList('birthday'),
                        self::fieldsCheckboxList('ceremony'),
                        self::fieldsCheckboxList('anniversary'),
                    ]),
            ]);
    }

    protected static function fieldsCheckboxList(string $section): CheckboxList
    {
        return CheckboxList::make("fields_{$section}")
            ->label(self::SECTION_LABELS[$section])
            ->options(InvitationTemplate::sectionFieldOptions($section))
            ->bulkToggleable();
    }

    protected static function plansTab(): Tab
    {
        return Tab::make('Plans')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->components([
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
                    ->defaultItems(1)
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
                    ...ServiceForm::descriptionFields(required: ! $creating),
                ]),
            Section::make('Settings')
                ->columnSpan(1)
                ->components(ServiceForm::settingsFields()),
            Section::make('Pictures')
                ->columns(3)
                ->columnSpanFull()
                ->components(ServiceForm::mediaFields()),
            Grid::make(2)
                ->columnSpanFull()
                ->components(ServiceForm::detailsSections()),
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
