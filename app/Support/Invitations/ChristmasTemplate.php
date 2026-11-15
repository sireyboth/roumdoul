<?php

namespace App\Support\Invitations;

use Carbon\Carbon;

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
