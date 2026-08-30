<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationRecipient;

class InvitationShowController extends Controller
{
    public function __invoke(Invitation $invitation, InvitationRecipient $recipient)
    {
        abort_unless($recipient->invitation_id === $invitation->id, 404);

        if ($invitation->isExpired()) {
            return response()->view('invitations.expired', status: 410);
        }

        if (! $recipient->viewed_at) {
            $recipient->update(['viewed_at' => now()]);
        }

        return view('invitations.show', [
            'view' => $invitation->template->view,
            'recipientName' => $recipient->recipient_name,
            'fields' => $invitation->field_values ?? [],
            'invitation' => $invitation,
        ]);
    }
}
