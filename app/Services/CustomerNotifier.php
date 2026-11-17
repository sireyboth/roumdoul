<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invitation;
use App\Models\Order;
use App\Notifications\InvitationExpiringNotification;
use App\Notifications\OrderPaidNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Messages to customers. A failed email must never break marking an order paid or the daily reminder
 * run, so every send is wrapped in try/catch and only logged.
 */
class CustomerNotifier
{
    public function orderPaid(Order $order): void
    {
        $this->send($order->customer, $order->customer_email, new OrderPaidNotification($order), "order {$order->order_number}");
    }

    public function invitationExpiring(Invitation $invitation): bool
    {
        return $this->send($invitation->customer, null, new InvitationExpiringNotification($invitation), "invitation {$invitation->id}");
    }

    private function send(?Customer $customer, ?string $fallbackEmail, BaseNotification $notification, string $what): bool
    {
        try {
            if ($customer?->email) {
                $customer->notify($notification);
            } elseif ($fallbackEmail) {
                Notification::route('mail', $fallbackEmail)->notify($notification);
            } else {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("Customer notification failed for {$what}: {$e->getMessage()}");

            return false;
        }
    }
}
