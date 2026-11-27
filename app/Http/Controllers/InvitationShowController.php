<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationRecipient;

class InvitationShowController extends Controller
{
    public function __invoke(InvitationRecipient $recipient)
    {
        $invitation = $recipient->invitation;
        abort_if($invitation === null, 404);

        if ($recipient->revoked_at !== null) {
            return response()->view('invitations.expired', status: 410);
        }

        // An order moved back from paid (refund, mistake) takes the guest links down with it.
        if ($invitation->isExpired() || ! $invitation->isPaid()) {
            return response()->view('invitations.expired', status: 410);
        }

        // A template whose design file was removed from the site (e.g. the old Christmas template):
        // show the "no longer available" page instead of an error.
        if (! view()->exists($invitation->template->view)) {
            return response()->view('invitations.expired', status: 410);
        }

        // guest stats: first open, how many visits (a refresh within 30 min isn't a new visit), latest visit
        if (! $recipient->last_viewed_at || $recipient->last_viewed_at->lt(now()->subMinutes(30))) {
            $recipient->forceFill([
                'viewed_at' => $recipient->viewed_at ?? now(),
                'last_viewed_at' => now(),
                'open_count' => $recipient->open_count + 1,
            ])->saveQuietly();
        }

        return view('invitations.show', [
            'view' => $invitation->template->view,
            'recipientName' => $recipient->recipient_name,
            'recipient' => $recipient,
            'fields' => $invitation->field_values ?? [],
            'invitation' => $invitation,
        ]);
    }

    /** Old long link /invite/{slug}/{code}: send the guest to the short one. */
    public function legacy(Invitation $invitation, InvitationRecipient $recipient)
    {
        abort_unless($recipient->invitation_id === $invitation->id, 404);

        return redirect()->route('invitation.show', $recipient, 301);
    }
}
