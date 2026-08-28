<?php

namespace App\Filament\Resources\PromoCodes\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PromoCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30)
                    ->helperText('Customers type this at checkout — case-insensitive, always stored uppercase.')
                    ->dehydrateStateUsing(fn (string $state) => strtoupper(trim($state)))
                    ->formatStateUsing(fn (?string $state) => $state ? strtoupper($state) : $state),
                Select::make('type')
                    ->options([
                        'percentage' => 'Percentage off (%)',
                        'fixed' => 'Fixed amount off ($)',
                    ])
                    ->required()
                    ->live()
                    ->default('percentage'),
                TextInput::make('value')
                    ->label(fn (callable $get) => $get('type') === 'fixed' ? 'Amount off' : 'Percentage off')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(fn (callable $get) => $get('type') === 'percentage' ? 100 : null)
                    ->prefix(fn (callable $get) => $get('type') === 'fixed' ? '$' : null)
                    ->suffix(fn (callable $get) => $get('type') === 'percentage' ? '%' : null),
                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->helperText('Leave empty for no expiry.')
                    ->native(false),
                TextInput::make('usage_limit')
                    ->label('Total use limit')
                    ->helperText('How many times this code can be used in total. Leave empty for unlimited.')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('times_used')
                    ->label('Times used so far')
                    ->disabled()
                    ->visibleOn('edit'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
