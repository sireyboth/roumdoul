<?php

namespace App\Filament\Resources\Customers\Actions;

use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    // The account's password is never displayed or editable — this is the only way an
    // admin can change it. It generates a fresh random password, saves it hashed, and
    // hands the plain value to the admin exactly once via a persistent notification,
    // so they can relay it to the customer themselves (e.g. over Telegram/phone) —
    // there's no reliable transactional email sending configured in this project yet.
    public static function make(): Action
    {
        return Action::make('resetPassword')
            ->label('Reset password')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Reset customer password?')
            ->modalDescription('This immediately replaces their current password. Share the new one with them yourself — it will only be shown once, right here.')
            ->action(function (Customer $record): void {
                $newPassword = Str::password(12, symbols: false);

                $record->update(['password' => $newPassword]);

                Notification::make()
                    ->title('Password reset')
                    ->body("New password for {$record->email}: {$newPassword}")
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
