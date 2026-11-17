<?php

namespace App\Http\Controllers;

use App\Models\InvitationRecipient;
use App\Rules\NoProfanity;
use App\Services\ContentModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class InvitationRsvpController extends Controller
{
    public function __invoke(Request $request, InvitationRecipient $recipient, ContentModerator $moderator)
    {
        $invitation = $recipient->invitation;
        abort_if($invitation === null, 404);
        abort_if($recipient->revoked_at !== null, 404);
        abort_if($invitation->isExpired(), 410);
        // The guest page hides the buttons in both cases; this stops a direct request too.
        abort_unless($invitation->isPaid(), 404);
        abort_unless($invitation->fieldUnlocked('rsvp_enabled'), 403);

        // Per-link cooldown: a leaked or abused link can't be used to flood the customer's
        // dashboard, whatever IP the requests come from.
        $limiterKey = 'rsvp-recipient:'.$recipient->id;
        $max = (int) config('moderation.rsvp_updates_per_window', 6);

        if (RateLimiter::tooManyAttempts($limiterKey, $max)) {
            return response()->json(['ok' => false, 'retry_after' => RateLimiter::availableIn($limiterKey)], 429);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:yes,no'],
            'note' => ['nullable', 'string', 'max:500', new NoProfanity],
        ]);

        RateLimiter::hit($limiterKey, (int) config('moderation.rsvp_window_seconds', 600));

        $note = isset($validated['note']) ? $moderator->sanitize($validated['note'], 500) : null;
        $note = $note === '' ? null : $note;

        // Same answer + same note again is a harmless duplicate: succeed without rewriting.
        $isDuplicate = $recipient->rsvp_status === $validated['status']
            && ($recipient->rsvp_note ?? null) === $note;

        if (! $isDuplicate) {
            $recipient->update([
                'rsvp_status' => $validated['status'],
                'rsvp_note' => $note,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
