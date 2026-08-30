<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use Illuminate\Http\Request;

class InvitationRsvpController extends Controller
{
    public function __invoke(Request $request, Invitation $invitation, InvitationRecipient $recipient)
    {
        abort_unless($recipient->invitation_id === $invitation->id, 404);
        abort_if($invitation->isExpired(), 410);

        $validated = $request->validate([
            'status' => ['required', 'in:yes,no'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $recipient->update([
            'rsvp_status' => $validated['status'],
            'rsvp_note' => $validated['note'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }
}
