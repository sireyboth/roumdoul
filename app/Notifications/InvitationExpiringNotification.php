<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * «Your invitation expires soon» — sent once per expiry date by `invitations:remind-expiring`
 * (daily, routes/console.php), linking to the invitation where the «extend» add-on is offered.
 */
class InvitationExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public Invitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inv = $this->invitation->loadMissing('template');
        $days = max(0, (int) now()->startOfDay()->diffInDays($inv->expires_at->copy()->startOfDay()));

        return (new MailMessage)
            ->subject(__('Your invitation expires in :days days', ['days' => $days]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('there')]))
            ->line(__('Your «:template» invitation will stop working on :date. After that, your guests\' links won\'t open any more.', [
                'template' => $inv->template?->name ?? __('Invitation'),
                'date' => $inv->expires_at->format('d M Y'),
            ]))
            ->line(__('You can keep it online longer — open your invitation and choose «Extend time».'))
            ->action(__('Open my invitation'), route('dashboard.invitations.show', $inv))
            ->salutation(__('— ROUMDOUL'));
    }
}
