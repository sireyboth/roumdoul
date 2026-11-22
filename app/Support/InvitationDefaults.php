<?php

namespace App\Support;

use App\Models\InvitationTemplate;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Starting values for the admin's «New invitation template» form: the first plan, add-on prices, the
 * dropdown suggestions next to plan inputs, the shop-page texts, «How to use», FAQ and the fields ticked
 * for a category. Edited in Admin › Catalog › Invitation defaults (App\Filament\Pages\InvitationDefaults),
 * stored as one Setting row; anything never saved falls back to DEFAULTS below.
 *
 * Only the invitation template form uses these — the plain Services form never does, so a non-invitation
 * product keeps blank plans.
 */
class InvitationDefaults
{
    public const SETTING_KEY = 'invitation_defaults';

    public const DEFAULTS = [
        // the plan(s) a new template starts with
        'plans' => [
            ['label' => 'Basic', 'price' => 5, 'badge' => null, 'max_recipients' => 100, 'max_gallery_photos' => 6, 'retention_months' => 6],
        ],

        // add-ons pre-filled on every new invitation plan
        'extra_guest_price' => 0.1,
        'extra_photo_price' => 0.25,
        'extend_price' => 6,
        'extend_months' => null, // blank = ServicePlan::DEFAULT_EXTEND_MONTHS
        'feature_prices' => [
            ['feature' => 'qr_code', 'price' => 0.25],
            ['feature' => 'map_url', 'price' => 0.25],
            ['feature' => 'event_schedule', 'price' => 0.5],
        ],

        // dropdown suggestions shown under the plan inputs (you can still type any value)
        'suggest_labels' => ['Basic', 'Standard', 'Premium', 'VIP'],
        'suggest_prices' => ['3', '5', '8', '10', '15'],
        'suggest_recipients' => ['50', '100', '150', '200', '300', '500'],
        'suggest_photos' => ['6', '10', '15', '20', '30'],
        'suggest_months' => ['3', '6', '12'],

        // «Shop page» tab
        'sort_order' => 2,
        'short_description' => 'A beautiful digital invitation with a personal link for every guest. Add your names, date, place, photos and music — ready in minutes.',
        'description' => "A modern invitation your guests open on their phone. No printing, no waiting.\n\n"
            ."**How it works**\n"
            ."- Choose a plan and order\n"
            ."- Fill in your names, date, place, photos and music in your dashboard\n"
            ."- Send one personal link to each guest — their name shows on the invitation\n\n"
            ."**What you get**\n"
            ."- A personal link for every guest\n"
            ."- Photo gallery and background music\n"
            ."- Map, countdown and RSVP (depends on your plan)\n"
            ."- Edit anytime — guests see changes right away\n"
            ."- Works on every phone, in Khmer\n\n"
            .'Questions? Message us on Telegram — we reply fast.',
        'how_to_use_steps' => [
            'Choose a plan and place your order.',
            'Pay by ABA or Wing. We confirm it quickly.',
            'Open your dashboard and fill in your details and photos.',
            'Add your guests\' names to get one link for each.',
            'Send the links by Telegram, Messenger or SMS.',
        ],
        'faqs' => [
            ['question' => 'When can I start?', 'answer' => 'Right after we confirm your payment, usually within minutes.'],
            ['question' => 'Can I edit it after sending?', 'answer' => 'Yes. Change anything anytime. Guests see the new version right away.'],
            ['question' => 'Do guests need an app?', 'answer' => 'No. The link opens in any phone browser.'],
            ['question' => 'I need more guests or photos.', 'answer' => 'Buy an add-on in your dashboard. No need to start again.'],
            ['question' => 'How long does it stay online?', 'answer' => 'It depends on your plan. You can extend it before it ends.'],
            ['question' => 'Can I see it first?', 'answer' => 'Yes. Tap «View live demo» on this page.'],
        ],

        // category (typed on Basics) → fields ticked for you on the Fields tab
        'category_presets' => [
            ['category' => 'wedding', 'sections' => ['essentials', 'premium', 'wedding'], 'fields' => ['dress_code']],
        ],
    ];

    /** @return array<string, mixed> */
    public static function all(): array
    {
        $stored = Setting::get(self::SETTING_KEY, []);

        return array_replace(self::DEFAULTS, is_array($stored) ? $stored : []);
    }

    public static function get(string $key): mixed
    {
        return self::all()[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    public static function save(array $data): void
    {
        $data = array_intersect_key($data, self::DEFAULTS);

        // repeaters key their rows by uuid — store plain lists
        foreach (['plans', 'feature_prices', 'how_to_use_steps', 'faqs', 'category_presets'] as $list) {
            if (is_array($data[$list] ?? null)) {
                $data[$list] = array_values($data[$list]);
            }
        }

        Setting::put(self::SETTING_KEY, $data);
    }

    /** Add-on values every new invitation plan starts with. */
    public static function addons(): array
    {
        return [
            'extra_guest_price' => self::get('extra_guest_price'),
            'extra_photo_price' => self::get('extra_photo_price'),
            'extend_price' => self::get('extend_price'),
            'extend_months' => self::get('extend_months'),
            'feature_prices' => self::featurePriceRows(),
        ];
    }

    /** @return list<array{feature: string, price: float}> */
    public static function featurePriceRows(): array
    {
        return collect(self::get('feature_prices') ?? [])
            ->filter(fn ($row) => is_array($row) && isset(InvitationTemplate::FIELD_CATALOG[$row['feature'] ?? '']) && is_numeric($row['price'] ?? null))
            ->map(fn ($row) => ['feature' => $row['feature'], 'price' => (float) $row['price']])
            ->values()
            ->all();
    }

    /** The plans repeater's starting rows: each default plan plus the add-on defaults. */
    public static function planItems(): array
    {
        return collect(self::get('plans') ?: self::DEFAULTS['plans'])
            ->map(fn (array $plan) => [
                'label' => $plan['label'] ?? null,
                'price' => $plan['price'] ?? null,
                'badge' => $plan['badge'] ?? null,
                'in_stock' => true,
                'max_recipients' => $plan['max_recipients'] ?? null,
                'max_gallery_photos' => $plan['max_gallery_photos'] ?? null,
                'retention_months' => $plan['retention_months'] ?? null,
                'features' => [],
                ...self::addons(),
            ])
            ->values()
            ->all();
    }

    /** Suggestions for a plan input's dropdown (datalist). */
    public static function suggestions(string $key): array
    {
        return collect(self::get("suggest_{$key}") ?? [])
            ->map(fn ($v) => (string) $v)
            ->filter(fn ($v) => $v !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The catalog fields to tick for a category typed on the Basics tab ("wedding", "khmer-wedding"…), or [] when
     * no preset matches. A preset matches when the category contains its word.
     *
     * @return list<string>
     */
    public static function presetFieldsFor(?string $category): array
    {
        $category = Str::lower(trim((string) $category));

        if ($category === '') {
            return [];
        }

        foreach (self::get('category_presets') ?? [] as $preset) {
            $word = Str::lower(trim((string) ($preset['category'] ?? '')));

            if ($word === '' || ! Str::contains($category, $word)) {
                continue;
            }

            $fields = collect($preset['sections'] ?? [])
                ->flatMap(fn ($section) => InvitationTemplate::FIELD_SECTIONS[$section] ?? [])
                ->merge($preset['fields'] ?? []);

            return $fields->filter(fn ($key) => isset(InvitationTemplate::FIELD_CATALOG[$key]))->unique()->values()->all();
        }

        return [];
    }
}
