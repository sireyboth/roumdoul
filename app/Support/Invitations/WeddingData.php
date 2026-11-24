<?php

namespace App\Support\Invitations;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Everything a wedding template needs from `$fields`, worked out once: names, Khmer dates and times, venue + map
 * links, gift, programme, story, gallery, guest notes (dress-code colours), RSVP, contacts, music.
 * Premium fields are already gated (`$invitation === null` on demo pages = everything unlocked) and media paths
 * already turned into URLs. Templates only lay it out:
 *
 *     @php($w = \App\Support\Invitations\WeddingData::from($fields, $invitation, $recipient ?? null))
 *     {{ $w->coupleTitle }} · {{ $w->dateKhmer }} · @foreach ($w->gallery as $i => $url) … $w->captions[$i] …
 *
 * Every value is empty / null / an empty collection when its field is empty or locked, so a section can test it
 * directly (`@if ($w->giftQr)`). Khmer helpers: WeddingData::khDigits(), ::khTime(), ::khDate(), ::isKhmer().
 */
class WeddingData
{
    public const KH_MONTHS = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];

    public const KH_DAYS = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    public const KH_DAYS_SHORT = ['អា', 'ច', 'អ', 'ពុ', 'ព្រ', 'សុ', 'ស'];

    public const EN_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // names
    public string $groomName = '';     // as typed (trimmed)

    public string $brideName = '';

    public string $groom = '';         // display form: Latin names Title Cased, Khmer untouched

    public string $bride = '';

    public string $coupleTitle = '';   // «Sokha & Sreyneang» (either one alone when the other is empty)

    public string $initials = '♡';     // couple_initials, else first letters «S&S»

    /** @var list<string> «លោក …», «លោកស្រី …» */
    public array $groomParents = [];

    /** @var list<string> */
    public array $brideParents = [];

    // look
    public string $accent;

    public string $headline = '';

    public ?string $titleImage = null;

    // date + time
    public ?Carbon $eventDate = null;

    public ?string $dateNumeric = null;    // «15 · 12 · 2026»

    public ?string $dateKhmer = null;      // «ថ្ងៃអង្គារ ទី១៥ ខែធ្នូ ឆ្នាំ២០២៦»

    public ?string $timeKhmer = null;      // «វេលាម៉ោង ៥:០០ ល្ងាច – ៩:០០ ល្ងាច» (end_time added when set)

    public ?string $endTime = null;        // «៩:០០ ល្ងាច»

    public ?string $khmerDate = null;      // khmer_date (lunar date text) — premium

    public ?string $isoDate = null;        // for `new Date()` countdowns (with the +07:00 offset)

    /** @var array{title: string, en: string, cells: list<int|null>, day: int}|null weeks start on Sunday */
    public ?array $calendar = null;

    public bool $showCountdown = false;

    // venue
    public ?string $venueName = null;

    public ?string $venueAddress = null;   // premium

    public ?string $mapUrl = null;         // directions link (exact pin when map_url has coordinates)

    public ?string $mapEmbed = null;       // iframe src, only when there is something precise to show

    public bool $showVenue = false;

    // words
    public string $message = '';

    public string $receptionText;           // reception_message, else the classic Khmer wording

    public string $closingLine = '';

    /** @var list<array{0: string, 1: string, 2: string}> [Khmer heading, English heading, text] — thanks, apology */
    public array $letters = [];

    // photos
    public ?string $cover = null;          // cover_image, else the first gallery photo

    public ?string $background = null;     // background_image

    public ?string $groomPhoto = null;

    public ?string $bridePhoto = null;

    /** @var Collection<int, string> photo URLs */
    public Collection $gallery;

    /** @var Collection<int, string> caption N belongs to gallery photo N ('' when none) */
    public Collection $captions;

    // story + programme (premium)
    /** @var Collection<int, array{place: string, when: string, text: string}> «Place | when» titles split */
    public Collection $story;

    /** @var Collection<int, array{time: string, label: string}> time already in Khmer («៦:០០ ព្រឹក») */
    public Collection $schedule;

    public ?string $scheduleImage = null;  // when set, show it instead of the schedule rows

    // guest notes
    public string $dressCode = '';

    /** @var list<array{hex: string, name: string}> always render these as swatches (see DressCode) */
    public array $dressColours = [];

    public string $whatToBring = '';

    public ?string $rsvpBy = null;         // rsvp_deadline in Khmer

    // RSVP + gift + contact
    public bool $showRsvp = false;

    public ?string $rsvpUrl = null;        // null on demo pages: just show the thank-you

    public string $rsvpQuestion;

    public ?string $giftQr = null;

    public ?string $giftAccount = null;

    public ?string $giftNumber = null;     // digits only, for a «copy account number» button

    public string $giftMessage;

    /** @var Collection<int, array{role: string, name: string, phone: string, tel: string}> */
    public Collection $contacts;

    public ?string $ctaLabel = null;       // only set when both label and a valid https link are there

    public ?string $ctaUrl = null;

    // music
    public ?string $musicFile = null;

    public ?string $musicUrl = null;       // a link we can't play inline (open it in a new tab)

    public ?string $youtubeId = null;

    public string $songTitle = '';

    public static function from(array $fields, ?Invitation $invitation = null, ?InvitationRecipient $recipient = null, array $defaults = []): self
    {
        return new self($fields, $invitation, $recipient, $defaults);
    }

    /**
     * @param  array  $defaults  template wording for empty fields: accent, headline, closing_line, reception_message,
     *                           rsvp_message, gift_message
     */
    public function __construct(private array $fields, private ?Invitation $invitation = null, ?InvitationRecipient $recipient = null, array $defaults = [])
    {
        $f = fn (string $k) => trim((string) ($fields[$k] ?? ''));
        $on = fn (string $k) => $invitation ? $invitation->fieldUnlocked($k) : true;
        $url = fn (?string $p) => ($p = trim((string) $p)) === '' ? null : (preg_match('#^https?://#i', $p) ? $p : Storage::disk('s3')->url($p));

        // names
        $this->groomName = $f('groom_name');
        $this->brideName = $f('bride_name');
        $this->groom = self::pretty($this->groomName);
        $this->bride = self::pretty($this->brideName);
        $this->coupleTitle = collect([$this->groom, $this->bride])->filter()->implode(' & ');
        $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
        $this->initials = $f('couple_initials') ?: (collect([$this->groomName, $this->brideName])->map($initial)->filter()->implode('&') ?: '♡');
        $parent = function (string $key, string $title) use ($f): ?string {
            $n = $f($key);

            return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
        };
        $this->groomParents = array_values(array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]));
        $this->brideParents = array_values(array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]));

        // look
        $this->accent = preg_match('/^#[0-9a-f]{6}$/i', $f('accent_color')) ? $f('accent_color') : ($defaults['accent'] ?? '#b08d57');
        $this->headline = $f('headline') ?: ($defaults['headline'] ?? '');
        $this->titleImage = $url($f('title_image'));

        // date + time
        $this->eventDate = EventDate::parse($fields['event_date'] ?? null);
        if (preg_match('/^(\d{1,2}):(\d{2})/', $f('end_time'), $m)) {
            $this->endTime = self::khTime((int) $m[1], $m[2]);
        }
        if ($d = $this->eventDate) {
            $this->dateNumeric = $d->format('d · m · Y');
            $this->dateKhmer = self::khDate($d);
            $this->timeKhmer = 'វេលាម៉ោង '.self::khTime($d->hour, $d->format('i')).($this->endTime ? ' – '.$this->endTime : '');
            $this->isoDate = $d->toIso8601String();
            $first = $d->copy()->startOfMonth();
            $this->calendar = [
                'title' => 'ខែ'.self::KH_MONTHS[$d->month - 1].' '.self::khDigits($d->year),
                'en' => self::EN_MONTHS[$d->month - 1].' '.$d->year,
                'cells' => array_merge(array_fill(0, $first->dayOfWeek, null), range(1, $first->daysInMonth)),
                'day' => $d->day,
            ];
        }
        $this->khmerDate = $on('khmer_date') && $f('khmer_date') !== '' ? $f('khmer_date') : null;
        $this->showCountdown = $this->eventDate && ! empty($fields['countdown_enabled']) && $on('countdown_enabled');

        // venue + map: map_url (a pin) wins; an address that is itself a link counts as the map link
        $this->venueName = $f('venue_name') ?: null;
        $this->venueAddress = $on('venue_address') && $f('venue_address') !== '' ? $f('venue_address') : null;
        $mapLink = $on('map_url') && preg_match('#^https?://#i', $f('map_url')) ? $f('map_url') : null;
        if ($this->venueAddress && preg_match('#^https?://\S+$#i', $this->venueAddress)) {
            $mapLink = $mapLink ?: $this->venueAddress;
            $this->venueAddress = null;
        }
        $query = $this->venueAddress ?: $this->venueName;
        $pinned = false;
        if ($mapLink && preg_match('#[@=](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#', urldecode($mapLink), $pin)) {
            $query = $pin[1].','.$pin[2];
            $pinned = true;
        }
        $this->mapUrl = $mapLink ?: ($query ? 'https://maps.google.com/?q='.urlencode($query) : null);
        $this->mapEmbed = ($pinned || $this->venueAddress) && $query ? 'https://maps.google.com/maps?q='.urlencode($query).'&z=16&output=embed' : null;
        $this->showVenue = (bool) ($this->venueName || $this->venueAddress || $mapLink);

        // words
        $this->message = $f('message');
        $this->receptionText = $f('reception_message') ?: ($defaults['reception_message']
            ?? 'អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ');
        $this->closingLine = $f('closing_line') ?: ($defaults['closing_line'] ?? '');
        $this->letters = array_values(array_filter([
            ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', $f('thanks_message')],
            ['លិខិតសូមអភ័យទោស', 'A gentle apology', $f('apology_message')],
        ], fn ($l) => $l[2] !== ''));

        // photos
        $this->gallery = collect($on('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->map($url)->filter()->values();
        $this->captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
        $this->cover = $url($f('cover_image')) ?? $this->gallery->first();
        $this->background = $url($f('background_image'));
        $this->groomPhoto = $url($f('groom_photo'));
        $this->bridePhoto = $url($f('bride_photo'));

        // story + programme
        $this->story = collect($on('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
            ->filter(fn ($r) => ! empty($r['title']) || ! empty($r['text']))
            ->map(function ($r) {
                [$place, $when] = array_pad(array_map('trim', explode('|', (string) ($r['title'] ?? ''), 2)), 2, '');

                return ['place' => $place, 'when' => $when, 'text' => trim((string) ($r['text'] ?? ''))];
            })->values();
        $this->schedule = collect($on('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
            ->filter(fn ($r) => ! empty($r['time']) || ! empty($r['label']))
            ->map(fn ($r) => ['time' => self::scheduleTime($r['time'] ?? ''), 'label' => trim((string) ($r['label'] ?? ''))])->values();
        $this->scheduleImage = $on('schedule_image') ? $url($f('schedule_image')) : null;

        // guest notes
        $this->dressCode = $on('dress_code') ? $f('dress_code') : '';
        $this->dressColours = DressCode::colours($this->dressCode);
        $this->whatToBring = $f('what_to_bring');
        if ($d = EventDate::parse($fields['rsvp_deadline'] ?? null)) {
            $this->rsvpBy = self::khDate($d);
        }

        // RSVP, gift, contacts
        $this->showRsvp = ! empty($fields['rsvp_enabled']) && $on('rsvp_enabled');
        $this->rsvpUrl = $invitation && $recipient ? route('invitation.rsvp', $recipient) : null;
        $this->rsvpQuestion = $f('rsvp_message') ?: ($defaults['rsvp_message'] ?? 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមជាមួយយើងខ្ញុំដែរឬទេ?');
        $this->giftQr = $on('qr_code') ? $url($f('qr_code')) : null;
        $this->giftAccount = $this->giftQr && $f('gift_account') !== '' ? $f('gift_account') : null;
        if ($this->giftAccount && preg_match_all('/\d[\d\s-]{4,}\d/', $this->giftAccount, $nums)) {
            $this->giftNumber = preg_replace('/\D/', '', collect($nums[0])->sortByDesc(fn ($n) => strlen($n))->first());
        }
        $this->giftMessage = $f('gift_message') ?: ($defaults['gift_message'] ?? 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!');
        $this->contacts = collect([
            ['role' => 'ខាងកូនប្រុស', 'name' => $this->groom, 'phone' => $f('groom_phone')],
            ['role' => 'ខាងកូនស្រី', 'name' => $this->bride, 'phone' => $f('bride_phone')],
            ['role' => 'ព័ត៌មានបន្ថែម', 'name' => '', 'phone' => $f('contact_phone')],
        ])->filter(fn ($c) => $c['phone'] !== '')->unique('phone')
            ->map(fn ($c) => $c + ['tel' => preg_replace('/[^\d+]/', '', $c['phone'])])->values();
        if ($on('cta_label') && $f('cta_label') !== '' && preg_match('#^https?://#i', $f('cta_url'))) {
            $this->ctaLabel = $f('cta_label');
            $this->ctaUrl = $f('cta_url');
        }

        // music: an uploaded file plays inline; a YouTube link plays in a hidden player; anything else opens in a tab
        $this->musicFile = $url($f('music_file'));
        $link = $f('music_url') ?: null;
        if (! $this->musicFile && $link && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $link, $m)) {
            $this->youtubeId = $m[1];
        } elseif (! $this->musicFile) {
            $this->musicUrl = $link;
        }
        $this->songTitle = $f('song_title');
    }

    /** True when the plan includes this field (always true on demo pages). */
    public function unlocked(string $key): bool
    {
        return $this->invitation ? $this->invitation->fieldUnlocked($key) : true;
    }

    /** Raw field value (trimmed string) for anything this class doesn't prepare. */
    public function field(string $key): string
    {
        return trim((string) ($this->fields[$key] ?? ''));
    }

    // ---------------- Khmer helpers ----------------

    public static function khDigits(int|string|null $v): string
    {
        return strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    }

    /** «ថ្ងៃអង្គារ ទី១៥ ខែធ្នូ ឆ្នាំ២០២៦» */
    public static function khDate(Carbon $d): string
    {
        return 'ថ្ងៃ'.self::KH_DAYS[$d->dayOfWeek].' ទី'.self::khDigits($d->day).' ខែ'.self::KH_MONTHS[$d->month - 1].' ឆ្នាំ'.self::khDigits($d->year);
    }

    /** 17, '00' → «៥:០០ ល្ងាច» (ព្រឹក / ថ្ងៃត្រង់ / រសៀល / ល្ងាច) */
    public static function khTime(int $hour, string $minutes): string
    {
        $period = $hour < 12 ? 'ព្រឹក' : ($hour === 12 ? 'ថ្ងៃត្រង់' : ($hour < 17 ? 'រសៀល' : 'ល្ងាច'));

        return self::khDigits(sprintf('%d:%s', $hour % 12 ?: 12, $minutes)).' '.$period;
    }

    /** A schedule row's time as typed («06:00 AM», «17:30», «5.30 pm») → Khmer; anything else is left as typed. */
    public static function scheduleTime(?string $t): string
    {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }

        return self::khTime($h, $m[2]);
    }

    public static function isKhmer(?string $s): bool
    {
        return (bool) preg_match('/\p{Khmer}/u', (string) $s);
    }

    /** Latin names Title Cased («SOKHA» → «Sokha»); Khmer names untouched. */
    public static function pretty(string $name): string
    {
        return $name === '' || self::isKhmer($name) ? $name : mb_convert_case(mb_strtolower($name), MB_CASE_TITLE);
    }
}
