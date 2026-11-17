<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * «Your payment is confirmed» — sent once when an admin marks an order paid (Order::booted() →
 * CustomerNotifier). A purchase links to the new invitation(s); an add-on order says what was added.
 */
class OrderPaidNotification extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing(['items', 'invitations.template', 'invitation.template']);
        $mail = (new MailMessage)
            ->subject(__('Payment confirmed — order :number', ['number' => $order->order_number]))
            ->greeting(__('Hello :name,', ['name' => $order->customer_name ?: __('there')]))
            ->line(__('We received your payment of $:amount for order :number. Thank you!', [
                'amount' => number_format((float) ($order->amount_received ?? $order->total), 2),
                'number' => $order->order_number,
            ]));

        foreach ($order->items as $item) {
            $mail->line('• '.$item->service_name_snapshot.($item->quantity > 1 && ! $item->addon_type ? ' × '.$item->quantity : ''));
        }

        $invitation = $order->isAddon() ? $order->invitation : $order->invitations->first();
        if ($invitation) {
            $mail->line($order->isAddon()
                ? __('Your add-ons are now active on your invitation.')
                : __('Your invitation is ready. Open it to fill in your details and add your guests.'));
            $mail->action(__('Open my invitation'), route('dashboard.invitations.show', $invitation));
        } else {
            $mail->line(__('Our team will deliver your order shortly.'));
            $mail->action(__('View my order'), route('dashboard.orders.show', $order));
        }

        return $mail->salutation(__('— ROUMDOUL'));
    }
}
