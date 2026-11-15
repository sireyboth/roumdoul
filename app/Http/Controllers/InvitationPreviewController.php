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

        if (ChristmasTemplate::is($invitation->template->view)) {
            return $this->christmas($request, $invitation, 'cover');
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

    /** Page 2 (event details) of the two-page Christmas template. */
    public function details(Request $request, Invitation $invitation)
    {
        abort_unless($invitation->customer_id === Auth::guard('customer')->id(), 403);
        abort_unless($invitation->isPaid(), 403);
        abort_unless(ChristmasTemplate::is($invitation->template->view), 404);

        return $this->christmas($request, $invitation, 'details');
    }

    /**
     * The Christmas template is English-only. ?editor=1 is the dashboard editor's live preview
     * iframe: the guest name comes from the editor's own (unsaved) "Guest name" box, and the
     * details come from the editor's form (postMessage), so page 2 never bounces back there even
     * before anything is saved. The flag stays on both page URLs so the iframe keeps it.
     */
    private function christmas(Request $request, Invitation $invitation, string $page)
    {
        $editor = $request->boolean('editor');
        $fields = $invitation->field_values ?? [];
        $query = $editor ? ['editor' => 1] : [];
        $coverUrl = route('dashboard.invitations.preview', ['invitation' => $invitation, ...$query]);

        if ($page === 'details' && ! $editor && ! ChristmasTemplate::hasDetails($fields)) {
            return redirect($coverUrl);
        }

        return view('invitations.show', [
            'view' => $invitation->template->view,
            'recipientName' => $editor ? '' : ChristmasTemplate::t('page.honored_guest'),
            'recipient' => null,
            'fields' => $fields,
            'invitation' => $invitation,
            'editorPreview' => $editor,
            ...ChristmasTemplate::pageData($page, $coverUrl, route('dashboard.invitations.preview.details', ['invitation' => $invitation, ...$query])),
        ]);
    }
}
