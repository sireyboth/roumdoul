<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Invitation;
use App\Models\InvitationTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('គ្រប់គ្រងកម្មវត្ថុអញ្ជើញ | ROUMDOUL')]
class InvitationManagePage extends Component
{
    use WithFileUploads;

    public Invitation $invitation;

    public array $fieldValues = [];

    /** Pending single-image uploads, keyed by field key (e.g. 'cover_image', 'qr_code'). */
    public array $imageUploads = [];

    /** Pending multi-image uploads, keyed by gallery field key (e.g. 'photo_gallery'). */
    public array $galleryUploads = [];

    public string $newRecipientName = '';

    public bool $saved = false;

    public function mount(Invitation $invitation): void
    {
        abort_unless($invitation->customer_id === Auth::guard('customer')->id(), 403);

        $invitation->load(['template', 'recipients', 'order']);
        $this->invitation = $invitation;
        $this->fieldValues = $invitation->field_values ?? [];
    }

    public function save(): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $this->validate($this->saveRules());

        foreach ($this->imageUploads as $key => $file) {
            if ($file) {
                $this->fieldValues[$key] = $file->store('invitations/'.$this->invitation->id, 's3');
            }
        }
        $this->imageUploads = [];

        foreach ($this->galleryUploads as $key => $files) {
            if (! empty($files)) {
                $stored = collect($files)->map(fn ($file) => $file->store('invitations/'.$this->invitation->id, 's3'));
                $this->fieldValues[$key] = collect($this->fieldValues[$key] ?? [])->merge($stored)->values()->all();
            }
        }
        $this->galleryUploads = [];

        $this->invitation->update(['field_values' => $this->fieldValues]);
        $this->saved = true;
    }

    /**
     * Validation rules built dynamically from this invitation's template fields, so every
     * field a customer can actually fill in is checked server-side — not just whatever the
     * dashboard form happens to render client-side (client-side constraints like the file
     * input's accept="image/*" are trivially bypassed). URL-typed fields (music_url,
     * cta_url) are restricted to http/https so a recipient clicking the button/link on their
     * invitation can never be sent to a javascript:/data: URI — that's stored XSS against
     * the recipient, not the customer, since these render as real anchor hrefs on the
     * public invitation page. Image uploads are restricted to real image mime types (no
     * svg — inline <img> won't execute embedded script, but a directly-opened svg URL
     * would) so a customer can't use "cover image"/"gallery" to store an executable/script
     * file on the invitation's storage path.
     */
    protected function saveRules(): array
    {
        $catalog = InvitationTemplate::FIELD_CATALOG;
        $urlFields = ['music_url', 'cta_url'];
        $rules = [];

        foreach ($this->invitation->template->fields ?? [] as $key) {
            $type = $catalog[$key]['type'] ?? null;

            $rules["fieldValues.{$key}"] = match (true) {
                in_array($key, $urlFields, true) => ['nullable', 'url', 'starts_with:https://,http://', 'max:2048'],
                $type === 'textarea' => ['nullable', 'string', 'max:2000'],
                $type === 'text' => ['nullable', 'string', 'max:255'],
                $type === 'boolean' => ['nullable', 'boolean'],
                $type === 'datetime' => ['nullable', 'date'],
                $type === 'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
                $type === 'schedule' => ['nullable', 'array', 'max:20'],
                $type === 'gallery' => ['nullable', 'array', 'max:20'],
                default => null,
            };

            if ($type === 'schedule') {
                $rules["fieldValues.{$key}.*.time"] = ['nullable', 'string', 'max:50'];
                $rules["fieldValues.{$key}.*.label"] = ['nullable', 'string', 'max:200'];
            }
        }

        foreach ($this->imageUploads as $key => $file) {
            if ($file) {
                $rules["imageUploads.{$key}"] = ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'];
            }
        }

        foreach ($this->galleryUploads as $key => $files) {
            if (! empty($files)) {
                $rules["galleryUploads.{$key}.*"] = ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'];
            }
        }

        return array_filter($rules);
    }

    public function removeGalleryImage(string $key, int $index): void
    {
        $images = $this->fieldValues[$key] ?? [];
        unset($images[$index]);
        $this->fieldValues[$key] = array_values($images);
    }

    public function addScheduleItem(string $key): void
    {
        $this->fieldValues[$key][] = ['time' => '', 'label' => ''];
    }

    public function removeScheduleItem(string $key, int $index): void
    {
        $items = $this->fieldValues[$key] ?? [];
        unset($items[$index]);
        $this->fieldValues[$key] = array_values($items);
    }

    public function addRecipient(): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $this->validate(['newRecipientName' => ['required', 'string', 'max:255']]);

        // Locks the invitation row for the duration of the check+create, so two rapid/
        // concurrent submits (double-click, or someone deliberately spamming the request)
        // can't both pass the count check before either has actually inserted a row.
        $created = DB::transaction(function () {
            $invitation = Invitation::whereKey($this->invitation->id)->lockForUpdate()->first();

            if ($invitation->recipients()->count() >= $invitation->max_recipients) {
                return null;
            }

            return $invitation->recipients()->create(['recipient_name' => $this->newRecipientName]);
        });

        if (! $created) {
            $this->addError('newRecipientName', 'You have reached the recipient limit for this plan.');

            return;
        }

        $this->newRecipientName = '';
        $this->invitation->load('recipients');
    }

    public function removeRecipient(int $recipientId): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $this->invitation->recipients()->whereKey($recipientId)->delete();
        $this->invitation->load('recipients');
    }

    public function render()
    {
        return view('livewire.pages.dashboard.invitation-manage-page', [
            'fieldCatalog' => \App\Models\InvitationTemplate::FIELD_CATALOG,
        ]);
    }
}
