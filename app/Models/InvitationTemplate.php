<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvitationTemplate extends Model
{
    use HasFactory;

    /**
     * The standard field catalog: every key a template can declare in its `fields` schema,
     * with the input type the dashboard form should render for it. Adding a genuinely new
     * field means adding one entry here, not touching the dashboard or any template file.
     * Field types the dashboard form renderer understands: text, textarea, image, gallery
     * (multiple images), schedule (repeatable time+label rows), boolean, datetime, color.
     */
    public const FIELD_CATALOG = [
        // Identity / basics — always available on every plan once a template selects them,
        // see FREE_FIELDS below.
        'sender_name' => ['label' => 'Your name (sender)', 'type' => 'text'],
        'groom_name' => ['label' => "Groom's name", 'type' => 'text'],
        'bride_name' => ['label' => "Bride's name", 'type' => 'text'],
        'celebrant_name' => ['label' => "Birthday person's name", 'type' => 'text'],
        'turning_age' => ['label' => 'Turning (age)', 'type' => 'text'],
        'headline' => ['label' => 'Headline', 'type' => 'text'],
        'message' => ['label' => 'Message', 'type' => 'textarea'],
        'cover_image' => ['label' => 'Cover image', 'type' => 'image'],
        'event_date' => ['label' => 'Event date', 'type' => 'datetime'],
        'venue_name' => ['label' => 'Venue name', 'type' => 'text'],
        'music_url' => ['label' => 'Background music (YouTube/TikTok link)', 'type' => 'text'],
        'accent_color' => ['label' => 'Accent color', 'type' => 'color'],

        // Premium extras — the pricing upsell lever, see FREE_FIELDS below. A plan only
        // shows these to the recipient if the plan's `features` list includes the key.
        'venue_address' => ['label' => 'Venue address (for map link)', 'type' => 'text'],
        'khmer_date' => ['label' => 'Khmer date (lunar)', 'type' => 'text'],
        'rsvp_enabled' => ['label' => 'Enable RSVP buttons', 'type' => 'boolean'],
        'countdown_enabled' => ['label' => 'Show countdown', 'type' => 'boolean'],
        'photo_gallery' => ['label' => 'Photo gallery', 'type' => 'gallery'],
        'event_schedule' => ['label' => 'Event schedule', 'type' => 'schedule'],
        'qr_code' => ['label' => 'QR code image', 'type' => 'image'],
        'cta_label' => ['label' => 'Button label', 'type' => 'text'],
        'cta_url' => ['label' => 'Button link', 'type' => 'text'],
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
        'music_url', 'accent_color',
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
            'photo_gallery', 'event_schedule', 'qr_code', 'cta_label', 'cta_url',
        ],
        'wedding' => ['groom_name', 'bride_name', 'khmer_date'],
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

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
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
