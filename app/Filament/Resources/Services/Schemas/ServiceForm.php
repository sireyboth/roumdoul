<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\Category;
use App\Support\InvitationDefaults;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/**
 * Opened in a wide modal from the Services list — split into tabs so it fits a laptop screen
 * without scrolling: Basics · Pricing & plans · Media · Details & FAQ.
 */
class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Service')
                    ->columnSpanFull()
                    ->tabs([
                        self::basicsTab(),
                        self::pricingTab(),
                        self::mediaTab(),
                        self::detailsTab(),
                    ]),
            ]);
    }

    protected static function basicsTab(): Tab
    {
        return Tab::make('Basics')
            ->icon(Heroicon::OutlinedInformationCircle)
            ->columns(3)
            ->components([
                Section::make('Basic info')
                    ->columns(2)
                    ->columnSpan(2)
                    ->components([
                        self::categoryField(),
                        TextInput::make('name_en')
                            ->label('Name (English)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('name_km')
                            ->label('Name (Khmer)')
                            ->required(),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Auto-generated from the English name — used in /service/{slug} URLs.'),
                        ...self::descriptionFields(),
                    ]),

                Section::make('Settings')
                    ->columnSpan(1)
                    ->components([
                        ...self::settingsFields(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Visible in the shop.')
                            ->default(true),
                    ]),
            ]);
    }

    /*
     * The shop-listing fields below are shared with the Invitation Templates modal («Shop page» tab),
     * which edits the template's linked Service — keep them here so both forms stay the same.
     */

    /** Which shop category (the filter on /shop) the product is listed under. */
    public static function categoryField(bool $required = true): Select
    {
        return Select::make('category_id')
            ->label('Category')
            ->options(fn () => Category::orderBy('sort_order')->pluck('name_en', 'id'))
            ->helperText('The shop filter it shows under.')
            ->searchable()
            ->preload()
            ->required($required);
    }

    /**
     * @param  bool  $withDefaults  pre-fill the «Invitation defaults» texts (new invitation template only)
     * @return array<int, Field>
     */
    public static function descriptionFields(bool $required = true, bool $withDefaults = false): array
    {
        return [
            TextInput::make('short_description')
                ->when($withDefaults, fn (TextInput $f) => $f->default(fn () => InvitationDefaults::get('short_description')))
                ->label('Short description')
                ->helperText('Shown on product cards in the shop grid. Max 255 characters.')
                ->required($required)
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('description')
                ->when($withDefaults, fn (Textarea $f) => $f->default(fn () => InvitationDefaults::get('description')))
                ->label('Full description')
                ->helperText('Shown on the service detail page. Blank line = new paragraph, **bold**, start a line with "- " for a bullet list.')
                ->required($required)
                ->rows(10)
                ->columnSpanFull(),
        ];
    }

    /** Demo link, display order, featured, in stock (not «Active»: templates set that themselves). */
    public static function settingsFields(bool $withDefaults = false): array
    {
        return [
            TextInput::make('demo_url')
                ->label('Live demo URL')
                ->helperText('Shows a "View Live Demo" button on the product page.')
                ->url()
                ->placeholder('https://...'),
            TextInput::make('sort_order')
                ->label('Sort order')
                ->helperText('Lower numbers appear first.')
                ->required()
                ->numeric()
                ->default($withDefaults ? fn () => (int) InvitationDefaults::get('sort_order') : 0),
            Toggle::make('is_featured')
                ->label('Featured')
                ->helperText('Shown in "Special Deals" on the homepage.'),
            Toggle::make('in_stock')
                ->label('In stock')
                ->helperText('Off blocks purchases.')
                ->default(true),
        ];
    }

    protected static function pricingTab(): Tab
    {
        return Tab::make('Pricing & plans')
            ->icon(Heroicon::OutlinedCurrencyDollar)
            ->badge(fn (callable $get) => count($get('plans') ?? []) ?: null)
            ->columns(3)
            ->components([
                TextInput::make('base_price')
                    ->label('Base price')
                    ->helperText('Used when there are no plans below.')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                ...self::discountFields(),
                Repeater::make('plans')
                    ->relationship()
                    ->label('Pricing plans')
                    ->helperText('e.g. "1 Month", "12 Months". Leave empty to sell at the base price only. Click a plan to open it.')
                    ->addActionLabel('Add plan')
                    ->reorderableWithButtons()
                    ->itemLabel(fn (array $state) => ($state['label'] ?? 'New plan')
                        .(filled($state['price'] ?? null) ? ' — $'.$state['price'] : '')
                        .(($state['in_stock'] ?? true) ? '' : ' (out of stock)'))
                    ->collapsed()
                    ->collapsible()
                    ->columns(4)
                    ->components(PlanFields::components())
                    ->orderColumn('sort_order')
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]);
    }

    /** Discount on the whole product: every plan's price (cart, shop card, product page). */
    public static function discountFields(): array
    {
        return [
            Select::make('discount_type')
                ->label('Discount')
                ->options([
                    'percentage' => 'Percentage (%)',
                    'fixed' => 'Fixed amount ($)',
                ])
                ->native(false)
                ->live()
                ->placeholder('No discount'),
            TextInput::make('discount_value')
                ->label(fn (callable $get) => $get('discount_type') === 'fixed' ? 'Discount amount' : 'Discount percentage')
                ->numeric()
                ->minValue(0.01)
                ->maxValue(fn (callable $get) => $get('discount_type') === 'percentage' ? 100 : null)
                ->prefix(fn (callable $get) => $get('discount_type') === 'fixed' ? '$' : null)
                ->suffix(fn (callable $get) => $get('discount_type') === 'percentage' ? '%' : null)
                ->required(fn (callable $get) => filled($get('discount_type')))
                ->visible(fn (callable $get) => filled($get('discount_type'))),
        ];
    }

    protected static function mediaTab(): Tab
    {
        return Tab::make('Media')
            ->icon(Heroicon::OutlinedPhoto)
            ->columns(3)
            ->components(self::mediaFields());
    }

    /** Cover image + gallery screenshots of the product page. */
    public static function mediaFields(?string $coverHint = null): array
    {
        return [
            FileUpload::make('image_path')
                ->label('Cover image')
                ->helperText('Max 3MB, auto-resized to 1200×900.'.($coverHint ? ' '.$coverHint : ''))
                ->image()
                ->imageEditor()
                ->disk('s3')
                ->directory('services')
                ->maxSize(3072)
                ->automaticallyResizeImagesMode('cover')
                ->automaticallyResizeImagesToWidth('1200')
                ->automaticallyResizeImagesToHeight('900')
                ->automaticallyUpscaleImagesWhenResizing(false),
            FileUpload::make('gallery_images')
                ->label('Gallery')
                ->helperText('Extra screenshots (e.g. template pages, video scenes). Max 3MB each.')
                ->multiple()
                ->image()
                ->reorderable()
                ->disk('s3')
                ->directory('services/gallery')
                ->maxSize(3072)
                ->automaticallyResizeImagesMode('cover')
                ->automaticallyResizeImagesToWidth('1600')
                ->automaticallyResizeImagesToHeight('1200')
                ->automaticallyUpscaleImagesWhenResizing(false)
                ->panelLayout('grid')
                ->columnSpan(2),
        ];
    }

    protected static function detailsTab(): Tab
    {
        return Tab::make('Details & FAQ')
            ->icon(Heroicon::OutlinedQuestionMarkCircle)
            ->badge(fn (callable $get) => (count($get('how_to_use_steps') ?? []) + count($get('faqs') ?? [])) ?: null)
            ->columns(2)
            ->components(self::detailsSections());
    }

    /** «How to use» steps + FAQ, each its own tab on the product page. */
    public static function detailsSections(bool $withDefaults = false): array
    {
        return [
            Section::make('How to use')
                ->description('Leave empty to hide this tab on the product page.')
                ->columnSpan(1)
                ->components([
                    Repeater::make('how_to_use_steps')
                        ->hiddenLabel()
                        ->simple(
                            TextInput::make('step')->required(),
                        )
                        ->addActionLabel('Add step')
                        ->reorderableWithButtons()
                        ->default($withDefaults ? fn () => array_values(InvitationDefaults::get('how_to_use_steps') ?? []) : []),
                ]),

            Section::make('FAQ')
                ->description('Leave empty to hide this tab on the product page.')
                ->columnSpan(1)
                ->components([
                    Repeater::make('faqs')
                        ->hiddenLabel()
                        ->components([
                            TextInput::make('question')
                                ->required()
                                ->columnSpanFull(),
                            Textarea::make('answer')
                                ->required()
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->addActionLabel('Add question')
                        ->reorderableWithButtons()
                        ->itemLabel(fn (array $state) => $state['question'] ?? 'New question')
                        ->collapsed()
                        ->default($withDefaults ? fn () => array_values(InvitationDefaults::get('faqs') ?? []) : [])
                        ->collapsible(),
                ]),
        ];
    }
}
