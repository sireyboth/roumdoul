<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use App\Services\InvitationAddons;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The «Notes» modal. Status is never edited here: pending → paid only through «Mark as paid»
 * (amount + bank transaction ID check), other moves through «Change status».
 */
class OrderForm
{
    public const STATUSES = [
        'pending_payment' => 'Pending payment',
        'paid' => 'Paid',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ];

    public const TYPES = [
        Order::TYPE_PURCHASE => 'Purchase',
        Order::TYPE_ADDON => 'Add-on',
    ];

    public const ADDON_TYPES = [
        InvitationAddons::GUESTS => 'More guests',
        InvitationAddons::PHOTOS => 'More photos',
        InvitationAddons::FEATURE => 'Unlock extra',
        InvitationAddons::UPGRADE => 'Plan upgrade',
        InvitationAddons::EXTEND => 'Extend time',
    ];

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'paid', 'fulfilled' => 'success',
            'cancelled' => 'danger',
            null => 'gray',
            default => 'warning',
        };
    }

    public static function typeColor(?string $state): string
    {
        return $state === Order::TYPE_ADDON ? 'info' : 'gray';
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Order')
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->columns(2)
                    ->components([
                        TextInput::make('order_number')->label('Order #')->disabled(),
                        TextInput::make('total')->numeric()->prefix('$')->disabled(),
                        TextInput::make('customer_name')->label('Customer')->disabled(),
                        TextInput::make('customer_phone')->label('Phone / Telegram')->disabled(),
                        TextInput::make('customer_email')->label('Email')->disabled()->columnSpanFull(),
                    ]),
                Section::make('Notes')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->description('Only you and your team see these.')
                    ->components([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(6),
                    ]),
            ]);
    }
}
