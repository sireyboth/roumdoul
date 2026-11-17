<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class CategoryForm
{
    public const ICONS = [
        'sparkles' => 'Sparkles',
        'paint-brush' => 'Paint Brush',
        'play' => 'Play',
        'cpu-chip' => 'CPU Chip',
        'puzzle-piece' => 'Puzzle Piece',
        'shield-check' => 'Shield Check',
        'bolt' => 'Bolt',
        'star' => 'Star',
        'heart' => 'Heart',
        'truck' => 'Truck',
        'lock-closed' => 'Lock Closed',
        'cart' => 'Cart',
        'chat' => 'Chat',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Name')
                    ->icon(Heroicon::OutlinedLanguage)
                    ->description('How the category is called in the shop, in both languages.')
                    ->columns(2)
                    ->columnSpan(2)
                    ->components([
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
                            ->helperText('Filled in for you from the English name. It is the web address: /shop/{slug}.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Display')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->columnSpan(1)
                    ->components([
                        Select::make('icon')
                            ->options(self::ICONS)
                            ->required()
                            ->default('sparkles')
                            ->searchable(),
                        TextInput::make('sort_order')
                            ->label('Sort order')
                            ->helperText('Lower numbers appear first.')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
