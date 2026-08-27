<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->columns(2)
                    ->components([
                        TextEntry::make('order_number')->label('Order #')->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => OrderForm::STATUSES[$state] ?? $state)
                            ->color(fn (string $state) => match ($state) {
                                'paid', 'fulfilled' => 'success',
                                'cancelled' => 'danger',
                                default => 'warning',
                            }),
                        TextEntry::make('customer_name')->label('Name'),
                        TextEntry::make('customer_email')->label('Email')->copyable(),
                        TextEntry::make('customer_phone')->label('Phone / Telegram')->copyable(),
                        TextEntry::make('total')->money(),
                        TextEntry::make('notes')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
