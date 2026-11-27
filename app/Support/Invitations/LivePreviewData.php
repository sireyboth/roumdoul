<?php

namespace App\Support\Invitations;

use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Everything the editor's LIVE PREVIEW needs to know about one invitation, in one place.
 *
 * The preview (livewire/pages/dashboard/partials/live-preview.blade.php) is one invitation-shaped
 * layout shared by every template (partials/live-preview-page.blade.php), and the small preview
 * components (resources/views/components/preview/) ask this object about each field:
 *   has()      — does this template use the field, and does the plan include it? (if not: not drawn)
 *   value()    — the text the page starts with (the form's current value)
 *   label()    — the field's name, shown in grey while it is empty
 *   photoUrl() — the photo a photo slot starts with
 *   switchOn() — is an on/off switch on? (Latin names, countdown, RSVP)
 */
class LivePreviewData
{
    /** The fields this template's editor shows (the admin's field list). */
    private array $templateFields;

    /** The field catalog with this template's labels, in the customer's language. */
    private array $catalog;

    public function __construct(
        private Invitation $invitation,
        private array $fieldValues,
        private array $imageUploads,
    ) {
        $this->templateFields = $invitation->template->editorFields();
        $this->catalog = $invitation->template->localizedFields();
    }

    /** True when this template uses the field AND the plan includes it (otherwise guests never see it). */
    public function has(string $key): bool
    {
        if (! in_array($key, $this->templateFields, true)) {
            return false;
        }

        return $this->invitation->fieldUnlocked($key);
    }

    /** True when at least one of the fields is used (see has()). */
    public function hasAny(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }

        return false;
    }

    /** The field's name in the customer's language, e.g. «Groom's name». */
    public function label(string $key): string
    {
        return $this->catalog[$key]['label'] ?? $key;
    }

    /** The field's current text ('' when empty or not text). */
    public function value(string $key): string
    {
        $value = $this->fieldValues[$key] ?? '';

        if (! is_scalar($value)) {
            return '';
        }

        return trim((string) $value);
    }

    /** How the script formats the value: 'date' (date / date + time), 'time', or plain 'text'. */
    public function kind(string $key): string
    {
        $type = InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? 'text';

        if ($type === 'datetime' || $type === 'date') {
            return 'date';
        }
        if ($type === 'time') {
            return 'time';
        }

        return 'text';
    }

    /** True for the Latin-letter name fields, which follow the «Latin names» switch. */
    public function isLatinName(string $key): bool
    {
        return in_array($key, InvitationTemplate::LATIN_NAME_FIELDS, true);
    }

    /** The picture a photo slot starts with: a photo picked but not saved yet, else the saved one. */
    public function photoUrl(string $key): ?string
    {
        $picked = $this->imageUploads[$key] ?? null;
        if ($picked && method_exists($picked, 'isPreviewable') && $picked->isPreviewable()) {
            return $picked->temporaryUrl();
        }

        $saved = $this->fieldValues[$key] ?? null;
        if (is_string($saved) && $saved !== '') {
            return Storage::disk('s3')->url($saved);
        }

        return null;
    }

    /**
     * Is an on/off switch on? The «Latin names» switch is on unless turned off; the countdown and
     * RSVP switches are off until ticked.
     */
    public function switchOn(string $key): bool
    {
        if ($key === InvitationTemplate::LATIN_NAMES_SWITCH) {
            return InvitationTemplate::showsLatinNames($this->fieldValues);
        }

        return (bool) ($this->fieldValues[$key] ?? false);
    }
}
