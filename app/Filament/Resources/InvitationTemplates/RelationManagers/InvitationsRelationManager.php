<?php

namespace App\Filament\Resources\InvitationTemplates\RelationManagers;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The customer invitations built on this template — the ones that block deleting it.
 * Deleting here soft-deletes the invitation (its guest links stop working); once none
 * are left, the template itself can be deleted and purges the trashed ones with it.
 */
class InvitationsRelationManager extends RelationManager
{
    protected static string $relationship = 'invitations';

    protected static ?string $title = 'Invitations using this template';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('slug')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'order'])->withCount('recipients'))
            ->columns([
                TextColumn::make('slug')
                    ->label('Link')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->description(fn (Invitation $record) => $record->customer?->email)
                    ->url(fn (Invitation $record) => $record->customer_id
                        ? CustomerResource::getUrl('edit', ['record' => $record->customer_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->url(fn (Invitation $record) => $record->order_id
                        ? OrderResource::getUrl('view', ['record' => $record->order_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('order.status')
                    ->label('Order status')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('recipients_count')
                    ->label('Guests')
                    ->badge(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('Never'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('openLink')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (Invitation $record) => $record->recipients()->exists())
                    ->url(fn (Invitation $record) => route('invitation.show', [
                        'invitation' => $record,
                        'recipient' => $record->recipients()->oldest('id')->first(),
                    ]))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->modalDescription('All guest links for this invitation will stop working. This can\'t be undone from the admin panel.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription('All guest links for these invitations will stop working.'),
                ]),
            ]);
    }
}
