<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use App\Services\CustomerNotifier;
use Illuminate\Console\Command;

/**
 * Emails customers whose paid invitation expires within --days (default 14), once per expiry date
 * (invitations.expiry_reminded_at; an «extend» add-on clears it). Scheduled daily in routes/console.php.
 */
class RemindExpiringInvitations extends Command
{
    protected $signature = 'invitations:remind-expiring {--days=14 : Remind this many days before expiry}';

    protected $description = 'Email customers whose invitation expires soon, offering the extend add-on';

    public function handle(CustomerNotifier $notifier): int
    {
        $sent = 0;

        Invitation::query()
            ->with(['customer', 'order', 'template'])
            ->whereNull('expiry_reminded_at')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays((int) $this->option('days'))])
            ->chunkById(100, function ($invitations) use ($notifier, &$sent) {
                foreach ($invitations as $invitation) {
                    if (! $invitation->isPaid()) {
                        continue;
                    }
                    if ($notifier->invitationExpiring($invitation)) {
                        $invitation->forceFill(['expiry_reminded_at' => now()])->saveQuietly();
                        $sent++;
                    }
                }
            });

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}
