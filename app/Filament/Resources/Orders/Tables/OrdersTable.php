<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\OrderActions;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['invitation.template', 'invitation.customer']))
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => OrderForm::TYPES[$state] ?? $state)
                    ->color(fn (?string $state) => OrderForm::typeColor($state))
                    ->description(fn (Order $record) => $record->isAddon()
                        ? ($record->invitation?->template?->name ?? 'Invitation').' #'.$record->invitation_id
                        : null),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn ($record) => $record->customer_phone),
                TextColumn::make('total')
                    ->money()
                    ->sortable(),
                // read-only: «paid» is only reachable through «Mark as paid»
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => OrderForm::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => OrderForm::statusColor($state)),
                TextColumn::make('transaction_ref')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderForm::STATUSES),
                SelectFilter::make('type')
                    ->options(OrderForm::TYPES),
            ])
            ->emptyStateIcon(Heroicon::OutlinedShoppingCart)
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Orders appear here when customers check out. You get a Telegram alert for each one.')
            ->recordActions([
                OrderActions::markPaid(),
                ViewAction::make()
                    ->modalWidth(Width::SixExtraLarge)
                    ->modalHeading(fn (Order $record) => "Order {$record->order_number}")
                    ->extraModalFooterActions(fn (Order $record) => [
                        OrderActions::markPaid()->record($record)->cancelParentActions(),
                        OrderActions::changeStatus()->record($record)->cancelParentActions(),
                    ]),
                ActionGroup::make([
                    OrderActions::changeStatus(),
                    EditAction::make()
                        ->label('Notes')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->modalHeading(fn (Order $record) => "Notes for {$record->order_number}")
                        ->modalWidth(Width::FourExtraLarge),
                    DeleteAction::make()
                        ->modalDescription('The order and its items are deleted for good. Invitations already made from it are not removed.'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
