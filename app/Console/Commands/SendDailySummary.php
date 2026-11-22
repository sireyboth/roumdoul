<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use App\Support\Analytics\DailySummary;
use Illuminate\Console\Command;

/** The morning business summary to the owner's Telegram (scheduled in routes/console.php). */
class SendDailySummary extends Command
{
    protected $signature = 'report:daily {--print : Show the message here instead of sending it}';

    protected $description = 'Send yesterday\'s business summary (revenue, orders, visitors, to-dos) to Telegram';

    public function handle(DailySummary $summary, TelegramNotifier $telegram): int
    {
        $message = $summary->message();

        if ($this->option('print')) {
            $this->line(html_entity_decode(strip_tags($message), ENT_QUOTES));

            return self::SUCCESS;
        }

        if (! $telegram->sendDailySummary($message)) {
            $this->warn('Not sent: set TELEGRAM_BOT_TOKEN and TELEGRAM_ORDERS_CHAT_ID (or TELEGRAM_SUMMARY_CHAT_ID) in .env.');

            return self::FAILURE;
        }

        $this->info('Daily summary sent to Telegram.');

        return self::SUCCESS;
    }
}
