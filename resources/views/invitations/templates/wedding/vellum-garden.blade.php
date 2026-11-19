@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Vellum Garden: a fine-art garden wedding suite on ivory linen. A linen envelope closed with
    // a wax seal (the couple's initials) opens on the first tap; inside, botanical line art draws
    // itself around arched photo frames, and every part of the day is its own "enclosure card"
    // (save-the-date calendar, program, venue, gift, RSVP) laid side by side on wide screens.
    // Everything personal comes from $fields; Khmer headings/labels below are template copy.
    // On demo pages $invitation is null, so every premium extra previews.
    // CSS public/css/invitations/vellum-garden.css · JS public/js/invitations/vellum-garden.js
    // Debug: ?open=1 skips the envelope (screenshots / reviewing).
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $vgAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);
    // watercolour / pressed-flower decor (public/images/invitations/vellum-garden/)
    $vgImg = fn (string $file) => $vgAsset('images/invitations/vellum-garden/decor/'.$file);
    $pressed = fn (string $name, string $pos) => '<img class="vg-pressed '.$pos.' is-'.$name.'" src="'.e($vgImg('pressed-'.$name.'.webp')).'" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">';

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $khDayShort = ['អា', 'ច', 'អ', 'ពុ', 'ព្រ', 'សុ', 'ស'];

    // Names may be typed in Khmer or English: Khmer gets the Khmer display face, Latin the script face
    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' vg-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([mb_strtoupper($groomName), mb_strtoupper($brideName)])->filter()->implode(' & ');

    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');
    $initialParts = preg_split('/\s*&\s*/u', $initials, 2);

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#b0654b';

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate
        ? 'ថ្ងៃ'.$khDays[$eventDate->dayOfWeek].' ទី'.$khDigits($eventDate->day).' ខែ'.$khMonths[$eventDate->month - 1].' ឆ្នាំ'.$khDigits($eventDate->year)
        : null;
    $timeKhmer = $eventDate
        ? 'វេលាម៉ោង '.$khDigits($eventDate->format('g:i')).' '.($eventDate->hour < 12 ? 'ព្រឹក' : ($eventDate->hour < 18 ? 'រសៀល' : 'ល្ងាច'))
        : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    // save-the-date calendar of the wedding month (weeks start on Sunday, like Khmer wall calendars)
    $calendar = null;
    if ($eventDate) {
        $first = $eventDate->copy()->startOfMonth();
        $cells = array_merge(array_fill(0, $first->dayOfWeek, null), range(1, $first->daysInMonth));
        $calendar = ['title' => 'ខែ'.$khMonths[$eventDate->month - 1].' '.$khDigits($eventDate->year), 'cells' => $cells, 'day' => $eventDate->day];
    }

    $venueName = $fields['venue_name'] ?? null;
    $venueAddress = ($isUnlocked('venue_address') && ! empty($fields['venue_address'])) ? $fields['venue_address'] : null;
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $fields['map_url'] ?? '')) ? $fields['map_url'] : null;
    if ($venueAddress && preg_match('#^https?://\S+$#i', trim($venueAddress))) {
        $mapLink = $mapLink ?: trim($venueAddress);
        $venueAddress = null;
    }
    $mapQuery = $venueAddress ?: $venueName;
    $mapPinned = false;
    if ($mapLink && preg_match('#[@=](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#', urldecode($mapLink), $pin)) {
        $mapQuery = $pin[1].','.$pin[2];
        $mapPinned = true;
    }
    $mapUrl = $mapLink ?: ($mapQuery ? 'https://maps.google.com/?q='.urlencode($mapQuery) : null);
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery
        ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed'
        : null;
    $showVenue = $venueName || $venueAddress || $mapLink;

    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? $fields['gift_account'] : null;
    $giftMessage = trim($fields['gift_message'] ?? '') ?: 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!';

    $personalNote = trim($fields['message'] ?? '');
    $letters = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', 'A gentle apology', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    // the second arch on wide screens: another gallery photo, so the hero isn't one lonely frame
    $coverAlt = $gallery->first(fn ($u) => $u !== $coverUrl);

    // gallery rows, repeating: two arches → one big photo → three small squares. Near the end a row
    // takes what's left (2 → a pair, 1 → a big one), so no row is ever half empty.
    $galRows = [];
    for ($i = 0, $r = 0, $n = $gallery->count(); $i < $n; $r++) {
        $want = [2, 1, 3][$r % 3];
        $size = $want <= $n - $i ? $want : min(2, $n - $i);
        $galRows[] = ['type' => [1 => 'big', 2 => 'pair', 3 => 'trio'][$size], 'items' => range($i, $i + $size - 1)];
        $i += $size;
    }

    // dress code: the colours the couple names (Khmer or English words, or #hex codes) become fabric swatches,
    // in the order they're written; the text itself always shows under them
    $dressCode = $isUnlocked('dress_code') ? trim($fields['dress_code'] ?? '') : '';
    $dressColours = [];
    if ($dressCode !== '') {
        $palette = [
            // Khmer (longest first, so «ក្រហមដិត» wins over «ក្រហម»)
            'ក្រហមដិត' => '#7d1f2b', 'ក្រហមចាស់' => '#7d1f2b', 'ក្រហម' => '#b3262e', 'ផ្កាឈូកខ្ចី' => '#efc7c2', 'ផ្កាឈូក' => '#e39aa6',
            'ទឹកក្រូច' => '#e0873d', 'លឿងខ្ចី' => '#f1dc98', 'លឿង' => '#e9c75b', 'មាស' => '#c9a14a', 'ប្រាក់' => '#c0c2c4',
            'បៃតងចាស់' => '#3f5a3a', 'បៃតងខ្ចី' => '#b5c99a', 'បៃតងស្រាល' => '#a9b98f', 'បៃតង' => '#5f7f4e',
            'ខៀវចាស់' => '#23345c', 'ខៀវខ្ចី' => '#a9c6e0', 'ខៀវ' => '#3d64a8', 'ស្វាយខ្ចី' => '#c9b6dc', 'ស្វាយ' => '#6d4a8c',
            'ត្នោតខ្ចី' => '#b08d6e', 'ត្នោត' => '#7a5338', 'ប្រផេះ' => '#9a9a96', 'ក្រែម' => '#efe4cc', 'បេហ្ស' => '#dbc8a8',
            'សំប៉ាញ' => '#e8d6b0', 'ខ្មៅ' => '#1d1c1a', 'ពណ៌ស' => '#fbfaf6',
            // English
            'dusty pink' => '#d9a5a5', 'dusty blue' => '#8fa9c0', 'sky blue' => '#a9c6e0', 'light blue' => '#a9c6e0', 'navy blue' => '#23345c',
            'burgundy' => '#7d1f2b', 'maroon' => '#6e1f26', 'wine' => '#722f37', 'red' => '#b3262e', 'blush' => '#efc7c2',
            'pink' => '#e39aa6', 'rose' => '#d58d93', 'peach' => '#f2c1a0', 'coral' => '#ee8a72', 'terracotta' => '#c0694e',
            'orange' => '#e0873d', 'mustard' => '#d0a53a', 'yellow' => '#e9c75b', 'gold' => '#c9a14a', 'golden' => '#c9a14a',
            'champagne' => '#e8d6b0', 'silver' => '#c0c2c4', 'sage' => '#a9b98f', 'olive' => '#77804a', 'emerald' => '#2f7a5a',
            'green' => '#5f7f4e', 'navy' => '#23345c', 'teal' => '#2f7c80', 'blue' => '#3d64a8', 'lavender' => '#c9b6dc',
            'lilac' => '#c8a8d3', 'purple' => '#6d4a8c', 'brown' => '#7a5338', 'beige' => '#dbc8a8', 'nude' => '#d9b99b',
            'taupe' => '#a39382', 'cream' => '#efe4cc', 'ivory' => '#f6f0e1', 'white' => '#fbfaf6', 'black' => '#1d1c1a',
            'charcoal' => '#3b3b3b', 'grey' => '#9a9a96', 'gray' => '#9a9a96',
        ];
        $scan = mb_strtolower($dressCode);
        $found = [];
        $take = function (string $pattern, callable $hex, callable $name) use (&$scan, &$found) {
            if (preg_match_all($pattern, $scan, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$text, $at]) {
                    $found[] = ['at' => $at, 'hex' => $hex($text), 'name' => $name($text)];
                    $scan = substr_replace($scan, str_repeat(' ', strlen($text)), $at, strlen($text)); // matched once only
                }
            }
        };
        $take('/#[0-9a-f]{6}\b/', fn ($t) => $t, fn ($t) => mb_strtoupper($t));
        foreach ($palette as $word => $hex) {
            $isLatin = ! preg_match('/\p{Khmer}/u', $word);
            $take($isLatin ? '/\b'.preg_quote($word, '/').'\b/u' : '/'.preg_quote($word, '/').'/u', fn () => $hex, fn ($t) => $isLatin ? ucfirst($t) : $t);
        }
        // «ស» (white) on its own, between spaces / commas / «ឬ» / «និង»
        $take('/(?<=^|[\s,\/&+]|ឬ|និង)ស(?=$|[\s,\/&+។]|ឬ|និង)/u', fn () => '#fbfaf6', fn () => 'ស');
        $dressColours = collect($found)->sortBy('at')->unique('hex')->take(6)->values()->all();
    }
    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))->values();
    // a chapter title may be «Place | when»: the story card shows both, the map uses them for its pins
    $splitTitle = fn ($t) => array_pad(array_map('trim', explode('|', (string) $t, 2)), 2, '');

    // scratch to reveal: three perforated stamps hiding gallery photos (taken from the end of the gallery,
    // so they aren't the photos the hero already shows)
    $scratchDefaults = ['ថ្ងៃដែលយើងជួបគ្នា', 'ស្នាមញញឹមរបស់យើង', 'រហូតទៅ'];
    $scratchCards = $gallery->count() >= 2
        ? collect(range(0, min(3, $gallery->count()) - 1))->map(function ($k) use ($gallery, $captions, $scratchDefaults) {
            $i = $gallery->count() - 1 - $k;

            return ['src' => $gallery[$i], 'caption' => ($captions[$i] ?? '') ?: $scratchDefaults[$k]];
        })->reverse()->values()->all()
        : [];

    // memory map (shared x-memory-map): one pin per story chapter — place, when and a gallery photo
    // (the chapter's text stays in the story cards above, so it isn't repeated in the pin)
    $places = $story->take(6)->map(function ($c, $i) use ($splitTitle, $gallery, $khDigits) {
        [$place, $when] = $splitTitle($c['title'] ?? '');

        return [
            'name' => $place !== '' ? $place : 'អនុស្សាវរីយ៍ទី '.$khDigits($i + 1),
            'date' => $when,
            'photo' => $gallery->isNotEmpty() ? $gallery[($i + 1) % $gallery->count()] : null,
        ];
    })->all();
    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))->values();
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    $schedTime = function (?string $t) use ($khDigits): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }
        $period = $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));

        return $khDigits(sprintf('%02d:%s', $h % 12 ?: 12, $m[2])).' '.$period;
    };

    $receptionRow = $schedule->filter(fn ($row) => ! empty($row['time']))->last();
    $receptionLabel = trim($receptionRow['label'] ?? '') ?: 'ពិធីអាពាហ៍ពិពាហ៍';
    $receptionTime = $receptionRow ? $schedTime($receptionRow['time']) : ($eventDate ? $schedTime($eventDate->format('H:i')) : null);
    $receptionCustom = trim($fields['reception_message'] ?? '');

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', $recipient) : null;
    $rsvpQuestion = trim($fields['rsvp_message'] ?? '') ?: 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមជាមួយយើងខ្ញុំដែរឬទេ?';

    $parent = function (string $key, string $title) use ($fields): ?string {
        $n = trim($fields[$key] ?? '');

        return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
    };
    $groomParents = array_values(array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]));
    $brideParents = array_values(array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]));

    $tel = fn (string $p) => preg_replace('/[^\d+]/', '', $p);
    $contacts = collect([
        ['role' => 'ខាងកូនប្រុស', 'name' => $groomPretty, 'phone' => trim($fields['groom_phone'] ?? '')],
        ['role' => 'ខាងកូនស្រី', 'name' => $bridePretty, 'phone' => trim($fields['bride_phone'] ?? '')],
        ['role' => 'ព័ត៌មានបន្ថែម', 'name' => '', 'phone' => trim($fields['contact_phone'] ?? '')],
    ])->filter(fn ($c) => $c['phone'] !== '')->unique('phone')->values();

    $musicFile = ! empty($fields['music_file']) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $fields['music_url'] ?? null;
    $youtubeId = null;
    if (! $musicFile && $musicUrl && preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
        $youtubeId = $m[1];
    }

    // pressed wax: an irregular disc with a softly rippled rim
    $sealEdge = 'M'.collect(range(0, 89))->map(function ($i) {
        $a = $i / 90 * 2 * M_PI;
        $r = 50 + 2.6 * sin($a * 7 + .4) + 1.6 * sin($a * 17 + 1.1) + .9 * sin($a * 31);

        return round(cos($a) * $r, 2).' '.round(sin($a) * $r, 2);
    })->implode('L').'Z';

    // botanical line art: a stem that sways a little, leaves alternating up it, a small bloom on top.
    // Every stroke has pathLength=1 so the CSS can draw it in, one after the other (--d = order).
    $sprig = function (int $seed = 1, int $count = 8, float $height = 200, bool $bloom = true): string {
        mt_srand($seed);
        $pts = [];
        for ($i = 0; $i <= 24; $i++) {
            $t = $i / 24;
            $pts[] = [round(sin($t * 2.6 + $seed) * 7 * $t, 1), round(-$t * $height, 1)];
        }
        $out = '<path class="st" pathLength="1" style="--d:0" d="M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $pts)).'"/>';
        for ($k = 0; $k < $count; $k++) {
            $t = .14 + $k / $count * .78;
            $p = $pts[(int) round($t * 24)];
            $side = $k % 2 ? 1 : -1;
            $len = round((30 - $k * 1.6) * (mt_rand(88, 112) / 100), 1);
            $w = round($len * .36, 1);
            $ang = $side * mt_rand(38, 58);
            $leaf = "M0 0C{$w} -".round($len * .3, 1).' '.round($w * .85, 1).' -'.round($len * .82, 1)." 0 -{$len}C-".round($w * .85, 1).' -'.round($len * .82, 1)." -{$w} -".round($len * .3, 1).' 0 0Z';
            $out .= '<g transform="translate('.$p[0].' '.$p[1].') rotate('.$ang.')">'
                .'<path class="lf" pathLength="1" style="--d:'.($k + 1).'" d="'.$leaf.'"/>'
                .'<path class="st" pathLength="1" style="--d:'.($k + 1.5).'" d="M0 -2L0 -'.round($len * .78, 1).'"/></g>';
        }
        if ($bloom) {
            [$bx, $by] = end($pts);
            $petals = '';
            for ($j = 0; $j < 5; $j++) {
                $petals .= '<path class="pt" pathLength="1" style="--d:'.($count + 1 + $j * .3).'" transform="rotate('.($j * 72).')" d="M0 0C-7 -4 -8 -13 -3 -15C-1 -16 0 -14 0 -13C0 -14 1 -16 3 -15C8 -13 7 -4 0 0Z"/>';
            }
            $out .= '<g transform="translate('.$bx.' '.($by - 6).')">'.$petals.'<circle class="ct" r="2.6" style="--d:'.($count + 2.6).'"/></g>';
        }

        return $out;
    };

    $calendarSprig = $sprig(4, 5, 120, true);
    $gateSprig = $sprig(3, 7, 170, true);

    // a leaf for the vine / wreath below: base at 0 0, pointing up (-y), $len long
    $leafD = function (float $len): string {
        $w = round($len * .38, 1);

        return "M0 0C{$w} -".round($len * .3, 1).' '.round($w * .85, 1).' -'.round($len * .82, 1)." 0 -{$len}C-".round($w * .85, 1).' -'.round($len * .82, 1)." -{$w} -".round($len * .3, 1).' 0 0Z';
    };
    // a five-petal bloom (petals draw in, then the whole flower opens: .bloom)
    $bloom = function (float $x, float $y, float $d, float $size = 1) {
        $petals = '';
        for ($j = 0; $j < 5; $j++) {
            $petals .= '<path class="pt" pathLength="1" style="--d:'.$d.'" transform="rotate('.($j * 72).')" d="M0 0C-6 -3.5 -7 -11 -2.6 -13C-.9 -13.8 0 -12 0 -11.2C0 -12 .9 -13.8 2.6 -13C7 -11 6 -3.5 0 0Z"/>';
        }

        return '<g transform="translate('.$x.' '.$y.') scale('.$size.')"><g class="bloom" style="--d:'.$d.'">'.$petals.'<circle class="ct" r="2.4"/></g></g>';
    };

    // section headings: a small sprig beside the heading that draws itself, alternating left / right
    $headSprigN = 0;
    $headSprig = function () use ($sprig, &$headSprigN): string {
        $headSprigN++;

        return '<svg class="vg-head-sprig '.($headSprigN % 2 ? 'l' : 'r').' vg-draw" viewBox="-40 -135 80 140" aria-hidden="true">'.$sprig(10 + $headSprigN, 5, 120, true).'</svg>';
    };

    // a vine divider between sections: a bloom opens in the middle, then two vines grow out from it,
    // leaves unfolding along them, a bud at each end
    $vine = function (int $seed) use ($leafD, $bloom): string {
        mt_srand($seed);
        $out = '';
        foreach ([-1, 1] as $s) {
            $pts = [];
            for ($i = 0; $i <= 30; $i++) {
                $t = $i / 30;
                $pts[] = [round($s * (16 + $t * 245), 1), round(sin($t * 5.2 + $seed) * 6 * $t, 1)];
            }
            $out .= '<path class="st" pathLength="1" style="--d:2" d="M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $pts)).'"/>';
            for ($k = 0; $k < 7; $k++) {
                [$px, $py] = $pts[3 + $k * 4];
                $ang = $s * ($k % 2 ? mt_rand(118, 138) : mt_rand(42, 62));
                $out .= '<g transform="translate('.$px.' '.$py.') rotate('.$ang.')"><path class="lf" pathLength="1" style="--d:'.(2.5 + $k * 1.4).'" d="'.$leafD(round((17 - $k * 1.2) * mt_rand(88, 112) / 100, 1)).'"/></g>';
            }
            [$ex, $ey] = end($pts);
            $out .= '<circle class="ct bud" cx="'.$ex.'" cy="'.$ey.'" r="2.2"/>';
        }

        return '<div class="vg-vine" aria-hidden="true"><svg class="vg-draw" viewBox="-275 -24 550 48">'.$out.$bloom(0, 0, 0, 1.1).'</svg></div>';
    };

    // the closing wreath around the wax seal: two leafy branches climb from the bottom round each side
    $wreath = (function () use ($leafD, $bloom): string {
        mt_srand(21);
        $r = 74;
        $out = '';
        foreach ([-1, 1] as $s) {
            $pts = [];
            for ($i = 0; $i <= 28; $i++) {
                $a = deg2rad(90 - $s * 158 * $i / 28);
                $pts[] = [round(cos($a) * $r, 1), round(sin($a) * $r, 1), $a];
            }
            $out .= '<path class="st" pathLength="1" style="--d:1" d="M'.implode(' L', array_map(fn ($p) => $p[0].' '.$p[1], $pts)).'"/>';
            for ($k = 0; $k < 9; $k++) {
                [$px, $py, $a] = $pts[2 + $k * 3];
                $tan = rad2deg(atan2(cos($a) * -$s, -sin($a) * -$s)) + 90; // along the branch, the way it grows
                $ang = round($tan + ($k % 2 ? 38 : -38), 1);
                $out .= '<g transform="translate('.$px.' '.$py.') rotate('.$ang.')"><path class="lf" pathLength="1" style="--d:'.(1.6 + $k * 1.1).'" d="'.$leafD(round((15 - $k * .5) * mt_rand(90, 110) / 100, 1)).'"/></g>';
            }
            foreach ([11, 23] as $j => $at) {
                $out .= $bloom($pts[$at][0], $pts[$at][1], 6 + $j * 3, .55);
            }
        }

        return '<svg class="vg-wreath vg-draw" viewBox="-100 -100 200 200" aria-hidden="true">'.$out.'</svg>';
    })();

    // gallery sprigs: beside the arches (outer side) and on two corners of the big photo
    $galSprigs = ['l' => $sprig(31, 5, 140, true), 'r' => $sprig(32, 5, 140, true)];
    $galCorner = ['tl' => $sprig(33, 6, 150, true), 'br' => $sprig(34, 6, 150, true)];
    $rv = 'vg-rv';
    $skipGate = request()->boolean('open');
    $headEn = fn (string $t) => '<p class="vg-script-sm">'.e($t).'</p>';
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Pinyon+Script&family=Moulpali&family=Noto+Serif+Khmer:wght@400;500;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $vgAsset('css/invitations/vellum-garden.css') }}">
<noscript><style>.vg-gate{display:none}.vg-sound{opacity:1}</style></noscript>

<div class="vg{{ $skipGate ? ' is-open' : '' }}" style="--vg-accent: {{ $accent }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      <radialGradient id="vg-wax" cx=".38" cy=".32" r=".8">
        <stop offset="0" stop-color="#fff" stop-opacity=".38"/><stop offset=".35" stop-color="#fff" stop-opacity="0"/>
        <stop offset="1" stop-color="#000" stop-opacity=".28"/>
      </radialGradient>
      {{-- a little pressed wildflower: the divider / footer ornament --}}
      <symbol id="vg-flower" viewBox="-30 -16 60 32">
        <g fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round">
          <path d="M-29 4C-18 0-10 2-4 6M29 4C18 0 10 2 4 6"/>
          <path d="M-18 2C-20-4-16-8-12-6-12-2-15 1-18 2ZM18 2C20-4 16-8 12-6 12-2 15 1 18 2Z" fill="currentColor" fill-opacity=".15"/>
          <g transform="translate(0 -1)" fill="currentColor" fill-opacity=".14">
            <path d="M0 0C-5-3-6-10-2-12 0-12.6 0-11 0-10 0-11 0-12.6 2-12 6-10 5-3 0 0Z"/>
            <path transform="rotate(72)" d="M0 0C-5-3-6-10-2-12 0-12.6 0-11 0-10 0-11 0-12.6 2-12 6-10 5-3 0 0Z"/>
            <path transform="rotate(144)" d="M0 0C-5-3-6-10-2-12 0-12.6 0-11 0-10 0-11 0-12.6 2-12 6-10 5-3 0 0Z"/>
            <path transform="rotate(216)" d="M0 0C-5-3-6-10-2-12 0-12.6 0-11 0-10 0-11 0-12.6 2-12 6-10 5-3 0 0Z"/>
            <path transform="rotate(288)" d="M0 0C-5-3-6-10-2-12 0-12.6 0-11 0-10 0-11 0-12.6 2-12 6-10 5-3 0 0Z"/>
            <circle r="2" fill="currentColor" fill-opacity="1"/>
          </g>
        </g>
      </symbol>
    </defs>
  </svg>

  {{-- ============ 0 · ENVELOPE (tap the wax seal) ============ --}}
  @unless ($skipGate)
    <div class="vg-gate" id="vg-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      {{-- olive-leaf shadows drifting over linen behind the envelope (6 s forward/backward loop, muted) --}}
      <video class="vg-gate-video" autoplay muted loop playsinline preload="auto" aria-hidden="true" tabindex="-1"
        poster="{{ $vgImg('gate-portrait.jpg') }}" data-poster-wide="{{ $vgImg('gate-landscape.jpg') }}">
        <source src="{{ $vgImg('gate-portrait.mp4') }}" type="video/mp4" media="(max-aspect-ratio: 1/1)">
        <source src="{{ $vgImg('gate-landscape.mp4') }}" type="video/mp4">
      </video>
      <div class="vg-gate-in">
        <p class="vg-gate-kicker">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
        @if ($groomPretty || $bridePretty)
          <p class="vg-gate-names">
            <span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>
            @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
            <span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>
          </p>
        @endif

        <div class="vg-env" id="vg-env">
          <div class="vg-env-back"></div>
          <div class="vg-env-card" aria-hidden="true">
            <svg class="vg-env-card-orn"><use href="#vg-flower"/></svg>
            <p>សូមគោរពអញ្ជើញ</p>
          </div>
          <div class="vg-env-pocket"></div>
          <div class="vg-env-flap"><div class="vg-env-flap-in"></div></div>
          <svg class="vg-env-sprig vg-draw" viewBox="-40 -215 80 220" aria-hidden="true">{!! $gateSprig !!}</svg>
          <div class="vg-env-label">
            <p class="vg-env-to">សូមគោរពជូន</p>
            <p class="vg-env-guest{{ $kh($recipientName ?? '') }}">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</p>
          </div>
          <button type="button" class="vg-seal-btn" id="vg-open" aria-label="បើកសំបុត្រ">
            <svg class="vg-seal" viewBox="-60 -60 120 120" aria-hidden="true">
              <g class="vg-seal-half l"><path d="{{ $sealEdge }}"/><path d="{{ $sealEdge }}" fill="url(#vg-wax)"/></g>
              <g class="vg-seal-half r"><path d="{{ $sealEdge }}"/><path d="{{ $sealEdge }}" fill="url(#vg-wax)"/></g>
              <g class="vg-seal-face">
                <circle r="38" fill="none" stroke="#000" stroke-opacity=".22" stroke-width="1.4"/>
                <circle r="33" fill="none" stroke="#fff" stroke-opacity=".28" stroke-width=".8" stroke-dasharray="1.5 3"/>
                <text y="{{ $isKh($initials) ? 7 : 10 }}" text-anchor="middle" class="{{ $isKh($initials) ? 'vg-kh' : '' }}" font-size="{{ mb_strlen($initials) > 3 ? 20 : 28 }}">{{ $initials }}</text>
              </g>
            </svg>
          </button>
        </div>

        <p class="vg-gate-hint">ចុចលើត្រាក្រមួន ដើម្បីបើកសំបុត្រ</p>
      </div>
    </div>
  @endunless

  {{-- rose petals and a few leaves falling in 3D (shared component; in front of the envelope too, then over the page).
       A tap drops a handful, a revealed stamp bursts into petals, a map pin lets a few fall. --}}
  <x-flower-drop class="vg-flowers" :color="$accent" shape="rose" leaf-color="#9aab84" density=".55" />

  <main class="vg-page" id="vg-top">

    {{-- ============ 1 · HERO: arched portrait + names ============ --}}
    <section class="vg-hero">
      <div class="vg-hero-art {{ $rv }}">
        {{-- the arched photo inside a watercolour vine frame (the frame grows in from the bottom) --}}
        <div class="vg-arch-wrap">
          <figure class="vg-arch vg-arch-main">
            @if ($coverUrl)
              <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" fetchpriority="high">
            @else
              <div class="vg-arch-empty{{ $kh($initials) }}">{{ $initials }}</div>
            @endif
          </figure>
          <img class="vg-arch-frame" src="{{ $vgImg('arch-frame.webp') }}" alt="" aria-hidden="true" decoding="async" draggable="false">
        </div>
        @if ($coverAlt)
          <figure class="vg-arch vg-arch-small" aria-hidden="true"><img src="{{ $coverAlt }}" alt="" loading="lazy"></figure>
        @endif
      </div>

      <div class="vg-hero-text">
        <p class="vg-eyebrow {{ $rv }}">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
        <p class="vg-script-sm {{ $rv }}">The wedding of</p>
        <h1 class="vg-hero-names {{ $rv }} d1" aria-label="{{ $coupleTitle }}">
          @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
          @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
          @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
        </h1>
        @if ($eventDate)
          <div class="vg-hero-date {{ $rv }} d2">
            <span>{{ $khDays[$eventDate->dayOfWeek] }}</span>
            <b>{{ $eventDate->format('d') }}</b>
            <span>{{ $eventDate->format('m · Y') }}</span>
          </div>
          <p class="vg-hero-kdate {{ $rv }} d2">{{ $dateKhmer }}</p>
        @endif
        @if ($venueName)<p class="vg-hero-venue {{ $rv }} d3{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
        <a class="vg-scroll {{ $rv }} d3" href="#vg-invite" aria-label="អានបន្ត">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v15M6 13l6 6 6-6"/></svg>
        </a>
      </div>
    </section>

    {{-- ============ 2 · THE INVITATION (vellum card) ============ --}}
    <section class="vg-sec" id="vg-invite">
      <img class="vg-bg-sprig l" src="{{ $vgImg('sprig.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">
      <img class="vg-bg-sprig r" src="{{ $vgImg('sprig.webp') }}" alt="" aria-hidden="true" loading="lazy" decoding="async" draggable="false">
      <article class="vg-card vg-vellum vg-invite {{ $rv }}">
        {!! $pressed('rose', 'tr') !!}
        {!! $pressed('fern', 'bl') !!}
        <svg class="vg-orn" aria-hidden="true"><use href="#vg-flower"/></svg>
        @if ($groomParents || $brideParents)
          <div class="vg-parents">
            <div>
              <p class="vg-label">មាតាបិតាខាងកូនប្រុស</p>
              @foreach ($groomParents as $line)<p class="vg-parent">{{ $line }}</p>@endforeach
            </div>
            <span class="vg-parents-amp" aria-hidden="true">&amp;</span>
            <div>
              <p class="vg-label">មាតាបិតាខាងកូនស្រី</p>
              @foreach ($brideParents as $line)<p class="vg-parent">{{ $line }}</p>@endforeach
            </div>
          </div>
        @endif

        <h2 class="vg-invite-title">សូមគោរពអញ្ជើញ</h2>
        @if (! empty($recipientName))
          <p class="vg-guest{{ $kh($recipientName) }}">{{ $recipientName }}</p>
        @else
          <p class="vg-body">ឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា</p>
        @endif
        <p class="vg-body">អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ</p>

        @if ($groomPretty || $bridePretty)
          <div class="vg-couple">
            @if ($groomPretty)
              <div><p class="vg-label">កូនប្រុសនាម</p><p class="vg-couple-name{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>
            @endif
            @if ($groomPretty && $bridePretty)<svg class="vg-couple-orn" aria-hidden="true"><use href="#vg-flower"/></svg>@endif
            @if ($bridePretty)
              <div><p class="vg-label">កូនស្រីនាម</p><p class="vg-couple-name{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>
            @endif
          </div>
        @endif

        @if ($eventDate)
          <div class="vg-when">
            <p>{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="vg-soft">{{ $khmerLunarDate }}</p>@endif
            <p class="vg-accent-text">{{ $timeKhmer }}</p>
          </div>
        @endif
      </article>

      @if ($personalNote !== '')
        <aside class="vg-note {{ $rv }} d1">
          <p class="vg-script">a note from us</p>
          <p class="vg-note-text">{{ $personalNote }}</p>
        </aside>
      @endif
    </section>

    {{-- ============ 3 · SAVE THE DATE: calendar + countdown ============ --}}
    @if ($calendar)
      {!! $vine(2) !!}
      <section class="vg-sec vg-split" id="vg-date">
        <div class="vg-split-head {{ $rv }}">
          <p class="vg-eyebrow">ចំណាំថ្ងៃដ៏មានអត្ថន័យ</p>
          {!! $headEn('Save the date') !!}
          <p class="vg-body">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="vg-body vg-soft">{{ $khmerLunarDate }}</p>@endif
          @if ($showCountdown)
            <div class="vg-count" id="vg-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
              <div><b data-u="d">០</b><span>ថ្ងៃ</span></div>
              <div><b data-u="h">០</b><span>ម៉ោង</span></div>
              <div><b data-u="m">០</b><span>នាទី</span></div>
              <div><b data-u="s">០</b><span>វិនាទី</span></div>
            </div>
          @endif
        </div>
        <div class="vg-card vg-cal {{ $rv }} d1">
          <svg class="vg-cal-sprig vg-draw" viewBox="-40 -140 80 145" aria-hidden="true">{!! $calendarSprig !!}</svg>
          <p class="vg-cal-title">{{ $calendar['title'] }}</p>
          <div class="vg-cal-grid" role="grid" aria-label="{{ $calendar['title'] }}">
            @foreach ($khDayShort as $d)<span class="vg-cal-dow" role="columnheader">{{ $d }}</span>@endforeach
            @foreach ($calendar['cells'] as $c)
              @if ($c === null)
                <span></span>
              @elseif ($c === $calendar['day'])
                <span class="vg-cal-day is-day" role="gridcell" aria-current="date">
                  {{ $khDigits($c) }}
                  <svg viewBox="0 0 60 50" aria-hidden="true"><path pathLength="1" d="M33 6C14 4 4 16 6 27c2 12 16 18 28 16 14-2 22-12 20-22C52 10 40 4 26 8"/></svg>
                </span>
              @else
                <span class="vg-cal-day" role="gridcell">{{ $khDigits($c) }}</span>
              @endif
            @endforeach
          </div>
          <p class="vg-cal-time">{{ $timeKhmer }}</p>
        </div>
      </section>
    @endif

    {{-- ============ 4 · OUR STORY ============ --}}
    @if ($story->isNotEmpty())
      {!! $vine(3) !!}
      <section class="vg-sec" id="vg-story">
        <div class="vg-head {{ $rv }}">
          {!! $headSprig() !!}
          <p class="vg-eyebrow">ដំណើររឿងស្នេហារបស់យើង</p>
          {!! $headEn('Our story') !!}
        </div>
        <ol class="vg-story">
          @foreach ($story as $i => $chapter)
            @php [$chTitle, $chWhen] = $splitTitle($chapter['title'] ?? ''); @endphp
            <li class="vg-story-item {{ $rv }}">
              <span class="vg-story-pin" aria-hidden="true"><svg><use href="#vg-flower"/></svg></span>
              <div class="vg-card vg-story-card">
                <span class="vg-story-num">{{ $khDigits(str_pad($i + 1, 2, '0', STR_PAD_LEFT)) }}@if ($chWhen !== '') · {{ $chWhen }}@endif</span>
                @if ($chTitle !== '')<h3>{{ $chTitle }}</h3>@endif
                @if (! empty($chapter['text']))<p>{{ $chapter['text'] }}</p>@endif
              </div>
            </li>
          @endforeach
        </ol>
      </section>
    @endif

    {{-- ============ 4b · OUR PLACES: the shared memory map (one pin per story chapter) ============ --}}
    @if ($places)
      <section class="vg-sec" id="vg-places">
        <div class="vg-head {{ $rv }}">
          {!! $headSprig() !!}
          <p class="vg-eyebrow">ទីកន្លែងអនុស្សាវរីយ៍</p>
          {!! $headEn('Our places') !!}
          <p class="vg-label">ចុចលើបេះដូងនីមួយៗ ដើម្បីមើលរូបភាព</p>
        </div>
        <x-memory-map :places="$places" :label="mb_strtolower($groomPretty && $bridePretty ? $groomPretty.' & '.$bridePretty : 'our garden')"
          seed="11" photo-class="vg-ph-one" close-label="បិទ" class="vg-places-map {{ $rv }}" />
      </section>
    @endif

    {{-- ============ 5 · PROGRAM + RECEPTION CARD ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty() || $receptionTime || $receptionCustom !== '')
      {!! $vine(4) !!}
      <section class="vg-sec vg-pair" id="vg-program">
        @if ($scheduleImg || $schedule->isNotEmpty())
          <div class="vg-card vg-program {{ $rv }}">
            {!! $pressed('leaf', 'tr') !!}
            <p class="vg-eyebrow">កម្មវិធីសិរីមង្គល</p>
            {!! $headEn('Order of the day') !!}
            @if ($scheduleImg)
              <a class="vg-sched-img" href="{{ $scheduleImg }}" target="_blank" rel="noopener">
                <img src="{{ $scheduleImg }}" alt="កម្មវិធីអាពាហ៍ពិពាហ៍" loading="lazy">
              </a>
            @else
              <ul class="vg-sched">
                @foreach ($schedule as $row)
                  <li>
                    <span class="t">{{ ! empty($row['time']) ? $schedTime($row['time']) : '' }}</span>
                    <span class="dot" aria-hidden="true"></span>
                    <span class="l">{{ $row['label'] ?? '' }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
          </div>
        @endif

        @if ($receptionTime || $receptionCustom !== '' || $venueName)
          <div class="vg-card vg-vellum vg-reception {{ $rv }} d1">
            {!! $pressed('lavender', 'tl') !!}
            <svg class="vg-orn" aria-hidden="true"><use href="#vg-flower"/></svg>
            <p class="vg-eyebrow">សំបុត្រពិធីជប់លៀង</p>
            {!! $headEn('Reception') !!}
            @if (! empty($recipientName))<p class="vg-guest sm{{ $kh($recipientName) }}">{{ $recipientName }}</p>@endif
            @if ($receptionCustom !== '')
              <p class="vg-body">{{ $receptionCustom }}</p>
            @else
              <p class="vg-body">និងញាតិមិត្ត អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយសក្នុង{{ $receptionLabel }}
                @if ($receptionTime) វេលាម៉ោង {{ $receptionTime }}@endif
                @if ($venueName) នៅ {{ $venueName }}@endif
                ដោយមេត្រីភាព ។</p>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 5b · SCRATCH TO REVEAL: three perforated stamps hiding photos ============ --}}
    @if ($scratchCards)
      <section class="vg-sec" id="vg-scratch">
        <div class="vg-head {{ $rv }}">
          {!! $headSprig() !!}
          <p class="vg-eyebrow">កោសដើម្បីមើលរូបភាព</p>
          {!! $headEn('A little surprise') !!}
          <p class="vg-label">ប្រើម្រាមដៃកោសលើតែមនីមួយៗ</p>
        </div>
        <div class="vg-stamps" style="--n: {{ count($scratchCards) }}">
          @foreach ($scratchCards as $i => $card)
            <x-scratch-card stamp class="vg-stamp {{ $rv }}" style="--i: {{ $i }}; --tilt: {{ [-2.5, 1.8, -1.4][$i % 3] }}deg"
              :caption="$card['caption']" cover="ស្នេហា" hint="កោសមើល" :heart="true"
              label="កោសដើម្បីមើលរូបភាពទី {{ $khDigits($i + 1) }}" :reveal-sound="$sfx('reveal.mp3')">
              <button type="button" class="vg-ph-one" data-full="{{ $card['src'] }}" data-cap="{{ $card['caption'] }}" aria-label="មើលរូបភាពធំ">
                <img src="{{ $card['src'] }}" alt="" loading="lazy" decoding="async" draggable="false">
              </button>
            </x-scratch-card>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 6 · GALLERY: arched windows ============ --}}
    @if ($gallery->isNotEmpty())
      {!! $vine(5) !!}
      <section class="vg-sec vg-wide" id="vg-gallery">
        <div class="vg-head {{ $rv }}">
          {!! $headSprig() !!}
          <p class="vg-eyebrow">កម្រងអនុស្សាវរីយ៍</p>
          {!! $headEn('Moments of us') !!}
        </div>
        {{-- rows: two arches → one big photo → three squares, repeating (built in $galRows above) --}}
        <div class="vg-gal">
          @foreach ($galRows as $row)
            <div class="vg-gal-row is-{{ $row['type'] }}">
              @foreach ($row['items'] as $k => $i)
                @php $url = $gallery[$i]; @endphp
                <figure class="vg-gal-item {{ $rv }}" style="--i:{{ $k }}">
                  @if ($row['type'] === 'pair')
                    <svg class="vg-gal-sprig {{ $k ? 'r' : 'l' }} vg-draw" viewBox="-40 -155 80 160" aria-hidden="true">{!! $galSprigs[$k ? 'r' : 'l'] !!}</svg>
                  @elseif ($row['type'] === 'big')
                    <svg class="vg-gal-corner tl vg-draw" viewBox="-40 -165 80 170" aria-hidden="true">{!! $galCorner['tl'] !!}</svg>
                    <svg class="vg-gal-corner br vg-draw" viewBox="-40 -165 80 170" aria-hidden="true">{!! $galCorner['br'] !!}</svg>
                  @endif
                  <button type="button" class="vg-gal-btn" data-index="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="មើលរូបភាពទី {{ $khDigits($i + 1) }}">
                    <img src="{{ $url }}" alt="{{ $coupleTitle }} {{ $i + 1 }}" loading="lazy">
                  </button>
                  @if (! empty($captions[$i]))<figcaption>{{ $captions[$i] }}</figcaption>@endif
                </figure>
              @endforeach
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 7 · VENUE ============ --}}
    @if ($showVenue)
      {!! $vine(6) !!}
      <section class="vg-sec vg-split vg-venue" id="vg-venue">
        <div class="vg-split-head {{ $rv }}">
          <p class="vg-eyebrow">ទីតាំងប្រារព្ធពិធី</p>
          {!! $headEn('Where we gather') !!}
          @if ($venueName)<p class="vg-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="vg-body vg-soft">{{ $venueAddress }}</p>@endif
          @if ($mapUrl && ($venueAddress || $mapLink))
            <a class="vg-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
              មើលផែនទី
            </a>
          @endif
        </div>
        @if ($mapEmbed)
          <div class="vg-map {{ $rv }} d1">
            <iframe src="{{ $mapEmbed }}" title="Google Maps" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 7b · DRESS CODE: the colours named in dress_code become fabric swatches ============ --}}
    @if ($dressCode !== '')
      <section class="vg-sec" id="vg-dress">
        <div class="vg-card vg-vellum vg-dress {{ $rv }}">
          {!! $pressed('babys-breath', 'tr') !!}
          <svg class="vg-dress-sprig l vg-draw" viewBox="-40 -135 80 140" aria-hidden="true">{!! $sprig(41, 5, 120, true) !!}</svg>
          <svg class="vg-dress-sprig r vg-draw" viewBox="-40 -135 80 140" aria-hidden="true">{!! $sprig(42, 5, 120, true) !!}</svg>
          <svg class="vg-orn" aria-hidden="true"><use href="#vg-flower"/></svg>
          <p class="vg-eyebrow">សម្លៀកបំពាក់</p>
          {!! $headEn('Dress code') !!}
          @if ($dressColours)
            <ul class="vg-swatches" aria-label="ពណ៌សម្លៀកបំពាក់">
              @foreach ($dressColours as $i => $c)
                <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                  <span class="vg-swatch" aria-hidden="true"></span>
                  <span class="vg-swatch-name{{ $kh($c['name']) ? ' is-kh' : '' }}">{{ $c['name'] }}</span>
                </li>
              @endforeach
            </ul>
          @endif
          <p class="vg-body">{{ $dressCode }}</p>
        </div>
      </section>
    @endif

    {{-- ============ 8 · GIFT + RSVP (two enclosure cards) ============ --}}
    @if ($giftQr || $showRsvp)
      <section class="vg-sec vg-pair" id="vg-reply">
        @if ($showRsvp)
          <div class="vg-card vg-rsvp {{ $rv }}" id="vg-rsvp">
            <p class="vg-eyebrow">សូមបញ្ជាក់ការចូលរួម</p>
            {!! $headEn('Kindly reply') !!}
            <p class="vg-body">{{ $rsvpQuestion }}</p>
            <div class="vg-rsvp-opts" id="vg-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
              <button type="button" class="vg-opt" data-status="yes" role="radio" aria-checked="false"><span class="box" aria-hidden="true"></span>ខ្ញុំនឹងចូលរួម</button>
              <button type="button" class="vg-opt" data-status="no" role="radio" aria-checked="false"><span class="box" aria-hidden="true"></span>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
            </div>
            <form class="vg-rsvp-form" id="vg-rsvp-form">
              <label class="vg-sr" for="vg-rsvp-note">សារជូនពរ</label>
              <textarea id="vg-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
              <button type="submit" class="vg-btn">ផ្ញើការឆ្លើយតប</button>
            </form>
            <p class="vg-thanks" id="vg-thanks" role="status"></p>
          </div>
        @endif

        @if ($giftQr)
          <div class="vg-card vg-vellum vg-gift {{ $rv }} d1">
            <p class="vg-eyebrow">ចំណងដៃ</p>
            {!! $headEn('With love and thanks') !!}
            <p class="vg-body">{{ $giftMessage }}</p>
            <figure class="vg-qr">
              <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
              @if ($giftAccount)<figcaption>{{ $giftAccount }}</figcaption>@endif
            </figure>
            <a class="vg-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 9 · CONTACT ============ --}}
    @if ($contacts->isNotEmpty())
      <section class="vg-sec" id="vg-contact">
        <div class="vg-head {{ $rv }}">
          {!! $headSprig() !!}
          <p class="vg-eyebrow">ទំនាក់ទំនង</p>
          {!! $headEn('Questions? Call us') !!}
        </div>
        <div class="vg-contacts">
          @foreach ($contacts as $c)
            <a class="vg-card vg-contact {{ $rv }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p">{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 10 · THANKS / APOLOGY ============ --}}
    @if ($letters->isNotEmpty())
      <section class="vg-sec vg-pair vg-letters" id="vg-letters">
        @foreach ($letters as [$heading, $en, $text])
          <article class="vg-card vg-letter {{ $rv }}{{ $loop->index ? ' d1' : '' }}">
            <p class="vg-eyebrow">{{ $heading }}</p>
            {!! $headEn($en) !!}
            <p class="vg-body">{{ $text }}</p>
          </article>
        @endforeach
      </section>
    @endif

    {{-- ============ 11 · CLOSING: the seal again ============ --}}
    {!! $vine(7) !!}
    <section class="vg-sec vg-finale" id="vg-finale">
      <div class="vg-finale-seal">
      {!! $wreath !!}
      <svg class="vg-seal vg-seal-end {{ $rv }}" viewBox="-60 -60 120 120" aria-hidden="true">
        <path d="{{ $sealEdge }}"/><path d="{{ $sealEdge }}" fill="url(#vg-wax)"/>
        <circle r="38" fill="none" stroke="#000" stroke-opacity=".22" stroke-width="1.4"/>
        <circle r="33" fill="none" stroke="#fff" stroke-opacity=".28" stroke-width=".8" stroke-dasharray="1.5 3"/>
        <text y="{{ $isKh($initials) ? 7 : 10 }}" text-anchor="middle" class="{{ $isKh($initials) ? 'vg-kh' : '' }}" font-size="{{ mb_strlen($initials) > 3 ? 20 : 28 }}">{{ $initials }}</text>
      </svg>
      </div>
      <p class="vg-eyebrow {{ $rv }}">សូមអរគុណ</p>
      <p class="vg-body {{ $rv }} d1">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
      <p class="vg-script {{ $rv }} d1">with love,</p>
      @if ($groomPretty || $bridePretty)
        <p class="vg-finale-names {{ $rv }} d2">
          @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
          @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
          @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
        </p>
      @endif
    </section>

    <div class="vg-garland {{ $rv }}" aria-hidden="true"><img src="{{ $vgImg('divider.webp') }}" alt="" loading="lazy" decoding="async" draggable="false"></div>

    <x-invitation-footer prefix="vg" :names="collect([$groomPretty, $bridePretty])->filter()->implode(' & ')" :names-class="$isKh($groomPretty.$bridePretty) ? 'vg-kh' : ''" :date="$dateNumeric">
      <x-slot:ornament><svg aria-hidden="true"><use href="#vg-flower"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview --}}
  @if ($gallery->isNotEmpty())
    <div class="vg-lb" id="vg-lb" hidden role="dialog" aria-modal="true" aria-label="{{ 'កម្រងរូបភាព' }}">
      <button type="button" class="vg-lb-x" id="vg-lb-x" aria-label="បិទ">&times;</button>
      <button type="button" class="vg-lb-nav prev" id="vg-lb-prev" aria-label="មុន">&#8249;</button>
      <figure class="vg-lb-fig">
        <img id="vg-lb-img" alt="">
        <figcaption id="vg-lb-cap"></figcaption>
      </figure>
      <button type="button" class="vg-lb-nav next" id="vg-lb-next" aria-label="បន្ទាប់">&#8250;</button>
    </div>
  @endif

  {{-- music + one sound button for everything (music and the little sound effects) --}}
  @if ($musicFile)
    <audio id="vg-audio" src="{{ $musicFile }}" preload="none" loop></audio>
  @endif
  @if ($musicUrl && ! $musicFile && ! $youtubeId)
    <a class="vg-sound vg-sound-link" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
    </a>
  @else
    <button type="button" class="vg-sound" id="vg-sound" aria-pressed="false" aria-label="បិទសំឡេង">
      <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
    </button>
  @endif

  <script type="application/json" id="vg-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'seal' => $sfx('papercrackle1.mp3'),
          'open' => $sfx('envelope.mp3'),
          'chime' => $sfx('chime.mp3'),
          'tap' => $sfx('pop.mp3'),
          'card' => $sfx('card.mp3'),
          'reveal' => $sfx('reveal.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $vgAsset('js/invitations/vellum-garden.js') }}"></script>
