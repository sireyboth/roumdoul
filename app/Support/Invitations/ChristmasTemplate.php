<?php

namespace App\Support\Invitations;

use Carbon\Carbon;
use Illuminate\Support\HtmlString;

/**
 * Everything specific to the Christmas party template (invitations/templates/christmas.blade.php)
 * that the guest page, the dashboard editor and the save validation share: its English copy,
 * field limits, dress-code choices, date formatting and validation rules.
 *
 * This template is English-only — copy comes from lang/en/christmas.php with the locale forced to
 * 'en', dates are formatted en-GB («Saturday, 19 December 2026», «6:30 PM»), and its validation
 * messages are English whatever language the rest of the dashboard is shown in.
 */
final class ChristmasTemplate
{
    public const VIEW = 'invitations.templates.christmas';

    /** Dress-code choices in the editor's select; anything else is stored as typed ("Other"). */
    public const DRESS_CODES = ['Casual', 'Red & Green', 'Ugly Sweater', 'Formal'];

    /** Max characters per text field. */
    public const LIMITS = [
        'headline' => 40,
        'sender_name' => 40,
        'venue_name' => 50,
        'venue_address' => 100,
        'message' => 150,
        'dress_code' => 40,
        'what_to_bring' => 60,
        'contact_phone' => 50,
        'rsvp_message' => 100,
    ];

    /** The preview-only guest name in the editor (never saved: real names come from guest links). */
    public const GUEST_NAME_MAX = 30;

    /** Catalog fields the editor's main form handles; Reset clears exactly these. */
    public const FORM_FIELDS = [
        'headline', 'sender_name', 'event_date', 'venue_name', 'venue_address',
        'end_time', 'message', 'dress_code', 'what_to_bring', 'map_url', 'rsvp_deadline', 'contact_phone',
    ];

    /** Optional extras shown in their own card when the template row ticks them. */
    public const EXTRA_FIELDS = ['rsvp_enabled', 'rsvp_message', 'music_url'];

    public static function is(?string $view): bool
    {
        return $view === self::VIEW;
    }

    /** The template's two pages: 'cover' (gift box) and 'details' (the event card). */
    public const PAGES = ['cover', 'details'];

    /**
     * View data for one of the two pages. Each entry point (guest link, owner preview, demos) has
     * its own pair of routes; the page links to the other one through these URLs — nothing
     * personal ever goes into them, the data comes from the saved invitation (or, in the
     * dashboard editor's preview, from the editor's postMessage updates).
     */
    public static function pageData(string $page, string $coverUrl, string $detailsUrl): array
    {
        return ['page' => $page, 'pageUrls' => ['cover' => $coverUrl, 'details' => $detailsUrl]];
    }

    /** Whether there is anything for the details page to show; without it page 2 sends guests back to the cover. */
    public static function hasDetails(array $fields): bool
    {
        foreach (['headline', 'sender_name', 'event_date', 'venue_name', 'venue_address', 'message'] as $key) {
            if (trim((string) ($fields[$key] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /** Printed-stationery punctuation for typed text: straight quotes become curly ones. */
    public static function typo(string $text): string
    {
        $text = preg_replace('/(^|[\s(\[{“‘—–-])"/u', '$1“', $text);
        $text = str_replace('"', '”', $text);
        $text = preg_replace("/(^|[\\s(\\[{“‘—–-])'/u", '$1‘', $text);

        return str_replace("'", '’', $text);
    }

    /**
     * Inline SVGs for the details card (gold line icons and the holly/snowflake ornaments). Inline,
     * not <use>, and coloured with attributes/currentColor so the PNG download can redraw each one
     * on its canvas as a standalone image.
     */
    public static function svg(string $name, string $class = ''): HtmlString
    {
        $gold = '#C9A24B';
        $green = '#1F4D36';
        $red = '#A8172F';
        $icon = fn (string $paths) => '<svg class="xm-icon '.$class.'" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$paths.'</svg>';

        // a small holly leaf + two berries tucked into a card corner, mirrored per corner
        $corner = function (string $transform) use ($gold, $green, $red, $class) {
            return '<svg class="xm-corner '.$class.'" viewBox="0 0 40 40" width="34" height="34" aria-hidden="true" focusable="false"><g transform="'.$transform.'">'
                .'<path d="M3 30V9a6 6 0 0 1 6-6h21" fill="none" stroke="'.$gold.'" stroke-width="1.1" stroke-linecap="round"/>'
                .'<path d="M8 8c5 1 9 4 11 9l-3-.4 1.4 3.2-3.2-.6.6 3.4C10 20 7.5 14 8 8z" fill="'.$green.'"/>'
                .'<path d="M8.5 8.5 16 19" stroke="'.$gold.'" stroke-width=".7" fill="none"/>'
                .'<circle cx="7" cy="7" r="2.6" fill="'.$red.'"/><circle cx="11.6" cy="5.4" r="2" fill="'.$red.'"/>'
                .'<circle cx="6.4" cy="6.2" r=".7" fill="#fff" opacity=".6"/>'
                .'</g></svg>';
        };

        $svg = match ($name) {
            'calendar' => $icon('<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M8 13.5h2M14 13.5h2M8 16.5h2"/>'),
            'pin' => $icon('<path d="M12 21s-6.5-6.2-6.5-11.2a6.5 6.5 0 0 1 13 0C18.5 14.8 12 21 12 21z"/><circle cx="12" cy="9.8" r="2.4"/>'),
            'star' => $icon('<path d="M12 3.5l2.6 5.3 5.8.8-4.2 4.1 1 5.8L12 16.8l-5.2 2.7 1-5.8-4.2-4.1 5.8-.8z"/>'),
            'gift' => $icon('<rect x="3.5" y="8.5" width="17" height="4" rx="1"/><path d="M5 12.5V20h14v-7.5M12 8.5V20"/><path d="M12 8.5C10.5 5 6.5 4.5 6.5 7S10 8.5 12 8.5zM12 8.5c1.5-3.5 5.5-4 5.5-1.5S14 8.5 12 8.5z"/>'),
            'phone' => $icon('<path d="M6.6 3.5h2.6l1.5 4-2 1.3a11 11 0 0 0 6.5 6.5l1.3-2 4 1.5v2.6a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.6 5.7a2 2 0 0 1 2-2.2z"/>'),
            'holly' => '<svg class="xm-holly '.$class.'" viewBox="0 0 64 34" width="64" height="34" aria-hidden="true" focusable="false">'
                .'<path d="M32 21Q22 6 5 9l4 3-3 3 5 1-2 4 5-1v4q9 2 18-2z" fill="'.$green.'"/>'
                .'<path d="M32 21Q42 6 59 9l-4 3 3 3-5 1 2 4-5-1v4q-9 2-18-2z" fill="'.$green.'"/>'
                .'<path d="M31 20Q20 13 8 11M33 20Q44 13 56 11" fill="none" stroke="'.$gold.'" stroke-width=".8"/>'
                .'<circle cx="28.4" cy="21" r="3.6" fill="'.$red.'"/><circle cx="35.6" cy="21" r="3.6" fill="'.$red.'"/><circle cx="32" cy="15.6" r="3.6" fill="'.$red.'"/>'
                .'<circle cx="31" cy="14.6" r="1" fill="#fff" opacity=".55"/><circle cx="27.4" cy="20" r="1" fill="#fff" opacity=".55"/><circle cx="34.6" cy="20" r="1" fill="#fff" opacity=".55"/>'
                .'</svg>',
            'divider' => '<svg class="xm-divider '.$class.'" viewBox="0 0 180 16" width="180" height="16" aria-hidden="true" focusable="false" fill="none" stroke="'.$gold.'" stroke-linecap="round">'
                .'<path d="M10 8h64M106 8h64" stroke-width="1"/><path d="M6 8l3-3 3 3-3 3zM168 8l3-3 3 3-3 3z" fill="'.$gold.'" stroke="none"/>'
                .'<path d="M90 1v14M84 4.5l12 7M84 11.5l12-7" stroke-width="1.3"/><circle cx="90" cy="8" r="1.8" fill="'.$gold.'" stroke="none"/>'
                .'</svg>',
            'corner-tl' => $corner('translate(0 0)'),
            'corner-tr' => $corner('matrix(-1 0 0 1 40 0)'),
            'corner-bl' => $corner('matrix(1 0 0 -1 0 40)'),
            'corner-br' => $corner('matrix(-1 0 0 -1 40 40)'),
        };

        return new HtmlString($svg);
    }

    /** English copy from lang/en/christmas.php, whatever the app locale is. */
    public static function t(string $key, array $replace = []): string
    {
        return __('christmas.'.$key, $replace, 'en');
    }

    public static function label(string $field): string
    {
        return self::t("fields.{$field}.label");
    }

    /**
     * Splits the stored `event_date` («2026-12-19T18:30», «2026-12-19 18:30:00», or half-filled
     * «2026-12-19T» / «T18:30» while the form is being edited) into ['Y-m-d' or null, 'H:i' or null].
     */
    public static function splitStart(mixed $value): array
    {
        if ($value instanceof \DateTimeInterface) {
            return [$value->format('Y-m-d'), $value->format('H:i')];
        }

        $value = trim((string) $value);
        $date = preg_match('/^(\d{4}-\d{2}-\d{2})/', $value, $d) && self::validDate($d[1]) ? $d[1] : null;
        $time = preg_match('/(?:^|[T ])(\d{2}:\d{2})/', $value, $t) && self::validTime($t[1]) ? $t[1] : null;

        return [$date, $time];
    }

    public static function validDate(?string $value): bool
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $value));

        return checkdate($m, $d, $y);
    }

    public static function validTime(?string $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    /** «Saturday, 19 December 2026» */
    public static function formatDate(string $ymd): string
    {
        return Carbon::createFromFormat('!Y-m-d', $ymd)->locale('en_GB')->isoFormat('dddd, D MMMM YYYY');
    }

    /** «6:30 PM» */
    public static function formatTime(string $hi): string
    {
        return Carbon::createFromFormat('!H:i', $hi)->locale('en_GB')->format('g:i A');
    }

    /**
     * Save rules for this template's fields, keyed like the dashboard's (`fieldValues.{key}`).
     * Only fields the template row actually ticks get a rule. A required field the customer's
     * plan doesn't unlock (venue_address is premium) is optional instead — they can't show it.
     *
     * @param  array<string, mixed>  $values  the form's current fieldValues
     * @param  list<string>  $templateFields
     * @param  callable(string): bool  $unlocked
     */
    public static function rules(array $values, array $templateFields, callable $unlocked, ?string $today = null): array
    {
        $today ??= now()->toDateString();
        [$eventDay, $startTime] = self::splitStart($values['event_date'] ?? null);
        $required = fn (string $key) => $unlocked($key) ? 'required' : 'nullable';
        $text = fn (string $key) => [$required($key), 'string', 'max:'.self::LIMITS[$key]];
        $optional = fn (string $key) => ['nullable', 'string', 'max:'.self::LIMITS[$key]];

        $rules = [
            'headline' => $text('headline'),
            'sender_name' => $text('sender_name'),
            'event_date' => [$required('event_date'), 'string', function (string $attribute, mixed $value, \Closure $fail) use ($today) {
                $raw = trim((string) $value);
                [$date, $time] = self::splitStart($raw);
                if ($date === null) {
                    $fail(self::t(preg_match('/^\d/', $raw) ? 'errors.date_invalid' : 'errors.date_required'));
                } elseif ($date < $today) {
                    $fail(self::t('errors.date_past'));
                } elseif ($time === null) {
                    $fail(self::t('errors.start_time_required'));
                }
            }],
            'venue_name' => $text('venue_name'),
            'venue_address' => $text('venue_address'),
            'end_time' => ['nullable', 'date_format:H:i', function (string $attribute, mixed $value, \Closure $fail) use ($startTime) {
                if ($startTime && self::validTime($value) && $value <= $startTime) {
                    $fail(self::t('errors.end_before_start', ['time' => self::formatTime($startTime)]));
                }
            }],
            'message' => $optional('message'),
            'dress_code' => $optional('dress_code'),
            'what_to_bring' => $optional('what_to_bring'),
            'map_url' => ['nullable', 'string', 'max:2048', 'url', 'starts_with:https://,http://'],
            'rsvp_deadline' => ['nullable', 'date_format:Y-m-d', function (string $attribute, mixed $value, \Closure $fail) use ($eventDay) {
                if ($eventDay && self::validDate($value) && $value > $eventDay) {
                    $fail(self::t('errors.rsvp_after_event'));
                }
            }],
            'contact_phone' => $optional('contact_phone'),
            'rsvp_enabled' => ['nullable', 'boolean'],
            'rsvp_message' => $optional('rsvp_message'),
            'music_url' => ['nullable', 'string', 'max:2048', 'url', 'starts_with:https://,http://'],
        ];

        $out = [];
        foreach ($rules as $key => $rule) {
            if (in_array($key, $templateFields, true)) {
                $out["fieldValues.{$key}"] = $rule;
            }
        }

        return $out;
    }

    /** English messages for every rule above (the app's own validation.php is Khmer under km). */
    public static function messages(): array
    {
        $messages = [];
        foreach ([...self::FORM_FIELDS, ...self::EXTRA_FIELDS] as $key) {
            $field = self::label($key);
            $messages["fieldValues.{$key}.required"] = self::t('errors.required', ['field' => $field]);
            $messages["fieldValues.{$key}.string"] = self::t('errors.invalid', ['field' => $field]);
            $messages["fieldValues.{$key}.boolean"] = self::t('errors.invalid', ['field' => $field]);
            $messages["fieldValues.{$key}.max"] = self::t('errors.max', ['field' => $field, 'max' => self::LIMITS[$key] ?? 2048]);
        }
        $messages['fieldValues.event_date.required'] = self::t('errors.date_required');
        $messages['fieldValues.end_time.date_format'] = self::t('errors.time_invalid');
        $messages['fieldValues.rsvp_deadline.date_format'] = self::t('errors.date_invalid');
        foreach (['map_url', 'music_url'] as $key) {
            $messages["fieldValues.{$key}.url"] = self::t('errors.url');
            $messages["fieldValues.{$key}.starts_with"] = self::t('errors.url');
        }

        return $messages;
    }
}
