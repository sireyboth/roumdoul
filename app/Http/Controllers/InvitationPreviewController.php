<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the owner see their invitation exactly as recipients will (real saved field values,
 * real plan gating) without needing a recipient link — and without marking any recipient
 * as viewed, which opening one of their real links would do.
 */
class InvitationPreviewController extends Controller
{
    public function __invoke(Invitation $invitation)
    {
        abort_unless($invitation->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($invitation->isPaid(), 403);

        return view('invitations.show', [
            'view' => $invitation->template->view,
            'recipientName' => 'Guest',
            'recipient' => null,
            'fields' => $invitation->field_values ?? [],
            'invitation' => $invitation,
        ]);
    }
}
