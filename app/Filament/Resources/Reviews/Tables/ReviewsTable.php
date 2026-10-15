<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Models\ModerationLog;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
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
                IconColumn::make('reported_at')
                    ->label('Flagged')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->reported_at !== null)
                    ->trueColor('warning')
                    ->falseColor('gray'),
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
                TernaryFilter::make('reported_at')
                    ->label('Flagged')
                    ->nullable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record) => ! $record->is_approved)
                    ->action(function ($record) {
                        $record->update(['is_approved' => true]);
                        ModerationLog::record('review_approved', 'review', $record->id, [], null, Auth::id());
                    })
                    ->successNotificationTitle('Review approved — now visible on the homepage'),
                Action::make('unapprove')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('gray')
                    ->visible(fn ($record) => $record->is_approved)
                    ->action(function ($record) {
                        $record->update(['is_approved' => false]);
                        ModerationLog::record('review_hidden', 'review', $record->id, [], null, Auth::id());
                    })
                    ->successNotificationTitle('Review hidden from the homepage'),
                Action::make('flag')
                    ->label('Flag')
                    ->icon(Heroicon::OutlinedFlag)
                    ->color('warning')
                    ->visible(fn ($record) => $record->reported_at === null)
                    ->action(function ($record) {
                        // Flagging also pulls the review from the homepage until someone looks at it.
                        $record->update(['reported_at' => now(), 'is_approved' => false]);
                        ModerationLog::record('review_flagged', 'review', $record->id, [], null, Auth::id());
                    })
                    ->successNotificationTitle('Review flagged and hidden'),
                Action::make('clearFlag')
                    ->label('Clear flag')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('gray')
                    ->visible(fn ($record) => $record->reported_at !== null)
                    ->action(function ($record) {
                        $record->update(['reported_at' => null]);
                        ModerationLog::record('review_flag_cleared', 'review', $record->id, [], null, Auth::id());
                    }),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_approved' => true]))
                        ->successNotificationTitle('Reviews approved'),
                    DeleteBulkAction::make()
                        ->authorize(fn () => Auth::user()?->isAdmin() === true),
                ]),
            ]);
    }
}
