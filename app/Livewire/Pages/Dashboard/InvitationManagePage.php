<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Invitation;
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

    public $coverImageUpload = null;

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

        if ($this->coverImageUpload) {
            $this->fieldValues['cover_image'] = $this->coverImageUpload->store('invitations/'.$this->invitation->id, 's3');
            $this->coverImageUpload = null;
        }

        $this->invitation->update(['field_values' => $this->fieldValues]);
        $this->saved = true;
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
