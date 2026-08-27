<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('rating')
                    ->badge()
                    ->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))
                    ->color(fn (int $state) => match (true) {
                        $state >= 4 => 'success',
                        $state === 3 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('comment')
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->options(array_combine(range(1, 5), range(1, 5))),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
