<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic info')
                    ->columnSpanFull()
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

                Grid::make(2)
                    ->columnSpanFull()
                    ->components([
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
                                    ->itemLabel(fn (array $state) => $state['label'] ?? 'New plan')
                                    ->collapsed()
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('label')
                                            ->required(),
                                        TextInput::make('price')
                                            ->required()
                                            ->numeric()
                                            ->prefix('$'),
                                    ])
                                    ->orderColumn('sort_order')
                                    ->defaultItems(0)
                                    ->columnSpanFull(),
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

                Grid::make(2)
                    ->columnSpanFull()
                    ->components([
                        Section::make('How to use')
                            ->description('Leave empty to hide this tab on the product page.')
                            ->components([
                                Repeater::make('how_to_use_steps')
                                    ->label('')
                                    ->simple(
                                        TextInput::make('step')->required(),
                                    )
                                    ->addActionLabel('Add step')
                                    ->reorderableWithButtons()
                                    ->defaultItems(0),
                            ]),

                        Section::make('Frequently Asked Questions')
                            ->description('Leave empty to hide this tab on the product page.')
                            ->components([
                                Repeater::make('faqs')
                                    ->label('')
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

                Section::make('Settings')
                    ->columnSpanFull()
                    ->columns(4)
                    ->components([
                        TextInput::make('demo_url')
                            ->label('Live demo URL')
                            ->helperText('Optional — shows a "View Live Demo" button on the product page.')
                            ->url()
                            ->placeholder('https://...')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Shown in the "Special Deals" section on the homepage.')
                            ->inline(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Visible in the shop.')
                            ->default(true)
                            ->inline(false),
                        Toggle::make('in_stock')
                            ->label('In stock')
                            ->helperText('Off shows "Out of Stock" and blocks purchases.')
                            ->default(true)
                            ->inline(false),
                    ]),
            ]);
    }
}
