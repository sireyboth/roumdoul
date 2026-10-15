<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InvitationTemplate extends Model
{
    use HasFactory;

    /**
     * The standard field catalog: every key a template can declare in its `fields` schema,
     * with the input type the dashboard form should render for it. Adding a genuinely new
     * field means adding one entry here, not touching the dashboard or any template file.
     * Field types the dashboard form renderer understands: text, textarea, image, gallery
     * (multiple images), schedule (repeatable time+label rows), story (repeatable title+text
     * rows), boolean, datetime, color.
     */
    public const FIELD_CATALOG = [
        // `hint` is the customer-facing explanation shown under each input in the dashboard
        // and in its "Guide" section — write it for the customer, not for admins.

        // Identity / basics — always available on every plan once a template selects them,
        // see FREE_FIELDS below.
        'sender_name' => ['label' => 'Your name (sender)', 'type' => 'text', 'hint' => 'ឈ្មោះរបស់អ្នក — ភ្ញៀវនឹងឃើញថាអ្នកណាជាអ្នកអញ្ជើញ។'],
        'groom_name' => ['label' => "Groom's name", 'type' => 'text', 'hint' => 'ឈ្មោះកូនកំលោះ ដូចដែលអ្នកចង់ឲ្យបង្ហាញលើធៀប។'],
        'bride_name' => ['label' => "Bride's name", 'type' => 'text', 'hint' => 'ឈ្មោះកូនក្រមុំ ដូចដែលអ្នកចង់ឲ្យបង្ហាញលើធៀប។'],
        'celebrant_name' => ['label' => "Birthday person's name", 'type' => 'text', 'hint' => 'ឈ្មោះអ្នកដែលមានខួបកំណើត។'],
        'turning_age' => ['label' => 'Turning (age)', 'type' => 'text', 'hint' => 'អាយុដែលនឹងគ្រប់ក្នុងខួបនេះ ឧ. 25។'],
        'headline' => ['label' => 'Headline', 'type' => 'text', 'hint' => 'ចំណងជើងធំនៅខាងលើធៀប ឧ. «សូមគោរពអញ្ជើញ»។'],
        'message' => ['label' => 'Message', 'type' => 'textarea', 'hint' => 'សារដែលអ្នកចង់ផ្ញើដល់ភ្ញៀវ — ពីរបីប្រយោគខ្លីៗគឺល្អបំផុត។'],
        'cover_image' => ['label' => 'Cover image', 'type' => 'image', 'hint' => 'រូបភាពធំដែលភ្ញៀវឃើញមុនគេពេលបើកធៀប — ជ្រើសរូបច្បាស់ និងស្អាតបំផុត។'],
        'event_date' => ['label' => 'Event date', 'type' => 'datetime', 'hint' => 'ថ្ងៃ និងម៉ោងនៃកម្មវិធី — ប្រើសម្រាប់បង្ហាញកាលបរិច្ឆេទ និងនាឡិការាប់ថយក្រោយ។'],
        'venue_name' => ['label' => 'Venue name', 'type' => 'text', 'hint' => 'ឈ្មោះកន្លែងរៀបកម្មវិធី ឧ. «គេហដ្ឋានខាងស្រី» ឬឈ្មោះសណ្ឋាគារ។'],
        'music_url' => ['label' => 'Background music (YouTube/TikTok link)', 'type' => 'text', 'hint' => 'ចម្លងតំណបទចម្រៀងពី YouTube ឬ TikTok មកដាក់ — ភ្ញៀវអាចស្ដាប់ភ្លេងពេលបើកធៀប។'],
        'accent_color' => ['label' => 'Accent color', 'type' => 'color', 'hint' => 'ពណ៌សំខាន់របស់ធៀប (ប៊ូតុង ស៊ុម និងអក្សរលម្អ)។'],
        'groom_photo' => ['label' => "Groom's photo", 'type' => 'image', 'hint' => 'រូបថតកូនកំលោះ — រូបបញ្ឈរដែលឃើញមុខច្បាស់គឺល្អបំផុត។'],
        'bride_photo' => ['label' => "Bride's photo", 'type' => 'image', 'hint' => 'រូបថតកូនក្រមុំ — រូបបញ្ឈរដែលឃើញមុខច្បាស់គឺល្អបំផុត។'],
        'groom_phone' => ['label' => "Groom's phone number", 'type' => 'text', 'hint' => 'លេខទូរស័ព្ទខាងកូនកំលោះ ដែលភ្ញៀវអាចទាក់ទងបាន។'],
        'bride_phone' => ['label' => "Bride's phone number", 'type' => 'text', 'hint' => 'លេខទូរស័ព្ទខាងកូនក្រមុំ ដែលភ្ញៀវអាចទាក់ទងបាន។'],

        // Premium extras — the pricing upsell lever, see FREE_FIELDS below. A plan only
        // shows these to the recipient if the plan's `features` list includes the key.
        'venue_address' => ['label' => 'Venue address (for map link)', 'type' => 'text', 'hint' => 'អាសយដ្ឋានពេញរបស់កន្លែងកម្មវិធី — ភ្ញៀវចុចប៊ូតុងមើលផ្លូវ ដើម្បីបើក Google Maps។'],
        'khmer_date' => ['label' => 'Khmer date (lunar)', 'type' => 'text', 'hint' => 'ថ្ងៃខែតាមចន្ទគតិ ឧ. «ថ្ងៃអាទិត្យ ៥កើត ខែមាឃ»។'],
        'rsvp_enabled' => ['label' => 'Enable RSVP buttons', 'type' => 'boolean', 'hint' => 'ធីកដើម្បីឲ្យភ្ញៀវចុច «ចូលរួម» ឬ «មិនចូលរួម» — អ្នកមើលចម្លើយបាននៅផ្ទាំង «ការឆ្លើយតប»។'],
        'countdown_enabled' => ['label' => 'Show countdown', 'type' => 'boolean', 'hint' => 'ធីកដើម្បីបង្ហាញនាឡិការាប់ថយក្រោយដល់ថ្ងៃកម្មវិធី (ត្រូវបំពេញ Event date ផងដែរ)។'],
        'photo_gallery' => ['label' => 'Photo gallery', 'type' => 'gallery', 'hint' => 'រូបថតជាច្រើន (ឧ. រូប Pre-wedding) បង្ហាញជាវិចិត្រសាលនៅលើធៀប។'],
        'gallery_feed' => ['label' => 'Scrolling photo feed', 'type' => 'boolean', 'hint' => 'ធីកដើម្បីបង្ហាញរូបថតជាបន្ទះបញ្ឈរធំៗ រមូរចុះក្រោម (ដូច TikTok)។ មិនធីក = រូបតូចៗក្នុងស៊ុម។'],
        'event_schedule' => ['label' => 'Event schedule', 'type' => 'schedule', 'hint' => 'របៀបវារៈកម្មវិធី — ម៉ោង និងសកម្មភាពនីមួយៗ ឧ. «7:00 AM — ហែជំនូន»។'],
        'story_chapters' => ['label' => 'Our story (chapters)', 'type' => 'story', 'hint' => 'រឿងរ៉ាវរបស់អ្នកទាំងពីរ ចែកជាជំពូក — ចំណងជើង និងខ្លឹមសារខ្លីៗ។'],
        'qr_code' => ['label' => 'QR code image', 'type' => 'image', 'hint' => 'រូប QR code ឧ. QR ធនាគារ សម្រាប់ភ្ញៀវផ្ញើចំណងដៃពីចម្ងាយ។'],
        'cta_label' => ['label' => 'Button label', 'type' => 'text', 'hint' => 'អក្សរនៅលើប៊ូតុងផ្ទាល់ខ្លួន ឧ. «ចូលរួម Telegram Group» — ត្រូវបំពេញជាមួយ Button link។'],
        'cta_url' => ['label' => 'Button link', 'type' => 'text', 'hint' => 'តំណដែលប៊ូតុងនឹងបើក — ត្រូវចាប់ផ្ដើមដោយ https://'],
    ];

    /**
     * Field keys that are never plan-gated — if a template selects them, every customer on
     * every plan sees them, regardless of the plan's `features` list. Everything else in
     * FIELD_CATALOG is a premium extra: a template can still choose to include it, but it
     * only actually renders for a customer whose plan has that key in `features`
     * (see Invitation::hasFeature()). This is the pricing upsell lever — e.g. Basic gets
     * the essentials, Premium also unlocks the map link, countdown, RSVP tracking, gallery, etc.
     */
    public const FREE_FIELDS = [
        'sender_name', 'groom_name', 'bride_name', 'celebrant_name', 'turning_age',
        'headline', 'message', 'cover_image', 'event_date', 'venue_name',
        'music_url', 'accent_color', 'groom_photo', 'bride_photo', 'groom_phone', 'bride_phone',
        'gallery_feed',
    ];

    /**
     * Every FIELD_CATALOG key grouped by which event type it's actually relevant to — purely
     * for organizing the admin "Fields this design uses" checklist into readable sections as
     * the catalog grows. Has no effect on gating or rendering (that's FREE_FIELDS/hasFeature).
     * Most fields are 'universal' (reusable across any template); only fields specific to one
     * kind of event (e.g. a couple's names) get their own section. Add new sections here as
     * new event types bring genuinely dedicated fields.
     */
    public const FIELD_SECTIONS = [
        'universal' => [
            'sender_name', 'headline', 'message', 'cover_image', 'event_date', 'venue_name',
            'music_url', 'accent_color', 'venue_address', 'rsvp_enabled', 'countdown_enabled',
            'photo_gallery', 'gallery_feed', 'event_schedule', 'qr_code', 'cta_label', 'cta_url',
        ],
        'wedding' => [
            'groom_name', 'bride_name', 'groom_photo', 'bride_photo', 'groom_phone', 'bride_phone',
            'khmer_date', 'story_chapters',
        ],
        'birthday' => ['celebrant_name', 'turning_age'],
    ];

    protected $fillable = [
        'service_id',
        'name',
        'slug',
        'category',
        'is_premium',
        'is_active',
        'fields',
        'view',
        'thumbnail',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'fields' => 'array',
    ];

    protected static function booted(): void
    {
        // invitations.invitation_template_id is restrictOnDelete, and soft-deleted invitations
        // are still real rows — purge them first so they don't block deleting the template.
        // Live invitations still block it (on purpose: their public links would break).
        static::deleting(function (self $template) {
            $template->invitations()->onlyTrashed()->forceDelete();
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * Create the shop Service (+ its plans) this template is sold as, under the
     * "Digital Invitations" category. Used on create, and to re-link a template whose
     * Service was deleted (service_id is nullOnDelete, which silently drops it from the shop).
     *
     * @param  array<int, array{label: string, price: mixed, max_recipients?: mixed, retention_months?: mixed, features?: array}>  $plans
     */
    public static function createServiceFor(string $name, bool $isActive, array $plans): Service
    {
        $category = Category::firstOrCreate(
            ['slug' => 'digital-invitations'],
            ['name_en' => 'Digital Invitations', 'name_km' => 'កម្មវត្ថុអញ្ជើញឌីជីថល', 'icon' => 'sparkles']
        );

        $service = Service::create([
            'category_id' => $category->id,
            'name_en' => $name,
            'name_km' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'short_description' => $name,
            'description' => $name,
            'base_price' => $plans[0]['price'] ?? 0,
            'is_active' => $isActive,
        ]);

        foreach ($plans as $index => $plan) {
            $service->plans()->create([
                'label' => $plan['label'],
                'price' => $plan['price'],
                'max_recipients' => $plan['max_recipients'] ?? null,
                'retention_months' => $plan['retention_months'] ?? null,
                'features' => $plan['features'] ?? [],
                'sort_order' => $index,
            ]);
        }

        return $service;
    }

    /**
     * The standard field catalog keys this template uses (e.g. 'sender_name', 'event_date', 'venue_address').
     */
    public function usesField(string $key): bool
    {
        return in_array($key, $this->fields ?? [], true);
    }

    /**
     * FIELD_CATALOG entries minus FREE_FIELDS, labeled for a plan's "unlocked fields" checkbox
     * list — i.e. every field that *can* be gated behind a pricing plan.
     */
    public static function premiumFieldOptions(): array
    {
        return collect(self::FIELD_CATALOG)
            ->except(self::FREE_FIELDS)
            ->map(fn (array $field) => $field['label'])
            ->all();
    }

    /**
     * Labeled options for one FIELD_SECTIONS group, for the admin "Fields this design uses"
     * checklist (one CheckboxList per section, since Filament's CheckboxList has no native
     * option-group support like Select does).
     */
    public static function sectionFieldOptions(string $section): array
    {
        return collect(self::FIELD_CATALOG)
            ->only(self::FIELD_SECTIONS[$section] ?? [])
            ->map(fn (array $field) => $field['label'])
            ->all();
    }

    /**
     * Splits a flat `fields` array (as stored on the model) into one sub-array per
     * FIELD_SECTIONS group, keyed 'fields_universal', 'fields_wedding', etc. — used to
     * populate the admin form's per-section checkbox lists when editing an existing template.
     */
    public static function splitFieldsBySection(array $fields): array
    {
        return collect(self::FIELD_SECTIONS)
            ->mapWithKeys(fn (array $keys, string $section) => [
                "fields_{$section}" => array_values(array_intersect($fields, $keys)),
            ])
            ->all();
    }

    /**
     * The inverse of splitFieldsBySection() — merges the admin form's per-section checkbox
     * values ('fields_universal', 'fields_wedding', ...) back into one flat `fields` array
     * to actually persist on the model.
     */
    public static function mergeFieldSections(array $data): array
    {
        $fields = [];

        foreach (array_keys(self::FIELD_SECTIONS) as $section) {
            $fields = array_merge($fields, $data["fields_{$section}"] ?? []);
            unset($data["fields_{$section}"]);
        }

        $data['fields'] = array_values(array_unique($fields));

        return $data;
    }
}
