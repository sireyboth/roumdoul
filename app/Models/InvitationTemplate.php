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
        // and in its "Guide" section — write it for the customer, not for admins. `example`
        // (optional) is a sample value, used as the input placeholder and in the help panel.
        // `label` and `hint` are English source strings: the dashboard shows them through
        // localizedCatalog(), so each needs an entry in lang/km.json and lang/en.json. The
        // admin panel uses `label` untranslated. `example` is sample invitation content and
        // stays as written (invitations are Khmer).

        // Identity / basics — always available on every plan once a template selects them,
        // see FREE_FIELDS below.
        'sender_name' => ['label' => 'Your name (sender)', 'type' => 'text', 'hint' => 'Your name — guests will see who is inviting them.', 'example' => 'សុខា'],
        'groom_name' => ['label' => "Groom's name", 'type' => 'text', 'hint' => "The groom's name, as you want it to appear on the invitation.", 'example' => 'សុខ សុខា'],
        'bride_name' => ['label' => "Bride's name", 'type' => 'text', 'hint' => "The bride's name, as you want it to appear on the invitation.", 'example' => 'ចាន់ ស្រីនាង'],
        'couple_initials' => ['label' => "Couple's initials", 'type' => 'text', 'hint' => "The couple's initials, shown large inside the gold frame on the cover, e.g. «ប&ម». Leave empty to use the first letters of the groom's and bride's names.", 'example' => 'ប&ម'],
        'celebrant_name' => ['label' => "Birthday person's name", 'type' => 'text', 'hint' => 'The name of the person whose birthday it is.', 'example' => 'ស្រីពៅ'],
        'turning_age' => ['label' => 'Turning (age)', 'type' => 'text', 'hint' => 'The age they are turning, e.g. 25.', 'example' => '25'],
        'headline' => ['label' => 'Headline', 'type' => 'text', 'hint' => 'The big title at the top of the invitation, e.g. «សូមគោរពអញ្ជើញ».', 'example' => 'សូមគោរពអញ្ជើញ'],
        'message' => ['label' => 'Message', 'type' => 'textarea', 'hint' => 'The message you want to send your guests — a few short sentences work best.'],
        'thanks_message' => ['label' => 'Thank-you message', 'type' => 'textarea', 'hint' => 'A thank-you note to your honored guests — shown at the bottom of the invitation. Leave empty to hide it.'],
        'apology_message' => ['label' => 'Apology letter', 'type' => 'textarea', 'hint' => 'A note apologizing for not inviting guests in person — shown at the bottom of the invitation. Leave empty to hide it.'],
        'reception_message' => ['label' => 'Reception invitation text', 'type' => 'textarea', 'hint' => "The text on the reception invitation card, under the guest's name, e.g. «និងញាតិមិត្ត អញ្ជើញចូលរួមជាអធិបតី… ដោយមេត្រីភាព ។» Leave empty to use the default text."],
        'opening_line' => ['label' => 'Opening line', 'type' => 'text', 'hint' => 'The very first line, under the title — leave empty to use «នមោ តស្ស ភគវតោ អរហតោ សម្មាសម្ពុទ្ធស្ស».', 'example' => 'នមោ តស្ស ភគវតោ អរហតោ សម្មាសម្ពុទ្ធស្ស'],
        'gift_message' => ['label' => 'Text above the QR code', 'type' => 'textarea', 'hint' => "The words above the QR code for donations or wedding gifts — leave empty to use the invitation's default text."],
        'rsvp_message' => ['label' => 'RSVP question', 'type' => 'text', 'hint' => "The question above the RSVP buttons — leave empty to use the invitation's default text.", 'example' => 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមកុសលជាមួយយើងខ្ញុំដែរឬទេ?'],
        'background_image' => ['label' => 'Background image (optional)', 'type' => 'image', 'hint' => "A full-screen background image — portrait photos work best. Leave empty to use the invitation's default image."],
        'title_image' => ['label' => 'Title image (optional)', 'type' => 'image', 'hint' => "A large title image at the very top (e.g. decorative lettering as a transparent PNG). Leave empty to use the headline you typed or the invitation's default title image."],
        'cover_image' => ['label' => 'Cover image', 'type' => 'image', 'hint' => 'The big picture guests see first when they open the invitation — choose your clearest, most beautiful photo.'],
        'event_date' => ['label' => 'Event date', 'type' => 'datetime', 'hint' => 'The date and time of the event — used to show the date and the countdown.'],
        'venue_name' => ['label' => 'Venue name', 'type' => 'text', 'hint' => "The name of the venue, e.g. «គេហដ្ឋានខាងស្រី» (the bride's family home) or a hotel name.", 'example' => 'គេហដ្ឋានខាងស្រី'],
        'music_url' => ['label' => 'Background music (YouTube/TikTok link)', 'type' => 'text', 'hint' => 'Paste a song link from YouTube or TikTok — guests can listen to the music when they open the invitation.', 'example' => 'https://youtu.be/…'],
        'music_file' => ['label' => 'Background music (upload MP3)', 'type' => 'audio', 'section' => 'details', 'hint' => "Or upload an MP3 / M4A music file straight from your phone (max 10 MB ≈ a 10-minute song). If there's both a link and a file, the invitation plays the file."],
        'accent_color' => ['label' => 'Accent color', 'type' => 'color', 'hint' => "The invitation's main color (buttons, borders and decorative text)."],
        'groom_photo' => ['label' => "Groom's photo", 'type' => 'image', 'hint' => 'A photo of the groom — a portrait photo that clearly shows the face works best.'],
        'bride_photo' => ['label' => "Bride's photo", 'type' => 'image', 'hint' => 'A photo of the bride — a portrait photo that clearly shows the face works best.'],
        'groom_father' => ['label' => "Groom's father", 'type' => 'text', 'hint' => "The groom's father's name — type the name only («លោក» is added for you).", 'example' => 'ហេង ឌី'],
        'groom_mother' => ['label' => "Groom's mother", 'type' => 'text', 'hint' => "The groom's mother's name — type the name only («លោកស្រី» is added for you).", 'example' => 'សុខ ជា'],
        'bride_father' => ['label' => "Bride's father", 'type' => 'text', 'hint' => "The bride's father's name — type the name only («លោក» is added for you).", 'example' => 'ឈិន សារ៉ាត'],
        'bride_mother' => ['label' => "Bride's mother", 'type' => 'text', 'hint' => "The bride's mother's name — type the name only («លោកស្រី» is added for you).", 'example' => 'ពិស្ណុ សារ៉ាត'],
        'groom_phone' => ['label' => "Groom's phone number", 'type' => 'text', 'hint' => "A phone number on the groom's side that guests can call.", 'example' => '012 345 678'],
        'bride_phone' => ['label' => "Bride's phone number", 'type' => 'text', 'hint' => "A phone number on the bride's side that guests can call.", 'example' => '098 765 432'],
        'contact_phone' => ['label' => 'Contact phone number', 'type' => 'text', 'hint' => 'A phone number guests can call for more information.', 'example' => '012 345 678'],
        'host_names' => ['label' => 'Hosts / organizers', 'type' => 'textarea', 'hint' => 'Names of the ceremony hosts or organizing committee — one name per line, e.g. «លោក សុខ ហេង និងលោកស្រី ចាន់ ធីតា».', 'example' => 'លោក សុខ ហេង និងលោកស្រី ចាន់ ធីតា'],

        // Premium extras — the pricing upsell lever, see FREE_FIELDS below. A plan only
        // shows these to the recipient if the plan's `features` list includes the key.
        'venue_address' => ['label' => 'Venue address (for map link)', 'type' => 'text', 'hint' => "The venue's full address — guests tap the directions button to open Google Maps.", 'example' => 'ផ្ទះលេខ 12, ផ្លូវ 271, ភ្នំពេញ'],
        'map_url' => ['label' => 'Google Maps link', 'type' => 'text', 'hint' => 'Paste the location link from Google Maps (tap Share → Copy link) — the «មើលផែនទី» (View map) button will open the exact location.', 'example' => 'https://maps.app.goo.gl/…'],
        'khmer_date' => ['label' => 'Khmer date (lunar)', 'type' => 'text', 'hint' => 'The date in the lunar calendar, e.g. «ថ្ងៃអាទិត្យ ៥កើត ខែមាឃ».', 'example' => 'ថ្ងៃអាទិត្យ ៥កើត ខែមាឃ'],
        'rsvp_enabled' => ['label' => 'Enable RSVP buttons', 'type' => 'boolean', 'hint' => 'Tick to let guests tap «ចូលរួម» (attending) or «មិនចូលរួម» (not attending) — you can see their answers in the "Responses" tab.'],
        'countdown_enabled' => ['label' => 'Show countdown', 'type' => 'boolean', 'hint' => 'Tick to show a countdown to the day of the event (Event date must be filled in too).'],
        'photo_gallery' => ['label' => 'Photo gallery', 'type' => 'gallery', 'hint' => 'Lots of photos (e.g. pre-wedding photos), shown as a gallery on the invitation.'],
        'gallery_feed' => ['label' => 'Scrolling photo feed', 'type' => 'boolean', 'hint' => 'Tick to show photos as big vertical panels you scroll down (like TikTok). Unticked = small photos in a frame.'],
        'event_schedule' => ['label' => 'Event schedule', 'type' => 'schedule', 'hint' => 'The event program — each time and activity, e.g. «7:00 AM — ហែជំនូន».'],
        // `section` puts a field under a different dashboard tab than its type would (an image
        // normally goes under "media"); see InvitationManagePage::fieldSection().
        'schedule_image' => ['label' => 'Schedule as an image (optional)', 'type' => 'image', 'section' => 'program', 'hint' => 'Already have a designed schedule as an image (e.g. a decorated card)? Put it here — the invitation will show this image instead of the schedule typed above. Leave empty to use the typed schedule.'],
        'story_chapters' => ['label' => 'Our story (chapters)', 'type' => 'story', 'hint' => 'The story of you two, split into chapters — a title and a short text for each.'],
        'qr_code' => ['label' => 'QR code image', 'type' => 'image', 'hint' => 'A QR code image, e.g. a bank QR, so guests can send a wedding gift from afar.'],
        'gift_account' => ['label' => 'Gift account details', 'type' => 'textarea', 'hint' => 'Bank account name and number, shown under the QR code, e.g. «ABA · SOKHA & SREYNEANG · 000 123 456».', 'example' => 'ABA · SOKHA · 000 123 456'],
        'cta_label' => ['label' => 'Button label', 'type' => 'text', 'hint' => 'The text on your own button, e.g. «ចូលរួម Telegram Group» — fill in together with Button link.', 'example' => 'ចូលរួម Telegram Group'],
        'cta_url' => ['label' => 'Button link', 'type' => 'text', 'hint' => 'The link the button opens — must start with https://', 'example' => 'https://t.me/…'],
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
        'groom_father', 'groom_mother', 'bride_father', 'bride_mother', 'thanks_message', 'apology_message', 'reception_message', 'gallery_feed', 'gift_account',
        'contact_phone', 'host_names', 'title_image', 'opening_line', 'gift_message', 'rsvp_message', 'background_image', 'music_file',
        'couple_initials',
    ];

    /**
     * Premium fields that are an alternative form of another premium field, so they follow that
     * field's plan unlock instead of needing their own (see Invitation::fieldUnlocked()) — a plan
     * that includes the schedule includes it either way, typed rows or a ready-made image.
     */
    public const UNLOCKED_WITH = [
        'schedule_image' => 'event_schedule',
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
            'music_url', 'accent_color', 'venue_address', 'map_url', 'rsvp_enabled', 'countdown_enabled',
            'photo_gallery', 'gallery_feed', 'event_schedule', 'schedule_image', 'qr_code', 'gift_account', 'cta_label', 'cta_url',
            'contact_phone', 'title_image', 'gift_message', 'rsvp_message', 'background_image', 'music_file',
        ],
        'wedding' => [
            'groom_name', 'bride_name', 'couple_initials', 'groom_father', 'groom_mother', 'bride_father', 'bride_mother', 'groom_photo', 'bride_photo', 'groom_phone', 'bride_phone',
            'khmer_date', 'story_chapters', 'thanks_message', 'apology_message', 'reception_message',
        ],
        'birthday' => ['celebrant_name', 'turning_age'],
        // Buddhist merit-making ceremonies (Kathina, Phka Prak, ...)
        'ceremony' => ['host_names', 'opening_line'],
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
     * FIELD_CATALOG entries minus FREE_FIELDS (and UNLOCKED_WITH followers), labeled for a
     * plan's "unlocked fields" checkbox list — i.e. every field that *can* be gated behind a
     * pricing plan.
     */
    /** FIELD_CATALOG with `label` and `hint` in the current interface language (customer dashboard). */
    public static function localizedCatalog(): array
    {
        return array_map(fn (array $field) => array_merge($field, array_filter([
            'label' => __($field['label']),
            'hint' => isset($field['hint']) ? __($field['hint']) : null,
        ])), self::FIELD_CATALOG);
    }

    public static function premiumFieldOptions(): array
    {
        return collect(self::FIELD_CATALOG)
            ->except([...self::FREE_FIELDS, ...array_keys(self::UNLOCKED_WITH)])
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
