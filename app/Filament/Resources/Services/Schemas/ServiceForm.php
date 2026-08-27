<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Service details')
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
                            ->helperText('Shown on product cards in the shop grid.')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Full description')
                            ->helperText('Shown on the service detail page.')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->label('Image')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('services')
                            ->columnSpanFull(),
                        TextInput::make('base_price')
                            ->label('Base price')
                            ->helperText('Shown when the service has no pricing plans below.')
                            ->required()
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_featured')
                            ->label('Featured')
                            ->helperText('Shown in the "Special Deals" section on the homepage.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Visible in the shop.')
                            ->default(true),
                        Toggle::make('in_stock')
                            ->label('In stock')
                            ->helperText('Off shows "Out of Stock" and blocks purchases.')
                            ->default(true),
                    ]),

                Section::make('Pricing plans')
                    ->description('Add one or more plans customers can choose from (e.g. "1 Month", "12 Months"). Leave empty to sell at the base price only.')
                    ->components([
                        Repeater::make('plans')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Add plan')
                            ->reorderableWithButtons()
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
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
