<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\Support\Invitations\ChristmasTemplate;

class InvitationShowController extends Controller
{
    public function __invoke(InvitationRecipient $recipient)
    {
        return $this->render($recipient, 'cover');
    }

    /** Page 2 of the two-page Christmas template; other templates are one page. */
    public function details(InvitationRecipient $recipient)
    {
        return $this->render($recipient, 'details');
    }

    /** Old long link /invite/{slug}/{code}: send the guest to the short one. */
    public function legacy(Invitation $invitation, InvitationRecipient $recipient)
    {
        abort_unless($recipient->invitation_id === $invitation->id, 404);

        return redirect()->route('invitation.show', $recipient, 301);
    }

    public function legacyDetails(Invitation $invitation, InvitationRecipient $recipient)
    {
        abort_unless($recipient->invitation_id === $invitation->id, 404);

        return redirect()->route('invitation.show.details', $recipient, 301);
    }

    private function render(InvitationRecipient $recipient, string $page)
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

        $isChristmas = ChristmasTemplate::is($invitation->template->view);
        $fields = $invitation->field_values ?? [];
        abort_if($page === 'details' && ! $isChristmas, 404);

        $coverUrl = route('invitation.show', $recipient);
        if ($page === 'details' && ! ChristmasTemplate::hasDetails($fields)) {
            return redirect($coverUrl);
        }

        if (! $recipient->viewed_at) {
            $recipient->update(['viewed_at' => now()]);
        }

        return view('invitations.show', [
            'view' => $invitation->template->view,
            'recipientName' => $recipient->recipient_name,
            'recipient' => $recipient,
            'fields' => $fields,
            'invitation' => $invitation,
            ...($isChristmas ? ChristmasTemplate::pageData($page, $coverUrl, route('invitation.show.details', $recipient)) : []),
        ]);
    }
}
