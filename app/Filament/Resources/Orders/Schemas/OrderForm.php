<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public const STATUSES = [
        'pending_payment' => 'Pending payment',
        'paid' => 'Paid',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->columns(2)
                    ->components([
                        TextInput::make('order_number')->disabled(),
                        TextInput::make('customer_name')->disabled(),
                        TextInput::make('customer_email')->disabled(),
                        TextInput::make('customer_phone')->disabled(),
                        TextInput::make('total')->numeric()->prefix('$')->disabled(),
                    ]),

                Section::make('Fulfilment')
                    ->components([
                        Select::make('status')
                            ->options(self::STATUSES)
                            ->required(),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
