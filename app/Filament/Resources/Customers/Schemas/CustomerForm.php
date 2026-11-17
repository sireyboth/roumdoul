<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Contact details')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->description('The password is never shown here. Use «Reset password» to give the customer a new one.')
                    ->columns(2)
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
                            ->label('Email verified at')
                            ->helperText('Empty = the customer has not confirmed their email yet.'),
                        // No password field here on purpose — the account's password is never
                        // shown or edited directly. Use the "Reset password" action instead,
                        // which generates a fresh one and hands it to you once, out of band.
                    ]),
            ]);
    }
}
