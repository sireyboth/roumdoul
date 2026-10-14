<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.workspace')]
#[Title('គ្រប់គ្រងកម្មវត្ថុអញ្ជើញ | ROUMDOUL')]
class InvitationManagePage extends Component
{
    use WithFileUploads {
        _uploadErrored as livewireUploadErrored;
    }

    /**
     * Which FIELD_CATALOG field types are edited under which sidebar section. A section only
     * appears in the sidebar if this invitation's template actually uses one of its types.
     */
    public const SECTION_FIELD_TYPES = [
        'details' => ['text', 'textarea', 'datetime', 'color', 'boolean'],
        'media' => ['image', 'gallery'],
        'program' => ['schedule', 'story'],
    ];

    /** Per-photo upload cap (KB); keep in step with livewire.temporary_file_upload.rules. */
    public const MAX_UPLOAD_KB = 20480;

    public const MAX_GIF_KB = 5120;

    public const MAX_GALLERY_PHOTOS = 20;

    #[Url(as: 'tab')]
    public string $section = 'overview';

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

        $invitation->load(['template', 'recipients', 'order', 'plan']);
        $this->invitation = $invitation;
        $this->fieldValues = $invitation->field_values ?? [];

        if (! in_array($this->section, $this->availableSections(), true)) {
            $this->section = 'overview';
        }
    }

    public function showSection(string $section): void
    {
        if (in_array($section, $this->availableSections(), true)) {
            $this->section = $section;
            $this->saved = false;
        }
    }

    /** Sidebar sections in display order, skipping field sections this template has nothing for. */
    public function availableSections(): array
    {
        $usedTypes = collect($this->invitation->template->fields ?? [])
            ->map(fn (string $key) => InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null)
            ->filter();

        $fieldSections = collect(self::SECTION_FIELD_TYPES)
            ->filter(fn (array $types) => $usedTypes->intersect($types)->isNotEmpty())
            ->keys()
            ->all();

        $sections = ['overview', ...$fieldSections, 'guests'];

        if (in_array('rsvp_enabled', $this->invitation->template->fields ?? [], true)) {
            $sections[] = 'responses';
        }

        return $sections;
    }

    /** This template's field keys that are edited under the given sidebar section. */
    public function sectionFields(string $section): array
    {
        $types = self::SECTION_FIELD_TYPES[$section] ?? [];

        return collect($this->invitation->template->fields ?? [])
            ->filter(fn (string $key) => in_array(InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null, $types, true))
            ->values()
            ->all();
    }

    public function save(): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        if ($rules = $this->saveRules()) {
            $this->validate($rules);
        }

        $optimizer = app(ImageOptimizer::class);
        $directory = 'invitations/'.$this->invitation->id;

        foreach ($this->imageUploads as $key => $file) {
            if ($file) {
                $this->fieldValues[$key] = $optimizer->store($file, $directory);
            }
        }
        $this->imageUploads = [];

        foreach ($this->galleryUploads as $key => $files) {
            if (! empty($files)) {
                $stored = collect($files)->map(fn ($file) => $optimizer->store($file, $directory));
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
                $type === 'story' => ['nullable', 'array', 'max:8'],
                $type === 'gallery' => ['nullable', 'array', 'max:'.self::MAX_GALLERY_PHOTOS],
                default => null,
            };

            if ($type === 'schedule') {
                $rules["fieldValues.{$key}.*.time"] = ['nullable', 'string', 'max:50'];
                $rules["fieldValues.{$key}.*.label"] = ['nullable', 'string', 'max:200'];
            }

            if ($type === 'story') {
                $rules["fieldValues.{$key}.*.title"] = ['nullable', 'string', 'max:120'];
                $rules["fieldValues.{$key}.*.text"] = ['nullable', 'string', 'max:600'];
            }
        }

        return array_filter([...$rules, ...$this->uploadRules()]);
    }

    /**
     * Rules for pending photo uploads. Photos may be up to MAX_UPLOAD_KB (a full-resolution
     * phone photo) because ImageOptimizer shrinks them before storing; GIFs are stored as-is
     * to keep their animation, so they keep a tighter cap. A gallery's saved photos plus the
     * new ones may not exceed MAX_GALLERY_PHOTOS.
     */
    protected function uploadRules(): array
    {
        $photo = ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:'.self::MAX_UPLOAD_KB, 'dimensions:max_width=8000,max_height=8000',
            function (string $attribute, $file, \Closure $fail) {
                if ($file->getMimeType() === 'image/gif' && $file->getSize() > self::MAX_GIF_KB * 1024) {
                    $fail('GIFs can be at most '.(self::MAX_GIF_KB / 1024).' MB.');
                }
            }];
        $rules = [];

        foreach ($this->imageUploads as $key => $file) {
            if ($file) {
                $rules["imageUploads.{$key}"] = $photo;
            }
        }

        foreach ($this->galleryUploads as $key => $files) {
            if (! empty($files)) {
                $room = max(0, self::MAX_GALLERY_PHOTOS - count($this->fieldValues[$key] ?? []));
                $rules["galleryUploads.{$key}"] = ['array', 'max:'.$room];
                $rules["galleryUploads.{$key}.*"] = $photo;
            }
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'imageUploads.*.max' => 'Each photo can be at most '.(self::MAX_UPLOAD_KB / 1024).' MB.',
            'galleryUploads.*.*.max' => 'Each photo can be at most '.(self::MAX_UPLOAD_KB / 1024).' MB.',
            'galleryUploads.*.max' => 'The gallery holds up to '.self::MAX_GALLERY_PHOTOS.' photos — remove some first.',
            '*.dimensions' => 'This photo is too large (max 8000 × 8000 pixels).',
        ];
    }

    /**
     * Livewire rejects files over livewire.temporary_file_upload's cap (or a dropped
     * connection) before updated() ever runs, with a raw "The imageUploads.cover_image field
     * must not be greater than 20480 kilobytes." — swap that for a message a customer can read.
     */
    public function _uploadErrored($name, $errorsInJson, $isMultiple)
    {
        try {
            $this->livewireUploadErrored($name, $errorsInJson, $isMultiple);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                $name => "This photo couldn't be uploaded. Photos can be at most ".(self::MAX_UPLOAD_KB / 1024).' MB each.',
            ]);
        }
    }

    /**
     * Check photos as soon as they're picked, so the customer sees a problem before pressing
     * Save; a rejected pick is dropped so it doesn't linger as a preview.
     */
    public function updated(string $property): void
    {
        if (! preg_match('/^(imageUploads|galleryUploads)\.([^.]+)/', $property, $m)) {
            return;
        }

        try {
            $this->validate($this->uploadRules());
        } catch (ValidationException $e) {
            unset($this->{$m[1]}[$m[2]]);

            throw $e;
        }
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

    public function addStoryItem(string $key): void
    {
        $this->fieldValues[$key][] = ['title' => '', 'text' => ''];
    }

    public function removeStoryItem(string $key, int $index): void
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
        $recipients = $this->invitation->recipients;
        $templateFields = $this->invitation->template->fields ?? [];

        return view('livewire.pages.dashboard.invitation-manage-page', [
            'fieldCatalog' => InvitationTemplate::FIELD_CATALOG,
            'sections' => $this->availableSections(),
            'stats' => [
                'recipients' => $recipients->count(),
                'viewed' => $recipients->whereNotNull('viewed_at')->count(),
                'attending' => $recipients->where('rsvp_status', 'yes')->count(),
                'declined' => $recipients->where('rsvp_status', 'no')->count(),
                'fields' => count($templateFields),
                'filled' => collect($templateFields)
                    ->filter(fn (string $key) => filled($this->invitation->field_values[$key] ?? null))
                    ->count(),
            ],
        ]);
    }
}
