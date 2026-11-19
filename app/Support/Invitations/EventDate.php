<?php

namespace App\Support\Invitations;

use Illuminate\Support\Carbon;

/**
 * An invitation's `event_date` as a real moment in time.
 *
 * The dashboard stores what the customer typed in a datetime-local input («2027-02-14T19:00»),
 * which has no time zone. Read with Carbon::parse() it would be taken as app.timezone (UTC), so a
 * countdown built from it ran 7 hours late in Cambodia. This reads it as Cambodia wall-clock time
 * (config roumdoul.event_timezone) instead; toIso8601String() then gives «2027-02-14T19:00:00+07:00»,
 * which `new Date()` in the browser turns into the right instant on any phone. A value that already
 * carries an offset keeps it.
 */
class EventDate
{
    public static function timezone(): string
    {
        return config('roumdoul.event_timezone', 'Asia/Phnom_Penh');
    }

    /** The event date, or null when it's empty or can't be read. */
    public static function parse(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value), self::timezone());
        } catch (\Throwable) {
            return null;
        }
    }

    /** «Now» in the same zone, for day counts that must turn over at Cambodian midnight. */
    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }
}
