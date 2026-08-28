<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function sendNewOrder(Order $order): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.orders_chat_id');

        if (! $botToken || ! $chatId) {
            return;
        }

        $esc = fn (?string $value) => htmlspecialchars((string) $value, ENT_QUOTES);

        $lines = [
            "🛒 <b>New order — {$esc($order->order_number)}</b>",
            '',
            "👤 {$esc($order->customer_name)}",
            "📞 {$esc($order->customer_phone)}",
            "✉️ {$esc($order->customer_email)}",
            '',
        ];

        foreach ($order->items as $item) {
            $plan = $item->plan_label_snapshot ? ' ('.$esc($item->plan_label_snapshot).')' : '';
            $lines[] = "• {$esc($item->service_name_snapshot)}{$plan} × {$item->quantity} — \${$item->line_total}";
        }

        $lines[] = '';
        if ($order->discount_amount > 0) {
            $lines[] = "🏷️ Promo {$esc($order->promo_code)}: -\${$order->discount_amount}";
        }
        $lines[] = "💰 Total: \${$order->total}";
        if ($order->notes) {
            $lines[] = '📝 Notes: '.$esc($order->notes);
        }

        $this->send(implode("\n", $lines));
    }

    protected function send(string $text): void
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.orders_chat_id');

        try {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ])->throw();
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed: '.$e->getMessage());
        }
    }
}
