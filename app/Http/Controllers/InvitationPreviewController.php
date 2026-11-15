<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Models\Invitation;
use App\Support\Invitations\ChristmasTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the owner see their invitation exactly as recipients will (real saved field values,
 * real plan gating) without needing a recipient link — and without marking any recipient
 * as viewed, which opening one of their real links would do.
 */
class InvitationPreviewController extends Controller
{
    public function __invoke(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($invitation->isPaid(), 403);

        // The Christmas template is English-only. ?editor=1 is the dashboard editor's live preview
        // iframe: the guest name comes from the editor's own (unsaved) "Guest name" box instead.
        if (ChristmasTemplate::is($invitation->template->view)) {
            $editor = $request->boolean('editor');

            return view('invitations.show', [
                'view' => $invitation->template->view,
                'recipientName' => $editor ? '' : ChristmasTemplate::t('page.honored_guest'),
                'recipient' => null,
                'fields' => $invitation->field_values ?? [],
                'invitation' => $invitation,
                'editorPreview' => $editor,
            ]);
        }

        return view('invitations.show', [
            'view' => $invitation->template->view,
            // Placeholder for "this guest's name". The page itself is forced to Khmer
            // (guest.locale), so read the owner's own site language from the session.
            'recipientName' => __('Honored guest', locale: session(SetLocale::SESSION_KEY, SetLocale::DEFAULT)),
            'recipient' => null,
            'fields' => $invitation->field_values ?? [],
            'invitation' => $invitation,
        ]);
    }
}
