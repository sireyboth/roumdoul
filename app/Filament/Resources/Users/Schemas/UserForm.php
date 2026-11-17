<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public const ROLES = [
        'admin' => 'Admin',
        'staff' => 'Staff',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Account')
                    ->icon(Heroicon::OutlinedUser)
                    ->description('People who can log in to this admin panel.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('role')
                            ->options(self::ROLES)
                            ->required()
                            ->default('staff')
                            ->helperText('Admin can do everything. Staff handles orders and content.'),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                            ->visibleOn('create')
                            ->helperText('The new admin/staff member should change this after their first login.'),
                    ]),
            ]);
    }
}
