<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/**
 * Order status changes. Going to «paid» is ONLY possible through markPaid() — the admin types the amount
 * that arrived and the bank transaction ID, and Order::markPaid() checks both (amount ≥ total, ID not used
 * on another order). Every other move goes through changeStatus(), which never offers «paid».
 */
class OrderActions
{
    public static function markPaid(): Action
    {
        return Action::make('markPaid')
            ->label('Mark as paid')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Order $record) => $record->status === 'pending_payment')
            ->modalIcon(Heroicon::OutlinedBanknotes)
            ->modalHeading(fn (Order $record) => "Confirm payment for {$record->order_number}")
            ->modalDescription('Check your bank app first. Enter what actually arrived and the transaction ID — the order unlocks for the customer as soon as you confirm.')
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitActionLabel('Confirm payment')
            ->schema([Grid::make(2)->components([
                Section::make('The order')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->columns(2)
                    ->columnSpan(1)
                    ->components([
                        TextEntry::make('order_number')
                            ->label('Order #')
                            ->state(fn (Order $record) => $record->order_number)
                            ->copyable(),
                        TextEntry::make('type')
                            ->label('Type')
                            ->state(fn (Order $record) => OrderForm::TYPES[$record->type] ?? $record->type)
                            ->badge()
                            ->color(fn (Order $record) => OrderForm::typeColor($record->type)),
                        TextEntry::make('customer_name')
                            ->label('Customer')
                            ->state(fn (Order $record) => $record->customer_name)
                            ->helperText(fn (Order $record) => $record->customer_phone ?: $record->customer_email),
                        TextEntry::make('total')
                            ->label('Amount to receive')
                            ->state(fn (Order $record) => $record->total)
                            ->money()
                            ->size(TextSize::Large)
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                    ]),
                Section::make('What arrived in your bank')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columnSpan(1)
                    ->components([
                        TextInput::make('amount_received')
                            ->label('Amount received ($)')
                            ->helperText('Must be at least the order total.')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->default(fn (Order $record) => $record->total)
                            ->required(),
                        TextInput::make('transaction_ref')
                            ->label('Transaction ID from your bank app')
                            ->helperText('Copy it from ABA / Wing. One payment can only confirm one order.')
                            ->required()
                            ->maxLength(191),
                    ]),
            ])])
            ->action(function (array $data, Order $record, Schema $schema): void {
                $by = Filament::auth()->user();

                try {
                    $record->markPaid($data['amount_received'], (string) $data['transaction_ref'], $by instanceof User ? $by : null);
                } catch (ValidationException $e) {
                    // show the model's messages under the matching fields of this modal
                    throw ValidationException::withMessages(collect($e->errors())
                        ->mapWithKeys(fn (array $messages, string $key) => [$schema->getStatePath().'.'.$key => $messages])
                        ->all());
                }
            })
            ->successNotificationTitle('Payment confirmed — the order is paid and the customer has been told.');
    }

    /** Status moves other than «paid». Fulfilled is only offered once the order is paid. */
    public static function changeStatus(): Action
    {
        return Action::make('changeStatus')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('gray')
            ->visible(fn (Order $record) => self::statusOptions($record) !== [])
            ->modalHeading(fn (Order $record) => "Change status of {$record->order_number}")
            ->modalDescription('To confirm a payment use «Mark as paid» instead — it checks the amount and transaction ID.')
            ->modalWidth(Width::FourExtraLarge)
            ->schema([Grid::make(2)->components([
                TextEntry::make('current')
                    ->label('Now')
                    ->state(fn (Order $record) => OrderForm::STATUSES[$record->status] ?? $record->status)
                    ->badge()
                    ->color(fn (Order $record) => OrderForm::statusColor($record->status)),
                Select::make('status')
                    ->label('New status')
                    ->options(fn (Order $record) => self::statusOptions($record))
                    ->required()
                    ->native(false)
                    ->helperText('Back to pending locks the customer\'s invitation again until it is marked paid.'),
            ])])
            ->action(function (array $data, Order $record, Schema $schema): void {
                if (! array_key_exists($data['status'] ?? '', self::statusOptions($record))) {
                    throw ValidationException::withMessages([$schema->getStatePath().'.status' => 'That status change is not allowed here.']);
                }

                $record->update(['status' => $data['status']]);
            })
            ->successNotificationTitle('Status updated');
    }

    /** @return array<string, string> */
    public static function statusOptions(Order $order): array
    {
        $options = OrderForm::STATUSES;
        unset($options['paid'], $options[$order->status]);

        if (! $order->isPaid()) {
            unset($options['fulfilled']); // fulfilling would skip the payment check
        }

        return $options;
    }
}
