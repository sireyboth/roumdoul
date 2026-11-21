@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Paper Bloom: a handmade, layered paper-cut wedding invitation.
    // Cover: the couple's photo seen through four stacked paper arch windows (scalloped edges,
    // paper flowers) that shift in depth as the phone tilts / the pointer moves. A satin ribbon is
    // tied across the arch; tapping the bow unties it, the ribbon falls and the camera flies through
    // the paper layers into the invitation. Inside, every section is a sheet of coloured paper with a
    // scalloped edge; flowers pop up like a pop-up book, a name tag swings on a string, polaroids hang
    // on a clothesline, photos swipe like a deck, the map unfolds, the gift box opens and the RSVP
    // folds into a paper plane. Everything personal comes from $fields (Khmer copy is template text).
    // On demo pages $invitation is null, so every premium extra previews.
    // CSS public/css/invitations/paper-bloom.css · JS public/js/invitations/paper-bloom.js
    // Debug: ?open=1 skips the cover.
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $pbAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $khDayShort = ['អា', 'ច', 'អ', 'ពុ', 'ព្រ', 'សុ', 'ស'];
    $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $khDate = fn ($d) => 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year);

    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' pb-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#e58f8f';
    $headline = trim($fields['headline'] ?? '') ?: 'Together with their families';
    $titleImg = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate ? $khDate($eventDate) : null;
    $period = fn (int $h) => $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));
    $khTime = fn (int $h, string $min) => $khDigits(sprintf('%d:%s', $h % 12 ?: 12, $min)).' '.$period($h);
    $endTime = preg_match('/^(\d{1,2}):(\d{2})/', trim($fields['end_time'] ?? ''), $et) ? $khTime((int) $et[1], $et[2]) : null;
    $timeKhmer = $eventDate ? 'វេលាម៉ោង '.$khTime($eventDate->hour, $eventDate->format('i')).($endTime ? ' – '.$endTime : '') : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? $fields['khmer_date'] : null;

    $calendar = null;
    if ($eventDate) {
        $first = $eventDate->copy()->startOfMonth();
        $calendar = [
            'title' => 'ខែ'.$khMonths[$eventDate->month - 1].' '.$khDigits($eventDate->year),
            'en' => $enMonths[$eventDate->month - 1],
            'cells' => array_merge(array_fill(0, $first->dayOfWeek, null), range(1, $first->daysInMonth)),
            'day' => $eventDate->day,
        ];
    }

    $venueName = trim($fields['venue_name'] ?? '') ?: null;
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
    $mapEmbed = ($mapPinned || $venueAddress) && $mapQuery ? 'https://maps.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed' : null;
    $showVenue = $venueName || $venueAddress || $mapLink;

    $giftQr = ($isUnlocked('qr_code') && ! empty($fields['qr_code'])) ? $resolveUrl($fields['qr_code']) : null;
    $giftAccount = ($giftQr && ! empty($fields['gift_account'])) ? trim($fields['gift_account']) : null;
    $giftNumber = null;
    if ($giftAccount && preg_match_all('/\d[\d\s-]{4,}\d/', $giftAccount, $nums)) {
        $giftNumber = preg_replace('/\D/', '', collect($nums[0])->sortByDesc(fn ($n) => strlen($n))->first());
    }
    $giftMessage = trim($fields['gift_message'] ?? '') ?: 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR ខាងក្រោម។ សូមអរគុណ!';

    $personalNote = trim($fields['message'] ?? '');
    $receptionText = trim($fields['reception_message'] ?? '')
        ?: 'អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ';
    $letterRows = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', trim($fields['thanks_message'] ?? '')],
        ['លិខិតសូមអភ័យទោស', 'A gentle apology', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'Happily ever after';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    $heroUrl = $coverUrl; // the cover photo flies from the cover into the hero arch (one continuous shot)
    $finaleBg = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : null;
    $groomPhoto = ! empty($fields['groom_photo']) ? $resolveUrl($fields['groom_photo']) : null;
    $bridePhoto = ! empty($fields['bride_photo']) ? $resolveUrl($fields['bride_photo']) : null;

    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['title']) || ! empty($row['text']))->values();
    $splitTitle = fn ($t) => array_pad(array_map('trim', explode('|', (string) $t, 2)), 2, '');

    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($row) => ! empty($row['time']) || ! empty($row['label']))->values();
    $scheduleImg = ($isUnlocked('schedule_image') && ! empty($fields['schedule_image'])) ? $resolveUrl($fields['schedule_image']) : null;
    $schedTime = function (?string $t) use ($khTime): string {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return $t;
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }

        return $khTime($h, $m[2]);
    };

    // guest notes. Dress code is always colour swatches (DressCode), with the text under them
    $dressCode = trim($fields['dress_code'] ?? '');
    $dressColours = \App\Support\Invitations\DressCode::colours($dressCode);
    $whatToBring = trim($fields['what_to_bring'] ?? '');
    $rsvpBy = null;
    if (! empty($fields['rsvp_deadline']) && ($d = \App\Support\Invitations\EventDate::parse($fields['rsvp_deadline']))) {
        $rsvpBy = $khDate($d);
    }
    $showNotes = $dressCode !== '' || $whatToBring !== '' || $rsvpBy;

    $showCountdown = $eventDate && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', $recipient) : null;
    $rsvpQuestion = trim($fields['rsvp_message'] ?? '') ?: 'តើលោកអ្នកនឹងអញ្ជើញចូលរួមជាមួយយើងខ្ញុំដែរឬទេ?';

    $ctaLabel = $isUnlocked('cta_label') ? trim($fields['cta_label'] ?? '') : '';
    $ctaUrl = $isUnlocked('cta_url') && preg_match('#^https?://#i', trim($fields['cta_url'] ?? '')) ? trim($fields['cta_url']) : null;
    $showCta = $ctaLabel !== '' && $ctaUrl;

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
    $songTitle = trim($fields['song_title'] ?? '');

    // ---------------- paper art ----------------
    // A paper sheet with an arch-shaped window cut out of it. The window's edge is scalloped: the
    // outline (left side up, over the arch, right side down, back along the bottom) is walked in equal
    // steps and every step bulges into the window. Clockwise on screen, so the window is always on the
    // right of the direction of travel → that's the way the bumps go.
    $archWindow = function (float $cx, float $hw, float $top, float $bottom, float $step, float $depth) {
        $cy = $top + $hw;
        $side = $bottom - $cy;
        $arc = M_PI * $hw;
        $len = 2 * $side + $arc + 2 * $hw;
        $at = function (float $d) use ($cx, $hw, $cy, $bottom, $side, $arc) {
            if ($d < $side) {
                return [$cx - $hw, $bottom - $d];
            }
            $d -= $side;
            if ($d < $arc) {
                $a = M_PI + $d / $hw;

                return [$cx + cos($a) * $hw, $cy + sin($a) * $hw];
            }
            $d -= $arc;
            if ($d < $side) {
                return [$cx + $hw, $cy + $d];
            }
            $d -= $side;

            return [$cx + $hw - $d, $bottom];
        };
        $n = max(12, (int) round($len / $step));
        $pts = [];
        for ($i = 0; $i <= $n; $i++) {
            $pts[] = $at(min($len - .001, $i * $len / $n));
        }
        $d = 'M'.round($pts[0][0], 1).' '.round($pts[0][1], 1);
        for ($i = 0; $i < $n; $i++) {
            [$x0, $y0] = $pts[$i];
            [$x1, $y1] = $pts[$i + 1];
            $dx = $x1 - $x0;
            $dy = $y1 - $y0;
            $l = max(.001, hypot($dx, $dy));
            $qx = ($x0 + $x1) / 2 - $dy / $l * $depth;
            $qy = ($y0 + $y1) / 2 + $dx / $l * $depth;
            $d .= 'Q'.round($qx, 1).' '.round($qy, 1).' '.round($x1, 1).' '.round($y1, 1);
        }

        return ['d' => $d.'Z', 'pts' => $pts];
    };

    // flowers + leaves cut from paper, scattered along a window edge (deterministic per seed)
    $flowerAt = fn (float $x, float $y, float $s, string $color, float $rot, string $kind = 'fl') => '<g transform="translate('.round($x, 1).' '.round($y, 1).') rotate('.round($rot).') scale('.round($s, 2).')" style="color:'.$color.'"><use href="#pb-'.$kind.'"/></g>';
    $edgeFlowers = function (array $pts, int $seed, array $colors, float $size, array $at) use ($flowerAt): string {
        mt_srand($seed);
        $n = count($pts) - 1;
        $size /= 16; // a flower is ~38 units across: size 15 → ~36 units in the 400-wide drawing
        $out = '';
        foreach ($at as $k => $f) {
            [$x, $y] = $pts[(int) round($f * $n)];
            $out .= $flowerAt($x + mt_rand(-6, 6), $y + mt_rand(-6, 6), $size * mt_rand(80, 125) / 100 * 1.15, 'var(--pb-leaf)', mt_rand(0, 359), 'leaf');
            $out .= $flowerAt($x + mt_rand(-10, 10), $y + mt_rand(-10, 10), $size * mt_rand(70, 110) / 100 * .9, 'var(--pb-leaf-2)', mt_rand(0, 359), 'leaf');
            $out .= $flowerAt($x, $y, $size * mt_rand(85, 120) / 100, $colors[$k % count($colors)], mt_rand(0, 59), $k % 3 === 1 ? 'fl2' : 'fl');
        }

        return $out;
    };

    // the cover: four sheets, front (0) to back (3). [hw, top, bottom, step, depth, sheet colour, flower colours, flower spots]
    $layerSpec = [
        [148, 120, 470, 26, 7, 'var(--pb-l0)', ['var(--pb-cream)', 'var(--pb-butter)', '#fff'], [.04, .2, .37, .5, .63, .8, .96]],
        [130, 150, 482, 22, 6, 'var(--pb-l1)', ['var(--pb-accent)', 'var(--pb-cream)'], [.1, .3, .7, .9]],
        [113, 178, 494, 19, 5, 'var(--pb-l2)', ['var(--pb-l1)', '#fff', 'var(--pb-accent)'], [.06, .25, .5, .75, .94]],
        [97, 205, 506, 16, 4, 'var(--pb-l3)', ['var(--pb-accent)', 'var(--pb-l2)'], [.18, .82]],
    ];
    $coverLayers = collect($layerSpec)->map(function ($s, $i) use ($archWindow, $edgeFlowers) {
        $w = $archWindow(200, $s[0], $s[1], $s[2], $s[3], $s[4]);

        return [
            'path' => 'M-2400 -400H2800V1200H-2400Z'.$w['d'], // far past the stage, so no sheet edge ever shows on wide screens
            'color' => $s[5],
            'flowers' => $edgeFlowers($w['pts'], 11 + $i * 7, $s[6], 15 - $i * 1.6, $s[7]),
            'depth' => [26, 17, 10, 5][$i],
        ];
    })->reverse()->values(); // back first in the markup

    // a big cluster of paper flowers + leaves round x,y (cover corners, page edges)
    $cluster = function (float $x, float $y, int $seed, int $n = 5, float $r = 34, float $scale = 1) use ($flowerAt): string {
        mt_srand($seed);
        $colors = ['var(--pb-cream)', 'var(--pb-butter)', '#fff', 'var(--pb-l1)', 'var(--pb-accent)'];
        $out = '';
        for ($k = 0; $k < $n * 2; $k++) {
            $a = mt_rand(0, 628) / 100;
            $d = mt_rand(30, 100) / 100 * $r * 1.25;
            $out .= $flowerAt($x + cos($a) * $d, $y + sin($a) * $d, $scale * mt_rand(90, 150) / 100, $k % 2 ? 'var(--pb-leaf)' : 'var(--pb-leaf-2)', rad2deg($a) + 90, 'leaf');
        }
        for ($k = 0; $k < $n; $k++) {
            $a = mt_rand(0, 628) / 100;
            $d = $k === 0 ? 0 : mt_rand(35, 100) / 100 * $r;
            $out .= $flowerAt($x + cos($a) * $d, $y + sin($a) * $d, $scale * ($k === 0 ? 1.35 : mt_rand(70, 110) / 100), $colors[($seed + $k) % count($colors)], mt_rand(0, 59), $k % 3 === 1 ? 'fl2' : 'fl');
        }

        return $out;
    };

    // decoration on the front cover sheet: a garland across the top, flower clusters in the corners (and, on wide
    // screens, out on the sides), drawn on the same paper so they move and cast shadows with it
    $garland = (function () use ($flowerAt): string {
        mt_srand(5);
        $pts = [];
        for ($i = 0; $i <= 40; $i++) {
            $t = $i / 40;
            $pts[] = [-40 + $t * 480, 26 + sin($t * M_PI) * 58 + sin($t * 14) * 3];
        }
        $out = '<path d="M'.implode(' L', array_map(fn ($p) => round($p[0], 1).' '.round($p[1], 1), $pts)).'" fill="none" stroke="var(--pb-leaf)" stroke-width="2.2" stroke-linecap="round"/>';
        foreach ($pts as $i => [$x, $y]) {
            if ($i % 2) {
                $out .= $flowerAt($x, $y, mt_rand(55, 80) / 100, $i % 4 === 1 ? 'var(--pb-leaf)' : 'var(--pb-leaf-2)', ($i % 4 === 1 ? 200 : 160) + mt_rand(-25, 25), 'leaf');
            }
            if ($i % 7 === 3) {
                $out .= $flowerAt($x, $y + 2, mt_rand(50, 70) / 100, ['#fff', 'var(--pb-butter)', 'var(--pb-cream)'][$i % 3], mt_rand(0, 59), $i % 2 ? 'fl2' : 'fl');
            }
        }

        return $out;
    })();
    $coverDecor = $garland
        .$cluster(18, 150, 21, 5, 30, .85).$cluster(384, 168, 22, 5, 30, .85)
        .$cluster(6, 600, 23, 4, 26, .8).$cluster(396, 640, 24, 4, 26, .8)
        .$cluster(-10, 805, 25, 5, 34, 1).$cluster(410, 800, 26, 5, 34, 1)
        // only seen on wide screens (beyond the portrait stage)
        .$cluster(-110, 120, 31, 6, 44, 1.2).$cluster(-70, 420, 32, 5, 40, 1.1).$cluster(-140, 700, 33, 6, 44, 1.2)
        .$cluster(510, 110, 34, 6, 44, 1.2).$cluster(470, 440, 35, 5, 40, 1.1).$cluster(540, 690, 36, 6, 44, 1.2)
        .$cluster(-260, 300, 37, 5, 40, 1.2).$cluster(660, 320, 38, 5, 40, 1.2);

    // page decoration: flower clusters peeking in from a section's edges + a few small floating paper bits.
    // Alternates sides per section; the clusters drift a little with scroll (data-speed)
    $decorN = 0;
    $decor = function () use (&$decorN, $cluster): string {
        $n = $decorN++;
        $flip = $n % 2;
        $svg = fn (int $seed, string $cls, float $speed) => '<svg class="pb-deco '.$cls.'" data-speed="'.$speed.'" viewBox="-60 -60 120 120" aria-hidden="true">'.$cluster(0, 0, 100 + $seed, 5, 30, .95).'</svg>';
        $bits = '';
        mt_srand(200 + $n);
        for ($k = 0; $k < 4; $k++) {
            $bits .= '<svg class="pb-bit" viewBox="-12 -11 24 22" style="left:'.mt_rand(4, 94).'%;top:'.mt_rand(8, 88).'%;--d:'.(mt_rand(0, 40) / 10).'s;--r:'.mt_rand(-30, 30).'deg;color:'
                .['var(--pb-accent)', 'var(--pb-leaf-2)', 'var(--pb-butter)', 'var(--pb-l1)'][($n + $k) % 4].'" aria-hidden="true"><use href="#'.($k % 2 ? 'pb-heart' : 'pb-leaf').'"'.($k % 2 ? '' : ' transform="translate(0 12) scale(.7)"').'/></svg>';
        }

        return '<div class="pb-decor" aria-hidden="true">'
            .$svg($n * 2, $flip ? 'tr' : 'tl', .08).$svg($n * 2 + 1, $flip ? 'bl' : 'br', -.06).$bits.'</div>';
    };

    // the hero arch: two arch-shaped paper rings round the photo (outer edge scallops outward, inner inward)
    $heroLayers = collect([
        [[146, 6, 404, 20, -5], [126, 30, 384, 17, 5], 'var(--pb-l1)'],
        [[126, 30, 384, 17, -4], [110, 48, 368, 14, 4], 'var(--pb-cream)'],
    ])->map(function ($s, $i) use ($archWindow, $edgeFlowers) {
        $outer = $archWindow(150, ...$s[0]);
        $inner = $archWindow(150, ...$s[1]);

        return [
            'path' => $outer['d'].$inner['d'],
            'color' => $s[2],
            'flowers' => $i === 0 ? $edgeFlowers($inner['pts'], 91, ['var(--pb-accent)', 'var(--pb-butter)', '#fff'], 13, [.03, .16, .5, .84, .97]) : '',
        ];
    });

    // a little bunch of paper flowers (section headings, corners): pops up like a pop-up book
    $bunch = function (int $seed, int $count = 3, float $scale = 1) use ($flowerAt): string {
        mt_srand($seed);
        $colors = ['var(--pb-accent)', 'var(--pb-butter)', 'var(--pb-l1)', '#fff'];
        $out = '';
        for ($k = 0; $k < $count + 2; $k++) {
            $out .= $flowerAt(mt_rand(-26, 26), mt_rand(-10, 18), mt_rand(9, 13) / 10, $k % 2 ? 'var(--pb-leaf)' : 'var(--pb-leaf-2)', mt_rand(-80, 80), 'leaf');
        }
        for ($k = 0; $k < $count; $k++) {
            $out .= $flowerAt(($k - ($count - 1) / 2) * 20 + mt_rand(-4, 4), mt_rand(-8, 6) - ($k % 2) * 8, mt_rand(85, 115) / 100, $colors[($seed + $k) % 4], mt_rand(0, 59), $k % 2 ? 'fl2' : 'fl');
        }

        return '<svg class="pb-bunch" viewBox="-50 -40 100 70" style="--s:'.$scale.'" aria-hidden="true">'.$out.'</svg>';
    };

    $head = fn (string $en, string $khText, int $seed) => '<div class="pb-head pb-r" data-r="up">'
        .'<span class="pb-head-bunch l pb-r" data-r="pop">'.$bunch($seed).'</span>'
        .'<p class="pb-script">'.e($en).'</p><p class="pb-head-kh">'.e($khText).'</p>'
        .'<span class="pb-head-bunch r pb-r" data-r="pop">'.$bunch($seed + 50).'</span></div>';

    // paper sheets cycle through four colours
    $sheetN = 0;
    $sheet = function () use (&$sheetN): string {
        return 'pb-sheet s'.(($sheetN++) % 4);
    };

    $skipGate = request()->boolean('open');
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Fraunces / Allura are Latin-only: the CSS stacks always follow them with Noto Serif Khmer / Moulpali;
     Kantumruy Pro carries both scripts for the body --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..700;1,9..144,300..700&family=Allura&family=Moulpali&family=Noto+Serif+Khmer:wght@400;500;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $pbAsset('css/invitations/paper-bloom.css') }}">
<noscript><style>.pb-gate{display:none}.pb-page{visibility:visible!important}.pb-r{opacity:1!important;transform:none!important}</style></noscript>

<div class="pb{{ $skipGate ? ' is-open' : '' }}" style="--pb-accent: {{ $accent }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- six-petal paper flower: petals in the flower's colour, a lighter fold on each, a butter centre --}}
      <g id="pb-fl">
        <g fill="currentColor">
          @for ($p = 0; $p < 6; $p++)<path transform="rotate({{ $p * 60 }})" d="M0 0C-7.5 -5 -7.5 -15 0 -19C7.5 -15 7.5 -5 0 0Z"/>@endfor
        </g>
        <g fill="#fff" fill-opacity=".32">
          @for ($p = 0; $p < 6; $p++)<path transform="rotate({{ $p * 60 + 8 }}) scale(.5)" d="M0 0C-7.5 -5 -7.5 -15 0 -19C7.5 -15 7.5 -5 0 0Z"/>@endfor
        </g>
        <circle r="4.2" fill="var(--pb-center)"/><circle r="1.6" cx="-1" cy="-1" fill="#fff" fill-opacity=".6"/>
      </g>
      {{-- round five-petal blossom --}}
      <g id="pb-fl2">
        <g fill="currentColor">@for ($p = 0; $p < 5; $p++)<circle cx="0" cy="-9" r="7.6" transform="rotate({{ $p * 72 }})"/>@endfor</g>
        <g fill="#000" fill-opacity=".07">@for ($p = 0; $p < 5; $p++)<circle cx="0" cy="-5" r="3.6" transform="rotate({{ $p * 72 + 36 }})"/>@endfor</g>
        <circle r="5" fill="var(--pb-center)"/>
      </g>
      <g id="pb-leaf">
        <path fill="currentColor" d="M0 0C7 -6 7 -19 0 -28C-7 -19 -7 -6 0 0Z"/>
        <path d="M0 -2V-24" stroke="#000" stroke-opacity=".12" stroke-width="1" fill="none"/>
      </g>
      <g id="pb-heart"><path fill="currentColor" d="M0 9C-10 2 -11 -4 -8 -7.5C-5 -10.5 -1.5 -8.5 0 -5.5C1.5 -8.5 5 -10.5 8 -7.5C11 -4 10 2 0 9Z"/></g>
      {{-- pressed paper pattern on the cover sheet: tiny hearts and dots, a shade lighter than the paper --}}
      <pattern id="pb-press" width="44" height="44" patternUnits="userSpaceOnUse" patternTransform="rotate(-12)">
        <path transform="translate(11 11) scale(.42)" fill="#fff" fill-opacity=".2" d="M0 9C-10 2 -11 -4 -8 -7.5C-5 -10.5 -1.5 -8.5 0 -5.5C1.5 -8.5 5 -10.5 8 -7.5C11 -4 10 2 0 9Z"/>
        <circle cx="33" cy="33" r="1.8" fill="#fff" fill-opacity=".22"/>
        <circle cx="33" cy="11" r="1" fill="#fff" fill-opacity=".18"/><circle cx="11" cy="33" r="1" fill="#fff" fill-opacity=".18"/>
      </pattern>
    </defs>
  </svg>

  {{-- ============ 0 · COVER: paper arches + the ribbon ============ --}}
  @unless ($skipGate)
    <div class="pb-gate" id="pb-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      <div class="pb-stage" id="pb-stage">
        <div class="pb-photo" data-depth="2">
          @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" fetchpriority="high" draggable="false">
          @else
            <div class="pb-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>
          @endif
        </div>
        @foreach ($coverLayers as $i => $layer)
          <svg class="pb-layer l{{ 3 - $i }}" data-depth="{{ $layer['depth'] }}" viewBox="0 0 400 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <path fill-rule="evenodd" d="{{ $layer['path'] }}" style="fill:{{ $layer['color'] }}"/>
            @if ($loop->last)
              {{-- the front sheet: little hearts and dots pressed into the paper, a garland, flower clusters --}}
              <path fill-rule="evenodd" d="{{ $layer['path'] }}" fill="url(#pb-press)"/>
              {!! $coverDecor !!}
            @endif
            {!! $layer['flowers'] !!}
          </svg>
        @endforeach

        {{-- the ribbon tied across the arch, with a bow to untie --}}
        <div class="pb-ribbon" aria-hidden="true">
          <span class="pb-ribbon-band l"></span><span class="pb-ribbon-band r"></span>
        </div>
        <button type="button" class="pb-bow" id="pb-open" aria-label="ស្រាយខ្សែបូ ដើម្បីបើកសំបុត្រ">
          <svg viewBox="-60 -40 120 90" aria-hidden="true">
            <defs>
              <linearGradient id="pb-satin" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#fff" stop-opacity=".55"/><stop offset=".45" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".18"/>
              </linearGradient>
            </defs>
            <g class="pb-bow-tail l"><path d="M-4 4L-26 44L-16 40L-10 48L4 8Z"/><path d="M-4 4L-26 44L-16 40L-10 48L4 8Z" fill="url(#pb-satin)"/></g>
            <g class="pb-bow-tail r"><path d="M4 4L26 44L16 40L10 48L-4 8Z"/><path d="M4 4L26 44L16 40L10 48L-4 8Z" fill="url(#pb-satin)"/></g>
            <g class="pb-bow-loop l"><path d="M-2 0C-20 -30 -56 -22 -50 -2C-46 14 -18 12 -2 0Z"/><path d="M-2 0C-20 -30 -56 -22 -50 -2C-46 14 -18 12 -2 0Z" fill="url(#pb-satin)"/><path d="M-8 -2C-20 -14 -38 -14 -40 -4" fill="none" stroke="#000" stroke-opacity=".14" stroke-width="1.5"/></g>
            <g class="pb-bow-loop r"><path d="M2 0C20 -30 56 -22 50 -2C46 14 18 12 2 0Z"/><path d="M2 0C20 -30 56 -22 50 -2C46 14 18 12 2 0Z" fill="url(#pb-satin)"/><path d="M8 -2C20 -14 38 -14 40 -4" fill="none" stroke="#000" stroke-opacity=".14" stroke-width="1.5"/></g>
            <g class="pb-bow-knot"><rect x="-9" y="-10" width="18" height="20" rx="6"/><rect x="-9" y="-10" width="18" height="20" rx="6" fill="url(#pb-satin)"/></g>
          </svg>
        </button>

        <div class="pb-gate-text">
          <p class="pb-gate-kicker">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
          <p class="pb-gate-headline">{{ $headline }}</p>
          @if ($titleImg)
            <img class="pb-gate-title" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
          @elseif ($coupleTitle)
            <h1 class="pb-gate-names">
              @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
              @if ($groomPretty && $bridePretty)<em>&amp;</em>@endif
              @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
            </h1>
          @endif
          @if ($dateNumeric)<p class="pb-gate-date">{{ $dateNumeric }}</p>@endif
          <p class="pb-gate-to">សូមគោរពអញ្ជើញ <b class="{{ trim($kh($recipientName ?? '')) }}">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</b></p>
          <p class="pb-gate-hint"><span aria-hidden="true">☝</span> ចុចលើខ្សែបូ ដើម្បីបើកសំបុត្រ</p>
        </div>
      </div>
      <div class="pb-grain" aria-hidden="true"></div>
    </div>
  @endunless

  {{-- paper confetti (hearts + petals): taps, bursts and a few petals drifting down --}}
  <canvas class="pb-confetti" id="pb-confetti" aria-hidden="true"></canvas>

  <main class="pb-page" id="pb-top">

    {{-- ============ 1 · HERO ============ --}}
    <section class="pb-hero {{ $sheet() }}">
      {!! $decor() !!}
      <div class="pb-hero-arch" id="pb-hero-arch">
        <div class="pb-hero-photo" id="pb-hero-photo" data-depth="3">
          @if ($heroUrl)<img src="{{ $heroUrl }}" alt="{{ $coupleTitle }}">@else<div class="pb-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>@endif
        </div>
        @foreach ($heroLayers->reverse() as $i => $layer)
          <svg class="pb-hero-layer" data-depth="{{ $i ? 6 : 12 }}" viewBox="0 0 300 410" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
            <path fill-rule="evenodd" d="{{ $layer['path'] }}" style="fill:{{ $layer['color'] }}"/>{!! $layer['flowers'] !!}
          </svg>
        @endforeach
      </div>
      <p class="pb-eyebrow pb-r" data-r="up">The wedding of</p>
      <h2 class="pb-hero-names pb-r" data-r="up">
        @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
        @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
        @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
      </h2>
      @if ($dateKhmer)<p class="pb-hero-date pb-r" data-r="up">{{ $dateKhmer }}</p>@endif
      @if ($venueName)<p class="pb-hero-venue pb-r{{ $kh($venueName) }}" data-r="up">{{ $venueName }}</p>@endif
      <a class="pb-scroll" href="#pb-invite" aria-label="អានបន្ត" data-sfx="tap"><svg viewBox="0 0 24 24" aria-hidden="true"><use href="#pb-heart"/></svg></a>
    </section>

    {{-- ============ 2 · THE INVITATION (stitched card + swinging name tag) ============ --}}
    <section class="pb-sec {{ $sheet() }}" id="pb-invite">
      {!! $decor() !!}
      <article class="pb-card pb-invite pb-r" data-r="pop">
        <span class="pb-tape tl" aria-hidden="true"></span><span class="pb-tape tr" aria-hidden="true"></span>
        @if ($groomParents || $brideParents)
          <div class="pb-parents">
            <div><p class="pb-label">មាតាបិតាខាងកូនប្រុស</p>@foreach ($groomParents as $line)<p class="pb-parent">{{ $line }}</p>@endforeach</div>
            <svg class="pb-parents-fl" viewBox="-20 -20 40 40" aria-hidden="true" style="color:var(--pb-accent)"><use href="#pb-fl"/></svg>
            <div><p class="pb-label">មាតាបិតាខាងកូនស្រី</p>@foreach ($brideParents as $line)<p class="pb-parent">{{ $line }}</p>@endforeach</div>
          </div>
        @endif
        <h3 class="pb-invite-title">សូមគោរពអញ្ជើញ</h3>

        {{-- the guest's name on a kraft tag hanging from a string --}}
        <div class="pb-tag-wrap">
          <div class="pb-tag" id="pb-tag">
            <span class="pb-tag-hole" aria-hidden="true"></span>
            <span class="pb-tag-name{{ $kh($recipientName ?? '') }}">{{ ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា' }}</span>
          </div>
        </div>

        <p class="pb-body pb-r" data-r="words">{{ $receptionText }}</p>
        @if ($coupleTitle)
          <div class="pb-couple">
            @if ($groomPretty)<div><p class="pb-label">កូនប្រុសនាម</p><p class="pb-couple-name{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>@endif
            @if ($groomPretty && $bridePretty)<svg class="pb-couple-heart" viewBox="-12 -11 24 22" aria-hidden="true"><use href="#pb-heart"/></svg>@endif
            @if ($bridePretty)<div><p class="pb-label">កូនស្រីនាម</p><p class="pb-couple-name{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>@endif
          </div>
        @endif
        @if ($eventDate)
          <div class="pb-when">
            <p>{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="pb-soft">{{ $khmerLunarDate }}</p>@endif
            <p class="pb-accent-text">{{ $timeKhmer }}</p>
            @if ($venueName)<p class="pb-soft{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          </div>
        @endif
      </article>
    </section>

    {{-- ============ 3 · BRIDE & GROOM (scalloped paper frames) ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="pb-sec {{ $sheet() }}" id="pb-couple">
      {!! $decor() !!}
        {!! $head('The bride & groom', 'កូនកំលោះ និងកូនក្រមុំ', 3) !!}
        <div class="pb-duo">
          @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ']] as $k => [$photo, $name, $role])
            @if ($photo)
              <figure class="pb-duo-item pb-r" data-r="{{ $k ? 'spin-r' : 'spin-l' }}">
                <div class="pb-scallop"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></div>
                <figcaption><span class="pb-label">{{ $role }}</span>@if ($name)<span class="pb-duo-name{{ $kh($name) }}">{{ $name }}</span>@endif</figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 4 · A NOTE (sticky note + washi tape) ============ --}}
    @if ($personalNote !== '')
      <section class="pb-sec {{ $sheet() }}" id="pb-note">
      {!! $decor() !!}
        <div class="pb-sticky pb-r" data-r="drop">
          <span class="pb-tape top" aria-hidden="true"></span>
          <p class="pb-sticky-text pb-r" data-r="words">{{ $personalNote }}</p>
          <p class="pb-script pb-sticky-sign">with love, {{ $coupleTitle }}</p>
        </div>
      </section>
    @endif

    {{-- ============ 5 · SAVE THE DATE: tear-off calendar + flip countdown ============ --}}
    @if ($calendar)
      <section class="pb-sec {{ $sheet() }}" id="pb-date">
      {!! $decor() !!}
        {!! $head('Save the date', 'ចំណាំថ្ងៃដ៏មានអត្ថន័យ', 5) !!}
        <div class="pb-cal pb-r" data-r="pop">
          <div class="pb-cal-rings" aria-hidden="true">@for ($r = 0; $r < 6; $r++)<span></span>@endfor</div>
          <div class="pb-cal-head">
            <span class="pb-cal-en">{{ $calendar['en'] }}</span>
            <span class="pb-cal-kh">{{ $calendar['title'] }}</span>
          </div>
          <div class="pb-cal-grid" role="grid" aria-label="{{ $calendar['title'] }}">
            @foreach ($khDayShort as $dn)<span class="dn">{{ $dn }}</span>@endforeach
            @foreach ($calendar['cells'] as $i => $c)
              @if ($c === null)
                <span></span>
              @elseif ($c === $calendar['day'])
                <span class="day the-day">
                  {{-- hand-drawn circle, drawn in when the calendar is seen --}}
                  <svg viewBox="0 0 60 50" aria-hidden="true"><path pathLength="1" d="M33 6C17 4 5 13 6 26C7 39 22 46 36 43C50 40 57 28 53 17C50 9 41 5 30 7C24 8 19 10 16 13"/></svg>
                  <b>{{ $khDigits($c) }}</b>
                </span>
              @else
                <span class="day">{{ $khDigits($c) }}</span>
              @endif
            @endforeach
          </div>
          <p class="pb-body">{{ $dateKhmer }}</p>
          @if ($khmerLunarDate)<p class="pb-soft">{{ $khmerLunarDate }}</p>@endif
          <p class="pb-accent-text">{{ $timeKhmer }}</p>
        </div>

        @if ($showCountdown)
          <div class="pb-count pb-r" data-r="up" id="pb-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
            @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
              <div class="pb-flip"><div class="pb-flip-card"><b data-u="{{ $u }}">០០</b></div><span>{{ $label }}</span></div>
            @endforeach
          </div>
          <p class="pb-count-done" id="pb-count-done" hidden>ថ្ងៃនេះហើយ! ♥</p>
        @endif
      </section>
    @endif

    {{-- ============ 6 · OUR STORY: polaroids on a clothesline (tap to flip) ============ --}}
    @if ($story->isNotEmpty())
      <section class="pb-sec pb-wide {{ $sheet() }}" id="pb-story">
      {!! $decor() !!}
        {!! $head('Our little story', 'ដំណើររឿងស្នេហារបស់យើង', 7) !!}
        <p class="pb-hint pb-r" data-r="up">អូសទៅឆ្វេង-ស្ដាំ · ចុចលើរូប ដើម្បីអានរឿង</p>
        <div class="pb-line" id="pb-line">
          <svg class="pb-line-string" preserveAspectRatio="none" viewBox="0 0 100 20" aria-hidden="true"><path d="M0 2Q50 22 100 2" vector-effect="non-scaling-stroke"/></svg>
          <ol class="pb-line-track">
            @foreach ($story as $i => $c)
              @php([$place, $when] = $splitTitle($c['title'] ?? ''))
              <li class="pb-polaroid-wrap" style="--i:{{ $i }}">
                <span class="pb-peg" aria-hidden="true"></span>
                <button type="button" class="pb-polaroid" aria-label="{{ $place ?: 'រឿងទី '.$khDigits($i + 1) }}">
                  <span class="pb-polaroid-in">
                    <span class="pb-polaroid-front">
                      @if ($gallery->isNotEmpty())<img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy">@else<span class="pb-polaroid-blank"><svg viewBox="-12 -11 24 22"><use href="#pb-heart"/></svg></span>@endif
                      <span class="pb-polaroid-cap{{ $kh($place) }}">{{ $place }}</span>
                      @if ($when !== '')<span class="pb-polaroid-when{{ $kh($when) }}">{{ $when }}</span>@endif
                    </span>
                    <span class="pb-polaroid-back">
                      <span class="pb-polaroid-num">{{ $khDigits($i + 1) }}</span>
                      <span class="pb-polaroid-text">{{ $c['text'] ?? '' }}</span>
                    </span>
                  </span>
                </button>
              </li>
            @endforeach
          </ol>
        </div>
      </section>
    @endif

    {{-- ============ 7 · PROGRAM: paper tickets ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="pb-sec {{ $sheet() }}" id="pb-program">
      {!! $decor() !!}
        {!! $head('Order of the day', 'កម្មវិធីសិរីមង្គល', 9) !!}
        @if ($scheduleImg)
          <figure class="pb-card pb-sched-img pb-r" data-r="pop">
            <span class="pb-tape tl" aria-hidden="true"></span>
            <button type="button" class="pb-ph" data-group="sched" data-g="0" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីសិរីមង្គល"><img src="{{ $scheduleImg }}" alt="កម្មវិធីសិរីមង្គល" loading="lazy"></button>
          </figure>
        @else
          <ol class="pb-tickets">
            @foreach ($schedule as $i => $row)
              <li class="pb-ticket pb-r" data-r="{{ $i % 2 ? 'right' : 'left' }}">
                <span class="t">{{ $schedTime($row['time'] ?? '') }}</span>
                <span class="l{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
              </li>
            @endforeach
          </ol>
        @endif
      </section>
    @endif

    {{-- ============ 8 · GALLERY: a deck you swipe ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="pb-sec {{ $sheet() }}" id="pb-gallery">
      {!! $decor() !!}
        {!! $head('Moments we love', 'កម្រងរូបភាពអនុស្សាវរីយ៍', 11) !!}
        <div class="pb-deck pb-r" data-r="up" id="pb-deck">
          @foreach ($gallery as $i => $url)
            <figure class="pb-deck-card" data-i="{{ $i }}">
              <button type="button" class="pb-ph" data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" tabindex="-1" aria-label="{{ ($captions[$i] ?? '') ?: 'រូបភាពទី '.$khDigits($i + 1) }}"><img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="{{ $i < 3 ? 'eager' : 'lazy' }}" draggable="false"></button>
              <figcaption class="{{ trim($kh($captions[$i] ?? '')) }}">{{ ($captions[$i] ?? '') ?: '♥' }}</figcaption>
            </figure>
          @endforeach
        </div>
        @if ($gallery->count() > 1)
          <div class="pb-deck-ctrl">
            <button type="button" class="pb-round" id="pb-deck-prev" aria-label="រូបមុន">&#8249;</button>
            <span class="pb-deck-count" id="pb-deck-count">១ / {{ $khDigits($gallery->count()) }}</span>
            <button type="button" class="pb-round" id="pb-deck-next" aria-label="រូបបន្ទាប់">&#8250;</button>
          </div>
          <p class="pb-hint">អូសរូបចេញ ឬចុចដើម្បីមើលធំ</p>
        @endif
        <ul class="pb-thumbs" aria-label="រូបភាពទាំងអស់">
          @foreach ($gallery as $i => $url)
            <li class="pb-r" data-r="toss" style="--t:{{ [-4, 3, -2, 5, -3, 2][$i % 6] }}deg">
              <button type="button" class="pb-ph pb-thumb" data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="{{ ($captions[$i] ?? '') ?: 'មើលរូបភាពទី '.$khDigits($i + 1) }}">
                <img src="{{ $url }}" alt="" loading="lazy">
              </button>
            </li>
          @endforeach
        </ul>
      </section>
    @endif

    {{-- ============ 9 · VENUE: a folded map that unfolds ============ --}}
    @if ($showVenue)
      <section class="pb-sec {{ $sheet() }}" id="pb-venue">
      {!! $decor() !!}
        {!! $head('Where to find us', 'ទីតាំងកម្មវិធី', 13) !!}
        <div class="pb-card pb-venue pb-r" data-r="pop">
          @if ($venueName)<p class="pb-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="pb-body">{{ $venueAddress }}</p>@endif
          @if ($timeKhmer)<p class="pb-accent-text">{{ $timeKhmer }}</p>@endif
          <div class="pb-map" id="pb-map">
            @for ($p = 0; $p < 3; $p++)
              <div class="pb-map-panel" style="--p:{{ $p }}">
                <svg viewBox="0 0 300 270" preserveAspectRatio="xMidYMid slice" style="top:{{ -$p * 100 }}%" aria-hidden="true">
                  <rect width="300" height="270" fill="#e7efdc"/>
                  <path d="M-10 190C60 160 90 230 160 200S260 120 320 150" fill="none" stroke="#b9d7e6" stroke-width="22" stroke-linecap="round"/>
                  <g fill="#f4ead3">
                    <rect x="20" y="20" width="60" height="40" rx="6"/><rect x="100" y="30" width="44" height="54" rx="6"/><rect x="200" y="18" width="70" height="36" rx="6"/>
                    <rect x="30" y="90" width="50" height="50" rx="6"/><rect x="210" y="80" width="56" height="44" rx="6"/><rect x="60" y="225" width="70" height="30" rx="6"/>
                  </g>
                  <g fill="none" stroke="#fff" stroke-width="9" stroke-linecap="round"><path d="M0 75H300M160 0V270M0 150C80 140 120 160 300 165"/></g>
                  <g fill="#cfe0bf">@for ($t = 0; $t < 7; $t++)<circle cx="{{ 20 + $t * 43 }}" cy="{{ 128 + ($t % 2) * 10 }}" r="7"/>@endfor</g>
                  <g transform="translate(160 118)"><circle r="20" fill="{{ $accent }}" fill-opacity=".2"/><path d="M0 12C-12 3 -13 -4 -9.6 -8.4C-6 -12 -1.8 -10 0 -6.6C1.8 -10 6 -12 9.6 -8.4C13 -4 12 3 0 12Z" fill="{{ $accent }}"/></g>
                </svg>
              </div>
            @endfor
            @if ($mapEmbed)
              <iframe class="pb-map-frame" data-src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe>
            @endif
            <button type="button" class="pb-map-open" id="pb-map-open"><span aria-hidden="true">🗺</span> បើកផែនទី</button>
          </div>
          @if ($mapUrl)
            <a class="pb-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
              មើលផ្លូវ Google Maps
            </a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES: dress code swatches + tags ============ --}}
    @if ($showNotes)
      <section class="pb-sec {{ $sheet() }}" id="pb-notes">
      {!! $decor() !!}
        {!! $head('Good to know', 'ព័ត៌មានសម្រាប់ភ្ញៀវ', 15) !!}
        @if ($dressCode !== '')
          <div class="pb-card pb-dress pb-r" data-r="pop">
            <span class="pb-tape tr" aria-hidden="true"></span>
            <p class="pb-label">ពណ៌សម្លៀកបំពាក់</p>
            <p class="pb-script-sm">Dress code</p>
            @if ($dressColours)
              <ul class="pb-swatches" aria-label="ពណ៌សម្លៀកបំពាក់">
                @foreach ($dressColours as $i => $c)
                  <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                    <button type="button" class="pb-swatch" aria-label="{{ $c['name'] }}"><span class="pin" aria-hidden="true"></span></button>
                    <span class="pb-swatch-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="pb-body">{{ $dressCode }}</p>
          </div>
        @endif
        @if ($whatToBring !== '' || $rsvpBy)
          <div class="pb-tags">
            @if ($whatToBring !== '')
              <div class="pb-note-tag pb-r" data-r="swing"><span class="pb-tag-hole" aria-hidden="true"></span><p class="pb-label">សូមយកមកជាមួយ</p><p class="pb-script-sm">What to bring</p><p class="pb-body">{{ $whatToBring }}</p></div>
            @endif
            @if ($rsvpBy)
              <div class="pb-note-tag b pb-r" data-r="swing"><span class="pb-tag-hole" aria-hidden="true"></span><p class="pb-label">សូមឆ្លើយតបមុន</p><p class="pb-script-sm">Kindly reply by</p><p class="pb-body">{{ $rsvpBy }}</p></div>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 11 · RSVP: the reply folds into a paper plane ============ --}}
    @if ($showRsvp)
      <section class="pb-sec {{ $sheet() }}" id="pb-rsvp">
      {!! $decor() !!}
        {!! $head('Will you come?', 'សូមបញ្ជាក់ការចូលរួម', 17) !!}
        <div class="pb-rsvp-stage">
          <div class="pb-card pb-rsvp pb-r" data-r="pop" id="pb-rsvp-card">
            <span class="pb-tape tl" aria-hidden="true"></span>
            <p class="pb-body">{{ $rsvpQuestion }}</p>
            @if ($rsvpBy)<p class="pb-soft">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
            <div class="pb-rsvp-opts" id="pb-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
              <button type="button" class="pb-opt" data-status="yes" role="radio" aria-checked="false"><svg viewBox="-12 -11 24 22" aria-hidden="true"><use href="#pb-heart"/></svg>ខ្ញុំនឹងចូលរួម</button>
              <button type="button" class="pb-opt" data-status="no" role="radio" aria-checked="false"><span aria-hidden="true">✉</span>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
            </div>
            <form class="pb-rsvp-form" id="pb-rsvp-form">
              <label class="pb-sr" for="pb-rsvp-note">សារជូនពរ</label>
              <textarea id="pb-rsvp-note" rows="3" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
              <button type="submit" class="pb-btn">ផ្ញើជាយន្តហោះក្រដាស ✈</button>
            </form>
            <p class="pb-err" id="pb-rsvp-err" role="alert"></p>
          </div>
          <svg class="pb-plane" id="pb-plane" viewBox="0 0 64 44" aria-hidden="true">
            <path d="M2 22L62 2L40 42L30 28Z" fill="#fff"/><path d="M30 28L62 2L24 30Z" fill="#efe6da"/><path d="M30 28L34 40L40 42Z" fill="#ddd2c4"/>
          </svg>
          <div class="pb-card pb-rsvp-done" id="pb-rsvp-done" hidden role="status">
            <svg class="pb-done-heart" viewBox="-12 -11 24 22" aria-hidden="true"><use href="#pb-heart"/></svg>
            <p class="pb-script" id="pb-rsvp-done-title">Thank you!</p>
            <p class="pb-body" id="pb-rsvp-done-text"></p>
            <button type="button" class="pb-link" id="pb-rsvp-again">កែប្រែការឆ្លើយតប</button>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT: a box that opens ============ --}}
    @if ($giftQr)
      <section class="pb-sec {{ $sheet() }}" id="pb-gift-sec">
      {!! $decor() !!}
        {!! $head('With love and thanks', 'ចំណងដៃ', 19) !!}
        <p class="pb-body pb-gift-msg pb-r" data-r="up">{{ $giftMessage }}</p>
        <div class="pb-gift pb-r" data-r="pop" id="pb-gift">
          <div class="pb-gift-qr">
            <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
            @if ($giftAccount)<p class="pb-gift-acc">{{ $giftAccount }}</p>@endif
          </div>
          <button type="button" class="pb-box" id="pb-box" aria-label="បើកប្រអប់កាដូ">
            <span class="pb-box-lid" aria-hidden="true"><span class="bow"></span></span>
            <span class="pb-box-base" aria-hidden="true"></span>
            <span class="pb-box-hint">ចុចដើម្បីបើកកាដូ</span>
          </button>
        </div>
        <div class="pb-gift-btns" id="pb-gift-btns">
          @if ($giftNumber)<button type="button" class="pb-btn" id="pb-copy" data-copy="{{ $giftNumber }}">ចម្លងលេខគណនី</button>@endif
          <a class="pb-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACT + CUSTOM BUTTON ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="pb-sec {{ $sheet() }}" id="pb-contact">
      {!! $decor() !!}
        {!! $head('Questions? Call us', 'ទំនាក់ទំនង', 21) !!}
        <div class="pb-contacts">
          @foreach ($contacts as $i => $c)
            <a class="pb-card pb-contact pb-r" data-r="{{ $i % 2 ? 'right' : 'left' }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p">{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
        @if ($showCta)
          <a class="pb-btn pb-cta pb-r" data-r="pop" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }}</a>
        @endif
      </section>
    @endif

    {{-- ============ 14 · THANKS / APOLOGY (lined letter paper) ============ --}}
    @if ($letterRows->isNotEmpty())
      <section class="pb-sec {{ $sheet() }}" id="pb-letters">
      {!! $decor() !!}
        @foreach ($letterRows as [$khHead, $en, $text])
          <article class="pb-letter pb-r" data-r="{{ $loop->index ? 'right' : 'left' }}">
            <span class="pb-tape top" aria-hidden="true"></span>
            <p class="pb-label">{{ $khHead }}</p>
            <p class="pb-script">{{ $en }}</p>
            <p class="pb-letter-text">{{ $text }}</p>
          </article>
        @endforeach
      </section>
    @endif

    {{-- ============ 15 · FINALE ============ --}}
    <section class="pb-finale pb-sheet s{{ $sheetN % 4 }}" id="pb-finale">
      {!! $decor() !!}
      @if ($finaleBg)
        <div class="pb-finale-photo pb-r" data-r="zoom"><img src="{{ $finaleBg }}" alt="" loading="lazy"></div>
      @endif
      <span class="pb-finale-bunch l pb-r" data-r="pop">{!! $bunch(61, 4, 1.6) !!}</span>
      <span class="pb-finale-bunch r pb-r" data-r="pop">{!! $bunch(77, 4, 1.6) !!}</span>
      <p class="pb-finale-line pb-r" data-r="up">{{ $closingLine }}</p>
      <p class="pb-body pb-r" data-r="up">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
      @if ($coupleTitle)<p class="pb-finale-names pb-r{{ $kh($coupleTitle) }}" data-r="up">{{ $coupleTitle }}</p>@endif
      <button type="button" class="pb-btn pb-throw" id="pb-throw">
        <svg viewBox="-20 -20 40 40" aria-hidden="true" style="color:#fff"><use href="#pb-fl"/></svg>
        បាចផ្កាអបអរ
      </button>
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'pb-kh' : ''" :date="$dateNumeric"
      class="pb-footer"
      style="--inv-footer-bg:#4b3a34; --inv-footer-text:#fbf1e6; --inv-footer-muted:#d9c6b6; --inv-footer-accent:{{ $accent }}; --inv-footer-ring:rgba(255,255,255,.25); --inv-footer-names-font:'Allura','Moulpali',cursive; --inv-footer-caps-font:'Fraunces','Noto Serif Khmer',serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><svg viewBox="-20 -20 40 40" aria-hidden="true" style="color:{{ $accent }}"><use href="#pb-fl"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview --}}
  <div class="pb-lb" id="pb-lb" hidden role="dialog" aria-modal="true" aria-label="កម្រងរូបភាព">
    <div class="pb-lb-bg" id="pb-lb-bg"></div>
    <figure class="pb-lb-fig" id="pb-lb-fig"><img id="pb-lb-img" alt="" draggable="false"><figcaption id="pb-lb-cap"></figcaption></figure>
    <p class="pb-lb-count" id="pb-lb-count" aria-live="polite"></p>
    <button type="button" class="pb-lb-nav prev" id="pb-lb-prev" aria-label="រូបមុន">&#8249;</button>
    <button type="button" class="pb-lb-nav next" id="pb-lb-next" aria-label="រូបបន្ទាប់">&#8250;</button>
    <button type="button" class="pb-lb-x" id="pb-lb-x" aria-label="បិទ">&times;</button>
  </div>
  <div class="pb-toast" id="pb-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything --}}
  @if ($musicFile)<audio id="pb-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
  <div class="pb-sound-wrap">
    @if ($songTitle !== '' && ($musicFile || $youtubeId))
      <p class="pb-now" id="pb-now"><span class="disc" aria-hidden="true"></span><span class="t">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $musicFile && ! $youtubeId)
      <a class="pb-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></a>
    @else
      <button type="button" class="pb-sound" id="pb-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="pb-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'paper' => $sfx('papercrackle1.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'send' => $sfx('envelope.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $pbAsset('js/invitations/paper-bloom.js') }}"></script>
