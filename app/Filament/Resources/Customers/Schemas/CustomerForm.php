<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Phone / Telegram')
                    ->tel()
                    ->maxLength(50),
                DateTimePicker::make('email_verified_at')
                    ->label('Email verified at'),
                // No password field here on purpose — the account's password is never
                // shown or edited directly. Use the "Reset password" action instead,
                // which generates a fresh one and hands it to you once, out of band.
            ]);
    }
}
