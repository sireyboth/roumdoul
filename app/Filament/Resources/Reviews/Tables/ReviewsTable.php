<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

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
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'public_form' ? 'Feedback link' : 'Order')
                    ->color(fn (string $state) => $state === 'public_form' ? 'info' : 'gray'),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->placeholder('—')
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
                IconColumn::make('is_approved')
                    ->label('Public on homepage')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('rating')
                    ->options(array_combine(range(1, 5), range(1, 5))),
                SelectFilter::make('source')
                    ->options([
                        'order' => 'Order',
                        'public_form' => 'Feedback link',
                    ]),
                TernaryFilter::make('is_approved')
                    ->label('Public on homepage'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record) => ! $record->is_approved)
                    ->action(fn ($record) => $record->update(['is_approved' => true])),
                Action::make('unapprove')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('gray')
                    ->visible(fn ($record) => $record->is_approved)
                    ->action(fn ($record) => $record->update(['is_approved' => false])),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
