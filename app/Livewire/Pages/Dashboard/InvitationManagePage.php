<?php

namespace App\Livewire\Pages\Dashboard;

use App\Models\Invitation;
use App\Models\InvitationTemplate;
use App\Services\ImageOptimizer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * A catalog entry's own `section` overrides this (e.g. schedule_image sits under program).
     */
    public const SECTION_FIELD_TYPES = [
        'details' => ['text', 'textarea', 'datetime', 'color', 'boolean'],
        'media' => ['image', 'gallery'],
        'program' => ['schedule', 'story'],
    ];

    /**
     * How a tab's fields are grouped into cards on the form, in display order — purely layout.
     * A field not listed here lands in a trailing "other" card. `or` groups are alternatives
     * (fill one or the other) and render side by side with an "or" between them; `cols` => 3
     * lets a card of small uploads sit three across when it's wide enough; `wide` gives a card its own row.
     */
    public const FORM_GROUPS = [
        'people' => ['label' => 'អ្នកណាជាម្ចាស់កម្មវិធី', 'icon' => 'users', 'fields' => [
            'sender_name', 'groom_name', 'bride_name', 'celebrant_name', 'turning_age', 'groom_phone', 'bride_phone',
        ]],
        'when_where' => ['label' => 'ពេលណា និងនៅឯណា', 'icon' => 'calendar', 'fields' => [
            'event_date', 'khmer_date', 'venue_name', 'venue_address', 'map_url',
        ]],
        'words' => ['label' => 'ពាក្យពេចន៍លើធៀប', 'icon' => 'pencil', 'fields' => ['headline', 'message']],
        'options' => ['label' => 'មុខងារបើក/បិទ', 'icon' => 'bolt', 'fields' => [
            'rsvp_enabled', 'countdown_enabled', 'gallery_feed',
        ]],
        'style' => ['label' => 'ភ្លេង និងពណ៌', 'icon' => 'paint-brush', 'fields' => ['music_url', 'accent_color']],
        'button' => ['label' => 'ប៊ូតុងផ្ទាល់ខ្លួន', 'icon' => 'link', 'fields' => ['cta_label', 'cta_url']],
        'portraits' => ['label' => 'រូបសំខាន់ៗ', 'icon' => 'photo', 'cols' => 3, 'wide' => true, 'fields' => ['cover_image', 'groom_photo', 'bride_photo']],
        'gallery' => ['label' => 'វិចិត្រសាលរូបថត', 'icon' => 'squares', 'fields' => ['photo_gallery']],
        'gift' => ['label' => 'ចំណងដៃ', 'icon' => 'heart', 'fields' => ['qr_code', 'gift_account']],
        'schedule' => ['label' => 'របៀបវារៈ — វាយបញ្ចូល ឬដាក់រូបភាព', 'icon' => 'clock', 'or' => true, 'fields' => ['event_schedule', 'schedule_image']],
        'story' => ['label' => 'រឿងរ៉ាវរបស់យើង', 'icon' => 'heart', 'fields' => ['story_chapters']],
    ];

    /** Field types/keys that take the full width of a card; everything else sits two per row. */
    protected const FULL_WIDTH_TYPES = ['textarea', 'gallery', 'story'];

    protected const FULL_WIDTH_KEYS = ['venue_address', 'map_url', 'music_url', 'cta_url'];

    /** Cards containing these field types always span the full form width. */
    protected const WIDE_CARD_TYPES = ['schedule', 'story'];

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

    /** Field the help panel opens on when a section is reached from the guide. */
    public ?string $focusField = null;

    public string $newRecipientName = '';

    public bool $saved = false;

    public function mount(Invitation $invitation): void
    {
        abort_unless($invitation->customer_id === Auth::guard('customer')->id(), 403);

        $invitation->load(['template', 'recipients', 'order', 'plan']);
        $this->invitation = $invitation;
        $this->fieldValues = $invitation->field_values ?? [];

        // The feed is the default gallery layout, so an untouched checkbox should show ticked.
        if (in_array('gallery_feed', $invitation->template->fields ?? [], true)) {
            $this->fieldValues += ['gallery_feed' => true];
        }

        if (! in_array($this->section, $this->visibleSections(), true)) {
            $this->section = 'overview';
        }
    }

    public function showSection(string $section): void
    {
        if (in_array($section, $this->visibleSections(), true)) {
            $this->section = $section;
            $this->focusField = null;
            $this->saved = false;
        }
    }

    /** Opens the section a field is edited in with that field focused (used by the guide). */
    public function editField(string $key): void
    {
        $section = self::fieldSection($key);

        if (in_array($key, $this->invitation->template->fields ?? [], true) && in_array($section, $this->visibleSections(), true)) {
            $this->section = $section;
            $this->focusField = $key;
            $this->saved = false;
        }
    }

    /** Sidebar sections in display order, skipping field sections this template has nothing for. */
    public function availableSections(): array
    {
        $usedSections = collect($this->invitation->template->fields ?? [])
            ->map(fn (string $key) => self::fieldSection($key))
            ->filter();

        $fieldSections = collect(self::SECTION_FIELD_TYPES)
            ->keys()
            ->filter(fn (string $section) => $usedSections->contains($section))
            ->values()
            ->all();

        $sections = ['overview', ...$fieldSections, 'guests'];

        if (in_array('rsvp_enabled', $this->invitation->template->fields ?? [], true)) {
            $sections[] = 'responses';
        }

        $sections[] = 'guide';

        return $sections;
    }

    /** Sections the sidebar actually offers right now — only overview and the guide until paid. */
    public function visibleSections(): array
    {
        return $this->invitation->isPaid() ? $this->availableSections() : ['overview', 'guide'];
    }

    /** This template's field keys that are edited under the given sidebar section. */
    public function sectionFields(string $section): array
    {
        return collect($this->invitation->template->fields ?? [])
            ->filter(fn (string $key) => self::fieldSection($key) === $section)
            ->values()
            ->all();
    }

    /**
     * A section's fields split into the FORM_GROUPS cards they render in, skipping empty cards:
     * [groupKey => ['label', 'icon', 'or', 'fields' => [...]]], with leftovers under 'other'.
     */
    public function sectionGroups(string $section): array
    {
        $fields = $this->sectionFields($section);
        $groups = [];

        foreach (self::FORM_GROUPS as $name => $group) {
            $keys = array_values(array_intersect($group['fields'], $fields));
            if ($keys) {
                $groups[$name] = ['fields' => $keys, 'or' => ($group['or'] ?? false) && count($keys) > 1] + $group;
            }
        }

        $grouped = collect($groups)->pluck('fields')->flatten()->all();
        if ($rest = array_values(array_diff($fields, $grouped))) {
            $groups['other'] = ['label' => 'ផ្សេងៗ', 'icon' => 'sparkles', 'or' => false, 'fields' => $rest];
        }

        foreach ($groups as $name => $group) {
            $groups[$name]['full'] = $this->fullWidthFields($group);
            $groups[$name]['wide'] = $group['or'] || ($group['wide'] ?? false) || collect($group['fields'])
                ->contains(fn ($key) => in_array(InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null, self::WIDE_CARD_TYPES, true));
            $groups[$name]['weight'] = $this->groupWeight($groups[$name]);
        }

        return $groups;
    }

    /**
     * A section's cards arranged for wide screens, so there are no empty gaps: wide cards
     * (schedule, story, either/or pairs) get a row to themselves, and each run of ordinary
     * cards between them is split into two independently stacked columns balanced by
     * estimated height. Returns segments: ['wide' => groupKey] or ['columns' => [[keys], [keys]]].
     */
    public function sectionLayout(string $section): array
    {
        $groups = $this->sectionGroups($section);
        $segments = [];
        $run = [];

        $flush = function () use (&$run, &$segments, $groups) {
            if (count($run) === 1) {
                $segments[] = ['wide' => $run[0]];
            } elseif ($run) {
                $columns = [[], []];
                $heights = [0, 0];
                foreach ($run as $name) {
                    $side = $heights[0] <= $heights[1] ? 0 : 1;
                    $columns[$side][] = $name;
                    $heights[$side] += $groups[$name]['weight'];
                }
                $segments[] = ['columns' => $columns];
            }
            $run = [];
        };

        foreach ($groups as $name => $group) {
            if ($group['wide']) {
                $flush();
                $segments[] = ['wide' => $name];
            } else {
                $run[] = $name;
            }
        }
        $flush();

        return ['groups' => $groups, 'segments' => $segments];
    }

    /** Fields in a card that take its whole row: long inputs, and any short one left alone in its row. */
    protected function fullWidthFields(array $group): array
    {
        $fields = $group['fields'];
        if ($group['or'] || count($fields) === 1) {
            return $fields;
        }

        $full = [];
        $col = 0;
        foreach ($fields as $i => $key) {
            if (self::fieldIsFullWidth($key)) {
                $full[] = $key;
                $col = 0;

                continue;
            }
            // a lone photo stays half width (stretched it's a huge empty drop zone), and
            // three-across cards don't stretch at all
            $isPhoto = (InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null) === 'image';
            $next = $fields[$i + 1] ?? null;
            if (! $isPhoto && ($group['cols'] ?? 2) === 2 && $col === 0 && ($next === null || self::fieldIsFullWidth($next))) {
                $full[] = $key;

                continue;
            }
            $col = 1 - $col;
        }

        return $full;
    }

    /** Rough rendered height of a card, in "input rows", for balancing the two columns. */
    protected function groupWeight(array $group): float
    {
        $height = fn (string $key) => match (InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null) {
            'image' => 3.0, 'gallery', 'textarea' => 2.0, 'boolean' => 0.8, default => 1.0,
        };

        $weight = 1.0; // the card title
        $pending = null;
        foreach ($group['fields'] as $key) {
            if (in_array($key, $group['full'], true)) {
                $weight += $height($key);
            } elseif ($pending === null) {
                $pending = $height($key);
            } else {
                $weight += max($pending, $height($key));
                $pending = null;
            }
        }

        return $weight + ($pending ?? 0);
    }

    /** Whether a field spans the whole card row instead of sharing it with a neighbour. */
    public static function fieldIsFullWidth(string $key): bool
    {
        return in_array($key, self::FULL_WIDTH_KEYS, true)
            || in_array(InvitationTemplate::FIELD_CATALOG[$key]['type'] ?? null, self::FULL_WIDTH_TYPES, true);
    }

    /** The sidebar section a catalog field is edited under: its own `section`, else by its type. */
    public static function fieldSection(string $key): ?string
    {
        $field = InvitationTemplate::FIELD_CATALOG[$key] ?? null;

        if (! $field) {
            return null;
        }

        return $field['section'] ?? (collect(self::SECTION_FIELD_TYPES)
            ->search(fn (array $types) => in_array($field['type'], $types, true)) ?: null);
    }

    public function save(): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        if ($rules = $this->saveRules()) {
            try {
                $this->validate($rules);
            } catch (ValidationException $e) {
                $this->reportInvalidFields($e);

                throw $e;
            }
        }

        // Only keys this invitation's template actually declares may be stored, and uploads for
        // any other key are ignored — fieldValues/imageUploads are client-writable properties.
        $allowedKeys = $this->invitation->template->fields ?? [];
        $this->fieldValues = Arr::only($this->fieldValues, $allowedKeys);
        $this->imageUploads = Arr::only($this->imageUploads, $allowedKeys);
        $this->galleryUploads = Arr::only($this->galleryUploads, $allowedKeys);

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
        $this->dispatch('invitation-saved');
        $this->toast('success', 'បានរក្សាទុក! ភ្ញៀវនឹងឃើញការកែប្រែភ្លាមៗ។');
    }

    /** Shows a toast in the workspace (see resources/js/modules/toast.js). */
    protected function toast(string $type, string $message): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    /**
     * After a failed save: a toast saying how many fields need fixing, and the form jumps to
     * the first one (its sidebar tab too, if it's on another tab).
     */
    protected function reportInvalidFields(ValidationException $e): void
    {
        $keys = collect(array_keys($e->errors()))
            ->map(fn (string $attribute) => explode('.', $attribute)[1] ?? null)
            ->filter()
            ->unique()
            ->values();

        if ($first = $keys->first()) {
            $section = self::fieldSection($first);
            if ($section && $section !== $this->section && in_array($section, $this->visibleSections(), true)) {
                $this->section = $section;
            }
            $this->dispatch('field-error', key: $first);
        }

        $label = $first ? (InvitationTemplate::FIELD_CATALOG[$first]['label'] ?? $first) : null;
        $this->toast('error', $keys->count() > 1
            ? 'មិនទាន់រក្សាទុកទេ — មានប្រអប់ '.$keys->count().' ត្រូវកែ (ចាប់ពី «'.$label.'»)។'
            : 'មិនទាន់រក្សាទុកទេ — សូមកែប្រអប់ «'.$label.'»។');
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
                $type === 'image' => ['nullable', 'string', $this->ownStoragePathRule()],
                default => null,
            };

            if ($type === 'gallery') {
                $rules["fieldValues.{$key}.*"] = ['string', $this->ownStoragePathRule()];
            }

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
     * Stored photo values must be paths this invitation's own upload flow produced
     * (invitations/{id}/{file}) — never an external URL (a tracking pixel for every guest) or
     * another customer's storage key.
     */
    protected function ownStoragePathRule(): \Closure
    {
        $prefix = 'invitations/'.$this->invitation->id.'/';

        return function (string $attribute, mixed $value, \Closure $fail) use ($prefix) {
            if (! is_string($value) || ! str_starts_with($value, $prefix)
                || ! preg_match('#^[A-Za-z0-9._-]+$#', substr($value, strlen($prefix)))) {
                $fail('That photo is not valid. Please upload it again.');
            }
        };
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
            $this->toast('error', 'បង្ហោះរូបមិនបានទេ — រូបនីមួយៗធំបំផុត '.(self::MAX_UPLOAD_KB / 1024).' MB។');

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
            $this->toast('error', collect($e->errors())->flatten()->first() ?? 'រូបនេះប្រើមិនបានទេ។');

            throw $e;
        }

        $this->toast('info', 'រូបរួចរាល់ហើយ — ចុច Save ដើម្បីរក្សាទុក។');
    }

    public function removeGalleryImage(string $key, int $index): void
    {
        $images = $this->fieldValues[$key] ?? [];
        unset($images[$index]);
        $this->fieldValues[$key] = array_values($images);
        $this->toast('info', 'បានដករូបចេញ — ចុច Save ដើម្បីរក្សាទុក។');
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

        try {
            $this->validate(['newRecipientName' => ['required', 'string', 'max:255']]);
        } catch (ValidationException $e) {
            $this->toast('error', trim($this->newRecipientName) === '' ? 'សូមវាយឈ្មោះភ្ញៀវជាមុនសិន។' : 'ឈ្មោះវែងពេក។');

            throw $e;
        }

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
            $this->toast('warning', 'អ្នកបានប្រើភ្ញៀវគ្រប់ '.$this->invitation->max_recipients.' នាក់ក្នុងគម្រោងនេះហើយ។');

            return;
        }

        $this->newRecipientName = '';
        $this->invitation->load('recipients');
        $this->toast('success', 'បានបន្ថែម «'.$created->recipient_name.'» — ចុច Copy link ដើម្បីផ្ញើ។');
    }

    public function removeRecipient(int $recipientId): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $recipient = $this->invitation->recipients()->whereKey($recipientId)->first();
        if (! $recipient) {
            $this->toast('warning', 'ភ្ញៀវនេះត្រូវបានលុបរួចហើយ។');
            $this->invitation->load('recipients');

            return;
        }

        $recipient->delete();
        $this->invitation->load('recipients');
        $this->toast('success', 'បានលុប «'.$recipient->recipient_name.'» — តំណរបស់គេលែងដំណើរការហើយ។');
    }

    /** Pauses or restores one guest's link without deleting their RSVP. */
    public function toggleRecipient(int $recipientId): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $recipient = $this->invitation->recipients()->whereKey($recipientId)->firstOrFail();
        $recipient->update(['revoked_at' => $recipient->revoked_at ? null : now()]);
        $this->invitation->load('recipients');
        $recipient->revoked_at
            ? $this->toast('warning', 'បានផ្អាកតំណរបស់ «'.$recipient->recipient_name.'» — គេមិនអាចបើកធៀបបានទេ រហូតដល់អ្នកបើកវិញ។')
            : $this->toast('success', 'បានបើកតំណរបស់ «'.$recipient->recipient_name.'» វិញហើយ។');
    }

    /** Issues a new unguessable token; the old link stops working immediately. */
    public function rotateRecipient(int $recipientId): void
    {
        abort_unless($this->invitation->isPaid(), 403);

        $recipient = $this->invitation->recipients()->whereKey($recipientId)->firstOrFail();
        $recipient->update(['token' => Str::random(32), 'revoked_at' => null]);
        $this->invitation->load('recipients');
        $this->toast('success', 'បានបង្កើតតំណថ្មីសម្រាប់ «'.$recipient->recipient_name.'» — តំណចាស់លែងដំណើរការ។ កុំភ្លេចផ្ញើតំណថ្មី។');
    }

    public function render()
    {
        $recipients = $this->invitation->recipients;
        $templateFields = $this->invitation->template->fields ?? [];

        return view('livewire.pages.dashboard.invitation-manage-page', [
            'fieldCatalog' => InvitationTemplate::FIELD_CATALOG,
            'sections' => $this->visibleSections(),
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
