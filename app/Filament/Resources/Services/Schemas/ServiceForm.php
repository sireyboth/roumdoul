<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Models\InvitationTemplate;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->components([
                        Group::make([
                            Section::make('Basic info')
                                ->columns(2)
                                ->components([
                                    Select::make('category_id')
                                        ->relationship('category', 'name_en')
                                        ->searchable()
                                        ->preload()
                                        ->required(),
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
                                    TextInput::make('short_description')
                                        ->label('Short description')
                                        ->helperText('Shown on product cards in the shop grid. Max 255 characters.')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Textarea::make('description')
                                        ->label('Full description')
                                        ->helperText('Shown on the service detail page.')
                                        ->required()
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Pricing')
                                ->columns(2)
                                ->components([
                                    TextInput::make('base_price')
                                        ->label('Base price')
                                        ->helperText('When there are no plans below.')
                                        ->required()
                                        ->numeric()
                                        ->prefix('$'),
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
                                        ->visible(fn (callable $get) => filled($get('discount_type')))
                                        ->columnSpanFull(),
                                    Repeater::make('plans')
                                        ->relationship()
                                        ->label('Pricing plans')
                                        ->helperText('e.g. "1 Month", "12 Months". Leave empty to sell at the base price only.')
                                        ->addActionLabel('Add plan')
                                        ->reorderableWithButtons()
                                        ->itemLabel(fn (array $state) => ($state['label'] ?? 'New plan').(($state['in_stock'] ?? true) ? '' : ' (out of stock)'))
                                        ->collapsed()
                                        ->columns(2)
                                        ->components([
                                            TextInput::make('label')
                                                ->required(),
                                            TextInput::make('price')
                                                ->required()
                                                ->numeric()
                                                ->prefix('$'),
                                            Toggle::make('in_stock')
                                                ->label('In stock')
                                                ->default(true),
                                            TextInput::make('max_recipients')
                                                ->label('Max recipients')
                                                ->numeric()
                                                ->minValue(1)
                                                ->helperText('Digital Invitations only — how many shareable recipient links this plan allows.'),
                                            TextInput::make('retention_months')
                                                ->label('Retention (months)')
                                                ->numeric()
                                                ->minValue(1)
                                                ->helperText('Digital Invitations only — how long it stays active before expiring. Leave blank for no expiry.'),
                                            CheckboxList::make('features')
                                                ->label('Unlocked fields (premium extras)')
                                                ->helperText('Digital Invitations only — the essentials are always included on every plan; tick which extra fields this plan also unlocks.')
                                                ->options(InvitationTemplate::premiumFieldOptions())
                                                ->columns(2)
                                                ->columnSpanFull(),
                                        ])
                                        ->orderColumn('sort_order')
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ]),

                            Section::make('Settings')
                                ->columns(2)
                                ->components([
                                    TextInput::make('demo_url')
                                        ->label('Live demo URL')
                                        ->helperText('Shows a "View Live Demo" button on the product page.')
                                        ->url()
                                        ->placeholder('https://...')
                                        ->columnSpanFull(),
                                    TextInput::make('sort_order')
                                        ->label('Sort order')
                                        ->required()
                                        ->numeric()
                                        ->default(0)
                                        ->columnSpanFull(),
                                    Toggle::make('is_featured')
                                        ->label('Featured')
                                        ->helperText('Shown in "Special Deals" on the homepage.'),
                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->helperText('Visible in the shop.')
                                        ->default(true),
                                    Toggle::make('in_stock')
                                        ->label('In stock')
                                        ->helperText('Off blocks purchases.')
                                        ->default(true)
                                        ->columnSpanFull(),
                                ]),
                        ]),

                        Group::make([
                            Tabs::make('Extra content')
                                ->tabs([
                                    Tab::make('How to use')
                                        ->badge(fn (callable $get) => count($get('how_to_use_steps') ?? []) ?: null)
                                        ->components([
                                            Repeater::make('how_to_use_steps')
                                                ->label('')
                                                ->helperText('Leave empty to hide this tab on the product page.')
                                                ->simple(
                                                    TextInput::make('step')->required(),
                                                )
                                                ->addActionLabel('Add step')
                                                ->reorderableWithButtons()
                                                ->defaultItems(0),
                                        ]),

                                    Tab::make('FAQ')
                                        ->badge(fn (callable $get) => count($get('faqs') ?? []) ?: null)
                                        ->components([
                                            Repeater::make('faqs')
                                                ->label('')
                                                ->helperText('Leave empty to hide this tab on the product page.')
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
                                                ->defaultItems(0)
                                                ->collapsible(),
                                        ]),
                                ]),

                            Section::make('Media')
                                ->components([
                                    FileUpload::make('image_path')
                                        ->label('Cover image')
                                        ->helperText('Max 3MB, auto-resized to 1200×900.')
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
                                        ->helperText('Extra screenshots (e.g. template pages, video scenes).')
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
                                        ->panelLayout('grid'),
                                ]),
                        ]),
                    ]),
            ]);
    }
}
