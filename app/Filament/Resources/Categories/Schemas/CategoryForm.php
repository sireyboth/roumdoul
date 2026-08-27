<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
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
                    ->helperText('Auto-generated from the English name — used in /shop/{slug} URLs.'),
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
            ]);
    }
}
