<?php

namespace App\Filament\Resources\PromoCodes\Tables;

use App\Models\PromoCode;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PromoCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn (PromoCode $record) => $record->label()),
                TextColumn::make('usage')
                    ->label('Used')
                    ->state(fn (PromoCode $record) => $record->usage_limit
                        ? "{$record->times_used} / {$record->usage_limit}"
                        : (string) $record->times_used),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (PromoCode $record) => $record->isValid() ? 'Valid' : 'Expired / used up')
                    ->color(fn (PromoCode $record) => $record->isValid() ? 'success' : 'danger'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
