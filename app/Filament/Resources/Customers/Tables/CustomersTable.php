<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\Customers\Actions\ResetPasswordAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('phone')
                    ->label('Phone / Telegram')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->emptyStateIcon(Heroicon::OutlinedIdentification)
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Customers appear here once they create an account on the website.')
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge),
                ResetPasswordAction::make(),
            ]);
    }
}
