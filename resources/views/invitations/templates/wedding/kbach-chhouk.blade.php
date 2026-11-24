@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Kbach Chhouk (ក្បាច់ឈូក): a modern, light Khmer wedding invitation. Ivory silk, jade and
    // lotus pink, with fine gold kbach line art drawn procedurally below (phni curls, kanok vines,
    // pka chan, lotus buds and blooms, a lotus pond). Lines draw themselves, lotus buds open petal
    // by petal, water ripples.
    // Cover: a lotus pond at dawn, the couple's photo in a lotus-petal frame rising from the water.
    // Tap «បើកសំបុត្រ» → ripples spread, a giant lotus closes round the photo and blooms open petal
    // by petal in 3D, revealing the page while the photo flows into the hero frame.
    // Everything personal comes from $fields; on demo pages $invitation is null (all extras show).
    // CSS public/css/invitations/kbach-chhouk.css · JS public/js/invitations/kbach-chhouk.js
    // Debug: ?open=1 skips the cover.
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $kcAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $khDayShort = ['អា', 'ច', 'អ', 'ពុ', 'ព្រ', 'សុ', 'ស'];
    $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $khDate = fn ($d) => 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year);

    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' kc-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '♡');

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#2f7a5a';
    $headline = trim($fields['headline'] ?? '') ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍';
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
            'en' => $enMonths[$eventDate->month - 1].' '.$eventDate->year,
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
        ['លិខិតសូមអភ័យទោស', 'With our apologies', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'សូមអរគុណ';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
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
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';

    // ================= kbach line art, drawn here =================
    $n1 = fn (float $v) => (string) round($v, 1);
    // smooth path through points (Catmull-Rom → cubic Bézier)
    $smooth = function (array $p) use ($n1): string {
        $c = count($p);
        $d = 'M'.$n1($p[0][0]).' '.$n1($p[0][1]);
        for ($i = 0; $i < $c - 1; $i++) {
            $p0 = $p[max(0, $i - 1)];
            $p1 = $p[$i];
            $p2 = $p[$i + 1];
            $p3 = $p[min($c - 1, $i + 2)];
            $d .= 'C'.$n1($p1[0] + ($p2[0] - $p0[0]) / 6).' '.$n1($p1[1] + ($p2[1] - $p0[1]) / 6)
                .' '.$n1($p2[0] - ($p3[0] - $p1[0]) / 6).' '.$n1($p2[1] - ($p3[1] - $p1[1]) / 6)
                .' '.$n1($p2[0]).' '.$n1($p2[1]);
        }

        return $d;
    };
    // kbach phni curl: leaves (x,y) heading $deg, runs almost straight, then winds into a tightening spiral
    // ($turn 1 = clockwise on screen). Returns the end point too, for a little bud.
    $curlPts = function (float $x, float $y, float $size, float $deg, int $turn = 1, float $turns = 1.25): array {
        $pts = [[$x, $y]];
        $h = deg2rad($deg);
        $n = 30;
        for ($i = 1; $i <= $n; $i++) {
            $t = $i / $n;
            $step = $size * .15 * (1 - $t * .86);
            $h += $turn * ($turns * 2 * M_PI / $n) * (.25 + 1.5 * $t);
            $x += cos($h) * $step;
            $y += sin($h) * $step;
            $pts[] = [$x, $y];
        }

        return $pts;
    };
    $curl = fn (float $x, float $y, float $size, float $deg, int $turn = 1, float $turns = 1.25) => $smooth($curlPts($x, $y, $size, $deg, $turn, $turns));
    // a pointed kbach flame-leaf from (x,y) along $deg, bending to one side
    $flame = function (float $x, float $y, float $len, float $deg, float $w = .32, int $bend = 1) use ($n1): string {
        $a = deg2rad($deg);
        $ux = cos($a);
        $uy = sin($a);
        $px = -$uy;
        $py = $ux;
        $tx = $x + $ux * $len + $px * $len * .18 * $bend;
        $ty = $y + $uy * $len + $py * $len * .18 * $bend;
        $c1 = [$x + $ux * $len * .45 + $px * $len * $w, $y + $uy * $len * .45 + $py * $len * $w];
        $c2 = [$x + $ux * $len * .55 - $px * $len * $w * .7, $y + $uy * $len * .55 - $py * $len * $w * .7];

        return 'M'.$n1($x).' '.$n1($y).'Q'.$n1($c1[0]).' '.$n1($c1[1]).' '.$n1($tx).' '.$n1($ty).'Q'.$n1($c2[0]).' '.$n1($c2[1]).' '.$n1($x).' '.$n1($y).'Z';
    };
    // kanok vine along a straight line of length $len (x from 0): a wavy stem, a phni curl off every crest
    // (alternating sides) with a flame-leaf at its root. Returns stroke paths (pathLength=1 for drawing).
    $vine = function (float $len, float $amp = 5, float $wave = 46, float $curlSize = 15) use ($smooth, $curl, $flame): string {
        $pts = [];
        for ($x = 0; $x <= $len; $x += 4) {
            $pts[] = [$x, sin($x / $wave * 2 * M_PI) * $amp];
        }
        $out = '<path class="kc-ln" pathLength="1" d="'.$smooth($pts).'"/>';
        $k = 0;
        for ($x = $wave * .25; $x < $len - 8; $x += $wave / 2) {
            $up = $k % 2 === 0;
            $y = sin($x / $wave * 2 * M_PI) * $amp;
            $dir = $up ? -1 : 1;
            $out .= '<path class="kc-ln" pathLength="1" d="'.$curl($x, $y, $curlSize, $up ? -60 : 60, $up ? 1 : -1).'"/>';
            $out .= '<path class="kc-lf" d="'.$flame($x - 2, $y, $curlSize * .55, $up ? -150 : 150, .3, $dir).'"/>';
            $k++;
        }

        return $out;
    };
    // corner ornament for a 140×140 corner (the corner itself at 0,0): two short vines along the edges,
    // a big double phni curl and a small pka chan blossom
    $corner = (function () use ($vine, $curl, $flame): string {
        $out = '<g transform="translate(22 8)">'.$vine(110, 3.5, 40, 11).'</g>';
        $out .= '<g transform="translate(8 22) rotate(90) scale(1 -1)">'.$vine(110, 3.5, 40, 11).'</g>';
        $out .= '<path class="kc-ln" pathLength="1" d="'.$curl(10, 10, 44, 45, 1, 1.15).'"/>';
        $out .= '<path class="kc-ln" pathLength="1" d="'.$curl(10, 10, 30, 45, -1, 1.1).'"/>';
        $out .= '<path class="kc-lf" d="'.$flame(12, 12, 30, 45, .26, 1).'"/>';
        $out .= '<g transform="translate(10 10) rotate(45)"><use href="#kc-chan"/></g>';

        return $out;
    })();
    $cornerSvg = fn (string $pos) => '<svg class="kc-corner '.$pos.'" viewBox="0 0 140 140" aria-hidden="true">'.$corner.'</svg>';
    $frame = fn () => $cornerSvg('tl').$cornerSvg('tr').$cornerSvg('bl').$cornerSvg('br');

    // section divider: a small lotus in the middle, phni curls and vines running out to both sides
    $dividerHalf = '<g transform="translate(-20 0) scale(-1 1)">'.$vine(96, 3, 34, 10).'</g>';
    $divider = '<svg class="kc-divider" viewBox="-130 -26 260 40" aria-hidden="true">'
        .'<g class="kc-div-l">'.$dividerHalf.'</g><g class="kc-div-r" transform="scale(-1 1)">'.$dividerHalf.'</g>'
        .'<path class="kc-ln" pathLength="1" d="'.$curl(-6, 2, 16, 200, -1).'"/><path class="kc-ln" pathLength="1" d="'.$curl(6, 2, 16, -20, 1).'"/>'
        .'<g transform="translate(0 4) scale(.55)"><use href="#kc-bloom-g"/></g></svg>';

    // a lotus that opens petal by petal (outer pair first, the centre petal last)
    $bloomSpec = [[-64, .6, 0], [64, .6, 0], [-40, .78, 1], [40, .78, 1], [-18, .92, 2], [18, .92, 2], [0, 1, 3]];
    $bloom = function (string $cls = '') use ($bloomSpec): string {
        $out = '<svg class="kc-bloom '.$cls.'" viewBox="-34 -46 68 52" aria-hidden="true"><g class="kc-bloom-in">';
        foreach ($bloomSpec as [$a, $s, $d]) {
            $out .= '<g class="kc-pt" style="--a:'.$a.'deg;--s:'.$s.';--d:'.$d.'"><path d="M0 0C-9 -11 -8 -30 0 -41C8 -30 9 -11 0 0Z"/><path class="v" d="M0 -5V-33"/></g>';
        }

        return $out.'<path class="kc-cup" d="M-13 -2C-7 5 7 5 13 -2"/></g></svg>';
    };

    // the lotus pond: water, ripples, lily pads with a notch, buds and blooms on stems (swaying)
    $pond = function (float $w, float $h, int $seed, int $pads, string $cls = '', float $waterAt = .34) use ($n1): string {
        mt_srand($seed);
        $water = $h * $waterAt;
        $out = '<svg class="kc-pond '.$cls.'" viewBox="0 0 '.$w.' '.$h.'" preserveAspectRatio="xMidYMax slice" aria-hidden="true">';
        $out .= '<rect y="'.$n1($water).'" width="'.$w.'" height="'.$n1($h - $water).'" fill="url(#kc-water)"/>';
        for ($r = 0; $r < 9; $r++) {
            $cx = $w * ($r + .5) / 9 + mt_rand(-30, 30);
            $cy = $water + ($h - $water) * mt_rand(20, 90) / 100;
            $out .= '<ellipse class="kc-rip" style="--d:'.($r * .7).'s" cx="'.$n1($cx).'" cy="'.$n1($cy).'" rx="'.mt_rand(26, 60).'" ry="'.mt_rand(3, 6).'"/>';
        }
        $items = [];
        for ($p = 0; $p < $pads; $p++) {
            // denser near the middle (what a phone shows), spread wide for desktop
            $u = ($p + .5) / $pads;
            $x = $w / 2 + ($u - .5) * $w * (.55 + .45 * abs($u - .5) * 2) + mt_rand(-18, 18);
            $y = $water + ($h - $water) * mt_rand(18, 92) / 100;
            $items[] = [$x, $y, mt_rand(70, 130) / 100 * (.55 + ($y - $water) / ($h - $water) * .7), $p];
        }
        usort($items, fn ($a, $b) => $a[1] <=> $b[1]);
        foreach ($items as [$x, $y, $s, $p]) {
            $rx = 34 * $s;
            $ry = 9 * $s;
            $notch = deg2rad(mt_rand(0, 359));
            $a1 = $notch + .2;
            $a2 = $notch - .2;
            $out .= '<path class="kc-pad" d="M'.$n1($x).' '.$n1($y).'L'.$n1($x + cos($a1) * $rx).' '.$n1($y + sin($a1) * $ry)
                .'A'.$n1($rx).' '.$n1($ry).' 0 1 1 '.$n1($x + cos($a2) * $rx).' '.$n1($y + sin($a2) * $ry).'Z"/>';
            $out .= '<path class="kc-pad-v" d="M'.$n1($x).' '.$n1($y).'l'.$n1(-$rx * .6).' '.$n1(-$ry * .3).'M'.$n1($x).' '.$n1($y).'l'.$n1($rx * .62).' '.$n1(-$ry * .2).'M'.$n1($x).' '.$n1($y).'l'.$n1($rx * .1).' '.$n1($ry * .7).'"/>';
            if ($p % 3 !== 1) {
                $stem = (40 + mt_rand(0, 50)) * $s;
                $lean = mt_rand(-12, 12) * $s;
                $kind = $p % 3 === 0 ? 'kc-bloom-g' : 'kc-bud-g';
                $out .= '<g class="kc-sway" style="--sd:'.(mt_rand(0, 30) / 10).'s;transform-origin:'.$n1($x).'px '.$n1($y).'px">'
                    .'<path class="kc-stem" d="M'.$n1($x).' '.$n1($y).'Q'.$n1($x + $lean * 1.6).' '.$n1($y - $stem * .5).' '.$n1($x + $lean).' '.$n1($y - $stem).'"/>'
                    .'<g transform="translate('.$n1($x + $lean).' '.$n1($y - $stem + 1).') scale('.round($s * ($kind === 'kc-bloom-g' ? 1.05 : .9), 2).')"><use href="#'.$kind.'"/></g></g>';
            }
        }

        return $out.'</svg>';
    };

    // the background silk: Khmer hol (ikat) stepped diamonds, very pale, as a CSS image
    $holSvg = (function (string $accent): string {
        $s = '<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 72 72">';
        foreach ([[36, 36, 7, $accent, .05], [36, 36, 3, '#d98e98', .1], [0, 0, 4, '#b8955a', .055], [72, 0, 4, '#b8955a', .055], [0, 72, 4, '#b8955a', .055], [72, 72, 4, '#b8955a', .055]] as [$cx, $cy, $r, $c, $o]) {
            for ($k = -$r; $k <= $r; $k++) {
                $wd = ($r - abs($k)) * 3 + 1.5;
                $s .= '<rect x="'.($cx - $wd).'" y="'.($cy + $k * 3 - 1.5).'" width="'.($wd * 2).'" height="3" fill="'.$c.'" fill-opacity="'.$o.'"/>';
            }
        }
        $s .= '<path d="M0 36H10M62 36H72M36 0V10M36 62V72" stroke="#b8955a" stroke-opacity=".12" stroke-dasharray="2 3"/></svg>';

        return "url('data:image/svg+xml,".rawurlencode($s)."')";
    })($accent);

    // big line-art lotus plants peeking in from a section's edges (they drift with scroll: data-speed)
    $plant = function (int $seed) use ($smooth, $curl, $flame, $n1): string {
        mt_srand($seed);
        $lean = mt_rand(-20, 20);
        $o = '<path class="kc-stem" d="M100 400C96 320 '.(104 + $lean).' 220 '.(108 + $lean / 2).' 132"/>';
        $o .= '<path class="kc-stem" d="M96 400C90 330 70 260 '.(58 + $lean / 3).' 206"/>';
        $o .= '<path class="kc-stem" d="M104 400C112 340 140 300 150 268"/>';
        // a lotus leaf seen from the side: a shallow bowl with a wavy rim and veins
        $o .= '<path class="kc-pad" d="M104 270C128 246 172 240 198 258C180 280 136 290 104 270Z"/>';
        $o .= '<path class="kc-pad-v" d="M150 268L120 266M150 268L176 256M150 268L190 262M150 268L138 254"/>';
        $o .= '<g transform="translate('.(108 + $lean / 2).' 134) scale(2.3)"><use href="#kc-bloom-g"/></g>';
        $o .= '<g transform="translate('.$n1(58 + $lean / 3).' 208) scale(1.5) rotate(-12)"><use href="#kc-bud-g"/></g>';
        $o .= '<path class="kc-ln" d="'.$curl(100, 330, 46, -150, 1).'"/><path class="kc-ln" d="'.$curl(104, 300, 34, -30, -1).'"/>';
        $o .= '<path class="kc-lf" d="'.$flame(100, 330, 26, -120, .3, 1).'"/>';

        return $o;
    };
    $decoN = 0;
    $deco = function () use (&$decoN, $plant): string {
        $n = $decoN++;

        return '<div class="kc-deco" aria-hidden="true">'
            .'<svg class="l" data-speed=".07" viewBox="0 0 200 400">'.$plant(40 + $n).'</svg>'
            .'<svg class="r" data-speed="-.05" viewBox="0 0 200 400"><g transform="translate(200 0) scale(-1 1)">'.$plant(80 + $n).'</g></svg></div>';
    };

    $head = fn (string $en, string $khText) => '<header class="kc-head">'
        .'<p class="kc-script kc-r" data-r="rise">'.e($en).'</p>'
        .'<h2 class="kc-title kc-r" data-r="wipe">'.e($khText).'</h2>'
        .'<div class="kc-r kc-draw" data-r="draw">'.$divider.'</div></header>';

    $skipGate = request()->boolean('open');
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Cormorant Garamond / Pinyon Script are Latin-only: the CSS stacks always follow them with Noto Serif Khmer / Moulpali.
     Kantumruy Pro and Noto Serif Khmer carry both scripts --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Pinyon+Script&family=Moulpali&family=Moul&family=Noto+Serif+Khmer:wght@400;500;600&family=Battambang:wght@400;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $kcAsset('css/invitations/kbach-chhouk.css') }}">
<noscript><style>.kc-gate{display:none}.kc-page{visibility:visible!important}.kc-r{opacity:1!important;transform:none!important;clip-path:none!important}html.kc-locked,html.kc-locked body{overflow:auto!important}</style></noscript>

<div class="kc{{ $skipGate ? ' is-open' : '' }}" style="--kc-accent: {{ $accent }}; --kc-hol: {{ $holSvg }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- lotus-petal frame (pointed top, round base) for photos --}}
      <clipPath id="kc-petal-clip" clipPathUnits="objectBoundingBox">
        <path d="M.5 0C.62 .1 1 .3 1 .62C1 .86 .78 1 .5 1C.22 1 0 .86 0 .62C0 .3 .38 .1 .5 0Z"/>
      </clipPath>
      <linearGradient id="kc-petal-grad" x1="0" y1="1" x2="0" y2="0">
        <stop offset="0" stop-color="#fffaf3"/><stop offset=".6" stop-color="#f7d9da"/><stop offset="1" stop-color="#e8a2ab"/>
      </linearGradient>
      <linearGradient id="kc-water-dark" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#0c2219" stop-opacity="0"/><stop offset=".35" stop-color="#0c2219" stop-opacity=".45"/><stop offset="1" stop-color="#0c2219" stop-opacity=".7"/>
      </linearGradient>
      <linearGradient id="kc-water" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0" stop-color="#e3efe7" stop-opacity=".0"/><stop offset=".25" stop-color="#d5e8dd" stop-opacity=".85"/><stop offset="1" stop-color="#b7d6c5"/>
      </linearGradient>
      {{-- side-view lotus (static), bud, pka chan (four-petal kbach blossom), small heart --}}
      <g id="kc-bloom-g">
        @foreach ($bloomSpec as [$a, $s])
          <path class="kc-g-pt" transform="rotate({{ $a }}) scale({{ $s }})" d="M0 0C-9 -11 -8 -30 0 -41C8 -30 9 -11 0 0Z"/>
        @endforeach
        <path class="kc-g-cup" d="M-13 -2C-7 5 7 5 13 -2"/>
      </g>
      <g id="kc-bud-g">
        <path class="kc-g-pt" d="M0 0C-8 -9 -8 -24 0 -33C8 -24 8 -9 0 0Z"/>
        <path class="kc-g-pt" transform="rotate(-20) scale(.75)" d="M0 0C-8 -9 -8 -24 0 -33C8 -24 8 -9 0 0Z"/>
        <path class="kc-g-pt" transform="rotate(20) scale(.75)" d="M0 0C-8 -9 -8 -24 0 -33C8 -24 8 -9 0 0Z"/>
      </g>
      <g id="kc-chan">
        @for ($p = 0; $p < 4; $p++)<path class="kc-chan-p" transform="rotate({{ $p * 90 }})" d="M0 0C-4 -3 -4 -8 0 -12C4 -8 4 -3 0 0Z"/>@endfor
        <circle r="2.1" class="kc-chan-c"/>
      </g>
      <g id="kc-heart"><path d="M0 9C-10 2 -11 -4 -8 -7.5C-5 -10.5 -1.5 -8.5 0 -5.5C1.5 -8.5 5 -10.5 8 -7.5C11 -4 10 2 0 9Z"/></g>
    </defs>
  </svg>

  {{-- ============ 0 · COVER: a lotus pond at dawn ============ --}}
  @unless ($skipGate)
    <div class="kc-gate" id="kc-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      <div class="kc-sky" aria-hidden="true"><span class="kc-sun"></span></div>
      <div class="kc-mist m1" aria-hidden="true"></div><div class="kc-mist m2" aria-hidden="true"></div>
      {{-- fine kbach line frame round the whole cover --}}
      <div class="kc-gate-frame kc-draw is-drawn" aria-hidden="true">{!! $frame() !!}</div>

      <div class="kc-gate-top">
        <p class="kc-gate-kicker">{{ $headline }}</p>
        <p class="kc-gate-en">The Wedding Celebration</p>
      </div>

      <div class="kc-cover-photo-wrap" id="kc-cover-wrap">
        <svg class="kc-petal-frame kc-draw is-drawn" viewBox="-24 -44 148 194" aria-hidden="true">
          <path class="kc-ln o1" pathLength="1" transform="translate(50 70) scale(1.07) translate(-50 -70)" d="M50 0C62 13 100 39 100 80.6C100 111.8 78 130 50 130C22 130 0 111.8 0 80.6C0 39 38 13 50 0Z"/>
          <path class="kc-ln o2" pathLength="1" transform="translate(50 70) scale(1.14) translate(-50 -70)" d="M50 0C62 13 100 39 100 80.6C100 111.8 78 130 50 130C22 130 0 111.8 0 80.6C0 39 38 13 50 0Z"/>
          <path class="kc-ln" pathLength="1" d="{{ $curl(-6, 62, 26, 200, 1) }}"/><path class="kc-ln" pathLength="1" d="{{ $curl(106, 62, 26, -20, -1) }}"/>
          <path class="kc-ln" pathLength="1" d="{{ $curl(-8, 96, 20, 160, -1) }}"/><path class="kc-ln" pathLength="1" d="{{ $curl(108, 96, 20, 20, 1) }}"/>
          <path class="kc-lf" d="{{ $flame(-5, 78, 18, 180, .3, 1) }}"/><path class="kc-lf" d="{{ $flame(105, 78, 18, 0, .3, -1) }}"/>
          <g transform="translate(50 -9) scale(.62)"><use href="#kc-bud-g"/></g>
          <g transform="translate(50 -2)"><use href="#kc-chan"/></g>
        </svg>
        <div class="kc-cover-photo" id="kc-cover-photo">
          @if ($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" fetchpriority="high" draggable="false">
          @else
            <div class="kc-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>
          @endif
        </div>
        {{-- its reflection in the water --}}
        <div class="kc-reflect" aria-hidden="true">@if ($coverUrl)<img src="{{ $coverUrl }}" alt="">@endif</div>
      </div>

      {!! $pond(1200, 420, 7, 30, 'kc-gate-pond', .1) !!}
      <div class="kc-ripples" id="kc-ripples" aria-hidden="true"></div>

      <div class="kc-gate-text">
        @if ($titleImg)
          <img class="kc-gate-title" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
        @elseif ($coupleTitle)
          <h1 class="kc-gate-names">
            @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
            @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
            @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
          </h1>
        @endif
        @if ($dateNumeric)<p class="kc-gate-date">{{ $dateNumeric }}</p>@endif
        <p class="kc-gate-to">សូមគោរពអញ្ជើញ <b class="{{ trim($kh($guest)) ?: 'kc-latin' }}">{{ $guest }}</b></p>
        <button type="button" class="kc-open" id="kc-open">
          <svg viewBox="-34 -46 68 52" aria-hidden="true"><use href="#kc-bloom-g"/></svg>
          <span>បើកសំបុត្រ</span>
        </button>
      </div>
    </div>

    {{-- the giant lotus that closes round the photo and blooms open over the page --}}
    <div class="kc-lotus" id="kc-lotus" aria-hidden="true">
      @for ($p = 0; $p < 12; $p++)<span class="kc-lp outer" style="--r:{{ $p * 30 }}deg;--i:{{ $p }}"></span>@endfor
      @for ($p = 0; $p < 10; $p++)<span class="kc-lp inner" style="--r:{{ $p * 36 + 18 }}deg;--i:{{ $p }}"></span>@endfor
    </div>
  @endunless

  {{-- petals: a few drifting down, bursts on taps, lotus lanterns in the finale --}}
  <canvas class="kc-fx" id="kc-fx" aria-hidden="true"></canvas>

  {{-- desktop: kbach vines down both sides of the page --}}
  <div class="kc-rail l" aria-hidden="true"><svg viewBox="-30 0 60 900" preserveAspectRatio="xMidYMid meet"><g transform="translate(0 0) rotate(90)">{!! $vine(900, 7, 70, 22) !!}</g></svg></div>
  <div class="kc-rail r" aria-hidden="true"><svg viewBox="-30 0 60 900" preserveAspectRatio="xMidYMid meet"><g transform="rotate(90) scale(1 -1)">{!! $vine(900, 7, 70, 22) !!}</g></svg></div>

  <main class="kc-page" id="kc-top">

    {{-- ============ 1 · HERO ============ --}}
    <section class="kc-hero" id="kc-hero">
      <div class="kc-hero-glow" aria-hidden="true"></div>
      <p class="kc-hero-kicker kc-r" data-r="rise">{{ $headline }}</p>
      <div class="kc-hero-frame" id="kc-hero-frame">
        <svg class="kc-petal-frame kc-draw" id="kc-hero-lines" viewBox="-24 -44 148 194" aria-hidden="true">
          <path class="kc-ln o1" pathLength="1" transform="translate(50 70) scale(1.07) translate(-50 -70)" d="M50 0C62 13 100 39 100 80.6C100 111.8 78 130 50 130C22 130 0 111.8 0 80.6C0 39 38 13 50 0Z"/>
          <path class="kc-ln o2" pathLength="1" transform="translate(50 70) scale(1.14) translate(-50 -70)" d="M50 0C62 13 100 39 100 80.6C100 111.8 78 130 50 130C22 130 0 111.8 0 80.6C0 39 38 13 50 0Z"/>
          <path class="kc-ln" pathLength="1" d="{{ $curl(-6, 62, 26, 200, 1) }}"/><path class="kc-ln" pathLength="1" d="{{ $curl(106, 62, 26, -20, -1) }}"/>
          <path class="kc-ln" pathLength="1" d="{{ $curl(-8, 96, 20, 160, -1) }}"/><path class="kc-ln" pathLength="1" d="{{ $curl(108, 96, 20, 20, 1) }}"/>
          <path class="kc-lf" d="{{ $flame(-5, 78, 18, 180, .3, 1) }}"/><path class="kc-lf" d="{{ $flame(105, 78, 18, 0, .3, -1) }}"/>
          <g transform="translate(50 -9) scale(.62)"><use href="#kc-bud-g"/></g>
          <g transform="translate(50 -2)"><use href="#kc-chan"/></g>
        </svg>
        <div class="kc-hero-photo" id="kc-hero-photo">
          @if ($coverUrl)<img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}">@else<div class="kc-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>@endif
        </div>
      </div>
      <p class="kc-script kc-hero-en kc-r" data-r="rise">The wedding of</p>
      @if ($titleImg)
        <img class="kc-hero-title kc-r" data-r="rise" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
      @else
        <h1 class="kc-hero-names kc-r" data-r="wipe">
          @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
          @if ($groomPretty && $bridePretty)<em aria-hidden="true">&amp;</em>@endif
          @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
        </h1>
      @endif
      @if ($dateKhmer)<p class="kc-hero-date kc-r" data-r="rise">{{ $dateKhmer }}</p>@endif
      @if ($venueName)<p class="kc-hero-venue kc-r{{ $kh($venueName) }}" data-r="rise">{{ $venueName }}</p>@endif
      {!! $pond(1200, 200, 31, 20, 'kc-hero-pond') !!}
      <a class="kc-scroll" href="#kc-invite" aria-label="អានបន្ត" data-sfx="tap"><svg viewBox="-34 -46 68 52" aria-hidden="true"><use href="#kc-bud-g"/></svg></a>
    </section>

    {{-- ============ 2 · INVITATION + PARENTS in a fine kbach frame ============ --}}
    <section class="kc-sec" id="kc-invite">
        {!! $deco() !!}
      <article class="kc-card kc-invite kc-r kc-draw" data-r="fold">
        {!! $frame() !!}
        @if ($groomParents || $brideParents)
          <div class="kc-parents">
            <div class="kc-r" data-r="left"><p class="kc-label">មាតាបិតាខាងកូនប្រុស</p>@foreach ($groomParents as $line)<p class="kc-parent">{{ $line }}</p>@endforeach</div>
            {!! $bloom('kc-parents-lotus kc-r') !!}
            <div class="kc-r" data-r="right"><p class="kc-label">មាតាបិតាខាងកូនស្រី</p>@foreach ($brideParents as $line)<p class="kc-parent">{{ $line }}</p>@endforeach</div>
          </div>
        @endif
        <h2 class="kc-title kc-invite-title">សូមគោរពអញ្ជើញ</h2>
        <div class="kc-guest kc-r" data-r="bloom">
          <span class="kc-guest-name{{ $kh($guest) }}">{{ $guest }}</span>
        </div>
        <p class="kc-body kc-r" data-r="words">{{ $receptionText }}</p>
        @if ($coupleTitle)
          <div class="kc-couple">
            @if ($groomPretty)<div class="kc-r" data-r="left"><p class="kc-label">កូនប្រុសនាម</p><p class="kc-couple-name{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>@endif
            @if ($groomPretty && $bridePretty)<svg class="kc-couple-chan" viewBox="-14 -14 28 28" aria-hidden="true"><use href="#kc-chan"/></svg>@endif
            @if ($bridePretty)<div class="kc-r" data-r="right"><p class="kc-label">កូនស្រីនាម</p><p class="kc-couple-name{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>@endif
          </div>
        @endif
        @if ($eventDate)
          <div class="kc-when kc-r" data-r="rise">
            <p class="kc-when-date">{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="kc-soft">{{ $khmerLunarDate }}</p>@endif
            <p class="kc-accent-text">{{ $timeKhmer }}</p>
            @if ($venueName)<p class="kc-soft{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          </div>
        @endif
      </article>
    </section>

    {{-- ============ 3 · BRIDE & GROOM in lotus-petal frames ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="kc-sec" id="kc-couple">
        {!! $deco() !!}
        {!! $head('The bride & groom', 'កូនកំលោះ និងកូនក្រមុំ') !!}
        <div class="kc-duo">
          @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ']] as $k => [$photo, $name, $role])
            @if ($photo)
              <figure class="kc-duo-item kc-r" data-r="{{ $k ? 'fold-r' : 'fold-l' }}">
                <div class="kc-duo-frame">
                  <div class="kc-duo-photo"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></div>
                  <svg class="kc-duo-line" viewBox="-8 -8 116 146" aria-hidden="true"><path pathLength="1" transform="translate(50 65) scale(1.05) translate(-50 -65)" d="M50 0C62 13 100 39 100 80.6C100 111.8 78 130 50 130C22 130 0 111.8 0 80.6C0 39 38 13 50 0Z"/></svg>
                </div>
                <figcaption><span class="kc-label">{{ $role }}</span>@if ($name)<span class="kc-duo-name{{ $kh($name) }}">{{ $name }}</span>@endif</figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 4 · A NOTE from the couple ============ --}}
    @if ($personalNote !== '')
      <section class="kc-sec" id="kc-note">
        {!! $deco() !!}
        <blockquote class="kc-note kc-r kc-draw" data-r="wipe">
          <svg class="kc-note-vine top" viewBox="0 -14 300 28" preserveAspectRatio="none" aria-hidden="true">{!! $vine(300, 4, 50, 12) !!}</svg>
          {!! $bloom('kc-note-lotus') !!}
          <p class="kc-note-text kc-r" data-r="words">{{ $personalNote }}</p>
          @if ($coupleTitle)<p class="kc-note-sign{{ $kh($coupleTitle) }}">{{ $coupleTitle }}</p>@endif
          <svg class="kc-note-vine bottom" viewBox="0 -14 300 28" preserveAspectRatio="none" aria-hidden="true">{!! $vine(300, 4, 50, 12) !!}</svg>
        </blockquote>
      </section>
    @endif

    {{-- ============ 5 · THE DATE: Khmer lunar date, calendar, lotus countdown ============ --}}
    @if ($calendar)
      <section class="kc-sec kc-wide" id="kc-date">
        {!! $deco() !!}
        {!! $head('Save the date', 'ថ្ងៃមង្គល') !!}
        <div class="kc-date-grid">
          <div class="kc-card kc-lunar kc-r" data-r="fold">
            <p class="kc-lunar-day">{{ $khDays[$eventDate->dayOfWeek] }}</p>
            <p class="kc-lunar-num">{{ $khDigits($eventDate->day) }}</p>
            <p class="kc-lunar-month">ខែ{{ $khMonths[$eventDate->month - 1] }} ឆ្នាំ{{ $khDigits($eventDate->year) }}</p>
            @if ($khmerLunarDate)
              <div class="kc-lunar-moon" aria-hidden="true"><span></span></div>
              <p class="kc-lunar-kh">{{ $khmerLunarDate }}</p>
            @endif
            <p class="kc-accent-text">{{ $timeKhmer }}</p>
          </div>
          <div class="kc-card kc-cal kc-r" data-r="fold-r">
            <div class="kc-cal-head"><span class="kc-cal-en">{{ $calendar['en'] }}</span><span class="kc-cal-kh">{{ $calendar['title'] }}</span></div>
            <div class="kc-cal-grid" role="grid" aria-label="{{ $calendar['title'] }}">
              @foreach ($khDayShort as $dn)<span class="dn">{{ $dn }}</span>@endforeach
              @foreach ($calendar['cells'] as $c)
                @if ($c === null)
                  <span></span>
                @elseif ($c === $calendar['day'])
                  <span class="day the-day">{!! $bloom('kc-cal-lotus') !!}<b>{{ $khDigits($c) }}</b></span>
                @else
                  <span class="day">{{ $khDigits($c) }}</span>
                @endif
              @endforeach
            </div>
          </div>
        </div>
        @if ($showCountdown)
          <div class="kc-count" id="kc-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
            @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
              <div class="kc-medal kc-r" data-r="bloom" style="--i:{{ $loop->index }}">
                <svg class="kc-medal-ring" viewBox="-50 -50 100 100" aria-hidden="true">
                  <circle r="34" class="c1"/><circle r="30" class="c2"/>
                  @for ($p = 0; $p < 12; $p++)<path transform="rotate({{ $p * 30 }})" d="M0 -36C-5 -40 -5 -45 0 -49C5 -45 5 -40 0 -36Z"/>@endfor
                </svg>
                <b data-u="{{ $u }}">០០</b><span>{{ $label }}</span>
              </div>
            @endforeach
          </div>
          <p class="kc-count-done" id="kc-count-done" hidden>ថ្ងៃនេះហើយ ជាថ្ងៃមង្គល!</p>
        @endif
      </section>
    @endif

    {{-- ============ 6 · PROGRAMME: a lotus stem, buds bloom as you scroll ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="kc-sec" id="kc-program">
        {!! $deco() !!}
        {!! $head('Order of ceremonies', 'កម្មវិធីសិរីមង្គល') !!}
        @if ($scheduleImg)
          <figure class="kc-card kc-sched-img kc-r" data-r="fold">
            <button type="button" class="kc-ph" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីសិរីមង្គល"><img src="{{ $scheduleImg }}" alt="កម្មវិធីសិរីមង្គល" loading="lazy"></button>
          </figure>
        @else
          <div class="kc-stem-wrap" id="kc-stem">
            <svg class="kc-stem-line" aria-hidden="true">
              <path class="bg"/><path class="fg" pathLength="1"/>
            </svg>
            <ol class="kc-steps">
              @foreach ($schedule as $i => $row)
                <li class="kc-step" style="--i:{{ $i }}">
                  <span class="kc-step-bud">{!! $bloom() !!}</span>
                  <div class="kc-step-card">
                    <span class="t">{{ $schedTime($row['time'] ?? '') }}</span>
                    <span class="l{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
                  </div>
                </li>
              @endforeach
            </ol>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 7 · OUR STORY: along a winding river, lotus lanterns floating ============ --}}
    @if ($story->isNotEmpty())
      <section class="kc-sec kc-wide" id="kc-story">
        {!! $deco() !!}
        {!! $head('Our love story', 'ដំណើរស្នេហា') !!}
        <div class="kc-river" id="kc-river">
          <svg class="kc-river-svg" aria-hidden="true">
            <path class="w"/><path class="l" pathLength="1"/>
          </svg>
          <ol class="kc-chapters">
            @foreach ($story as $i => $c)
              @php([$place, $when] = $splitTitle($c['title'] ?? ''))
              <li class="kc-chapter {{ $i % 2 ? 'r' : 'l' }}" style="--k:{{ $i }}">
                <span class="kc-lantern" aria-hidden="true"><svg viewBox="-34 -46 68 52"><use href="#kc-bloom-g"/></svg><i></i></span>
                <div class="kc-chapter-card kc-r" data-r="{{ $i % 2 ? 'fold-r' : 'fold-l' }}">
                  @if ($gallery->isNotEmpty())
                    <button type="button" class="kc-chapter-ph kc-ph" data-full="{{ $gallery[($i + 1) % $gallery->count()] }}" data-cap="{{ $place }}"><img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy"></button>
                  @endif
                  <div class="kc-chapter-txt">
                    <span class="kc-chapter-num">{{ $khDigits($i + 1) }}</span>
                    @if ($when !== '')<span class="kc-chapter-when{{ $kh($when) }}">{{ $when }}</span>@endif
                    @if ($place !== '')<h3 class="kc-chapter-title{{ $kh($place) }}">{{ $place }}</h3>@endif
                    @if (! empty($c['text']))<p>{{ $c['text'] }}</p>@endif
                  </div>
                </div>
              </li>
            @endforeach
          </ol>
        </div>
      </section>
    @endif

    {{-- ============ 8 · GALLERY: lotus-arch frames and an ikat mosaic ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="kc-sec kc-wide" id="kc-gallery">
        {!! $deco() !!}
        {!! $head('Moments we treasure', 'កម្រងរូបភាពអនុស្សាវរីយ៍') !!}
        <div class="kc-mosaic">
          @foreach ($gallery as $i => $url)
            <figure class="kc-tile t{{ $i % 6 }} kc-r" data-r="{{ ['bloom', 'fold', 'wipe', 'fold-r', 'bloom', 'fold-l'][$i % 6] }}" style="--i:{{ $i % 3 }}">
              <button type="button" class="kc-ph" data-i="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="{{ ($captions[$i] ?? '') ?: 'រូបភាពទី '.$khDigits($i + 1) }}">
                <img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="lazy" draggable="false">
              </button>
              @if (($captions[$i] ?? '') !== '')<figcaption class="{{ trim($kh($captions[$i])) }}">{{ $captions[$i] }}</figcaption>@endif
            </figure>
          @endforeach
        </div>
        <p class="kc-hint kc-r" data-r="rise">ចុចលើរូប ដើម្បីមើលធំ</p>
      </section>
    @endif

    {{-- ============ 9 · VENUE ============ --}}
    @if ($showVenue)
      <section class="kc-sec" id="kc-venue">
        {!! $deco() !!}
        {!! $head('Where we celebrate', 'ទីតាំងប្រារព្ធពិធី') !!}
        <div class="kc-card kc-venue kc-r kc-draw" data-r="fold">
          {!! $frame() !!}
          @if ($venueName)<p class="kc-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="kc-body">{{ $venueAddress }}</p>@endif
          @if ($timeKhmer)<p class="kc-accent-text">{{ $timeKhmer }}</p>@endif
          <div class="kc-map" id="kc-map">
            <svg class="kc-map-art" viewBox="0 0 300 180" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
              <rect width="300" height="180" fill="#eef4ee"/>
              <path d="M-10 130C50 100 90 160 150 128S250 70 310 96" fill="none" stroke="#c9e0d3" stroke-width="20" stroke-linecap="round"/>
              <g fill="none" stroke="#fff" stroke-width="7" stroke-linecap="round"><path d="M0 52H300M118 0V180M0 100C80 92 140 110 300 104"/></g>
              <g fill="#f6efe2"><rect x="18" y="12" width="70" height="30" rx="5"/><rect x="134" y="16" width="56" height="26" rx="5"/><rect x="210" y="10" width="72" height="32" rx="5"/><rect x="22" y="64" width="64" height="24" rx="5"/><rect x="214" y="62" width="64" height="30" rx="5"/></g>
              <g transform="translate(150 84)"><circle r="22" fill="var(--kc-accent)" fill-opacity=".14"/><g transform="translate(0 6) scale(.7)"><use href="#kc-bloom-g"/></g></g>
            </svg>
            @if ($mapEmbed)
              <iframe class="kc-map-frame" data-src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe>
              <button type="button" class="kc-map-open" id="kc-map-open">បង្ហាញផែនទី</button>
            @endif
          </div>
          @if ($mapUrl)
            <a class="kc-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
              មើលផ្លូវ Google Maps
            </a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES: dress code as silk swatches ============ --}}
    @if ($showNotes)
      <section class="kc-sec" id="kc-notes">
        {!! $deco() !!}
        {!! $head('For our guests', 'ព័ត៌មានសម្រាប់ភ្ញៀវ') !!}
        @if ($dressCode !== '')
          <div class="kc-card kc-dress kc-r" data-r="fold">
            <p class="kc-label">ពណ៌សម្លៀកបំពាក់</p>
            <p class="kc-script kc-script-sm">Dress code</p>
            @if ($dressColours)
              <ul class="kc-swatches" aria-label="ពណ៌សម្លៀកបំពាក់">
                @foreach ($dressColours as $i => $c)
                  <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                    <button type="button" class="kc-swatch" aria-label="{{ $c['name'] }}"><span class="silk" aria-hidden="true"></span></button>
                    <span class="kc-swatch-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="kc-body">{{ $dressCode }}</p>
          </div>
        @endif
        @if ($whatToBring !== '' || $rsvpBy)
          <div class="kc-tags">
            @if ($whatToBring !== '')
              <div class="kc-card kc-tag kc-r" data-r="fold-l">{!! $bloom() !!}<p class="kc-label">សូមយកមកជាមួយ</p><p class="kc-body">{{ $whatToBring }}</p></div>
            @endif
            @if ($rsvpBy)
              <div class="kc-card kc-tag kc-r" data-r="fold-r">{!! $bloom() !!}<p class="kc-label">សូមឆ្លើយតបមុន</p><p class="kc-body">{{ $rsvpBy }}</p></div>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 11 · RSVP: the reply floats away as a lotus lantern ============ --}}
    @if ($showRsvp)
      <section class="kc-sec" id="kc-rsvp">
        {!! $deco() !!}
        {!! $head('Will you join us?', 'សូមបញ្ជាក់ការចូលរួម') !!}
        <div class="kc-rsvp-stage">
          <div class="kc-card kc-rsvp kc-r" data-r="fold" id="kc-rsvp-card">
            <p class="kc-body">{{ $rsvpQuestion }}</p>
            @if ($rsvpBy)<p class="kc-soft">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
            <div class="kc-rsvp-opts" id="kc-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
              <button type="button" class="kc-opt" data-status="yes" role="radio" aria-checked="false"><svg viewBox="-34 -46 68 52" aria-hidden="true"><use href="#kc-bloom-g"/></svg>ខ្ញុំនឹងចូលរួម</button>
              <button type="button" class="kc-opt" data-status="no" role="radio" aria-checked="false"><svg viewBox="-34 -46 68 52" aria-hidden="true"><use href="#kc-bud-g"/></svg>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
            </div>
            <form class="kc-rsvp-form" id="kc-rsvp-form">
              <label class="kc-sr" for="kc-rsvp-note">សារជូនពរ</label>
              <textarea id="kc-rsvp-note" rows="3" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
              <button type="submit" class="kc-btn">បណ្ដែតចម្លើយ</button>
            </form>
            <p class="kc-err" id="kc-rsvp-err" role="alert"></p>
          </div>
          <span class="kc-fly-lantern" id="kc-fly-lantern" aria-hidden="true"><svg viewBox="-34 -46 68 52"><use href="#kc-bloom-g"/></svg><i></i></span>
          <div class="kc-card kc-rsvp-done" id="kc-rsvp-done" hidden role="status">
            {!! $bloom('is-open') !!}
            <p class="kc-script" id="kc-rsvp-done-title">Thank you</p>
            <p class="kc-body" id="kc-rsvp-done-text"></p>
            <button type="button" class="kc-link" id="kc-rsvp-again">កែប្រែការឆ្លើយតប</button>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT: lotus petals fold open over the QR ============ --}}
    @if ($giftQr)
      <section class="kc-sec" id="kc-gift-sec">
        {!! $deco() !!}
        {!! $head('With love & thanks', 'ចំណងដៃ') !!}
        <p class="kc-body kc-gift-msg kc-r" data-r="rise">{{ $giftMessage }}</p>
        <div class="kc-gift kc-r" data-r="bloom" id="kc-gift">
          <div class="kc-gift-qr">
            <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
            @if ($giftAccount)<p class="kc-gift-acc">{{ $giftAccount }}</p>@endif
          </div>
          <button type="button" class="kc-gift-cover" id="kc-gift-open" aria-label="បើកមើល QR ចំណងដៃ">
            @for ($p = 0; $p < 8; $p++)<span class="kc-gp" style="--r:{{ $p * 45 }}deg;--i:{{ $p }}" aria-hidden="true"></span>@endfor
            <svg class="kc-gift-chan" viewBox="-14 -14 28 28" aria-hidden="true"><use href="#kc-chan"/></svg>
            <span class="kc-gift-hint">ចុចដើម្បីបើក</span>
          </button>
        </div>
        <div class="kc-gift-btns" id="kc-gift-btns">
          @if ($giftNumber)<button type="button" class="kc-btn" id="kc-copy" data-copy="{{ $giftNumber }}">ចម្លងលេខគណនី</button>@endif
          <a class="kc-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACT + CUSTOM BUTTON ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="kc-sec" id="kc-contact">
        {!! $deco() !!}
        {!! $head('Questions? Call us', 'ទំនាក់ទំនង') !!}
        <div class="kc-contacts">
          @foreach ($contacts as $i => $c)
            <a class="kc-card kc-contact kc-r" data-r="{{ $i % 2 ? 'fold-r' : 'fold-l' }}" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p">{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
        @if ($showCta)
          <a class="kc-btn kc-cta kc-r" data-r="bloom" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }}</a>
        @endif
      </section>
    @endif

    {{-- ============ 14 · THANKS / APOLOGY on silk letter paper ============ --}}
    @if ($letterRows->isNotEmpty())
      <section class="kc-sec kc-wide" id="kc-letters">
        {!! $deco() !!}
        <div class="kc-letters">
          @foreach ($letterRows as [$khHead, $en, $text])
            <article class="kc-card kc-letter kc-r kc-draw" data-r="{{ $loop->index ? 'fold-r' : 'fold-l' }}">
              {!! $cornerSvg('tl').$cornerSvg('br') !!}
              <p class="kc-script kc-script-sm">{{ $en }}</p>
              <h3 class="kc-title kc-title-sm">{{ $khHead }}</h3>
              <p class="kc-letter-text">{{ $text }}</p>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 15 · FINALE: lotus lanterns on the water ============ --}}
    <section class="kc-finale" id="kc-finale">
      @if ($finaleBg)
        <div class="kc-finale-photo" data-speed=".12"><img src="{{ $finaleBg }}" alt="" loading="lazy"></div>
      @endif
      <div class="kc-finale-veil" aria-hidden="true"></div>
      <div class="kc-finale-in">
        <div class="kc-r kc-draw" data-r="draw">{!! $divider !!}</div>
        <p class="kc-finale-line kc-r{{ $kh($closingLine) }}" data-r="wipe">{{ $closingLine }}</p>
        <p class="kc-body kc-r" data-r="rise">វត្តមានរបស់លោកអ្នក គឺជាពរជ័យ និងជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃមង្គលរបស់យើងខ្ញុំ។</p>
        @if ($coupleTitle)<p class="kc-finale-names kc-r{{ $kh($coupleTitle) }}" data-r="rise">{{ $coupleTitle }}</p>@endif
        <button type="button" class="kc-btn kc-release" id="kc-release">
          <svg viewBox="-34 -46 68 52" aria-hidden="true"><use href="#kc-bloom-g"/></svg>
          បណ្ដែតប្រទីបផ្កាឈូក
        </button>
      </div>
      {!! $pond(1200, 170, 53, 18, 'kc-finale-pond') !!}
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'kc-kh' : ''" :date="$dateNumeric"
      class="kc-footer"
      style="--inv-footer-bg:#1f4a3a; --inv-footer-text:#f7f1e4; --inv-footer-muted:#bcd2c4; --inv-footer-accent:#e8c98a; --inv-footer-ring:rgba(232,201,138,.35); --inv-footer-names-font:'Pinyon Script','Moulpali',cursive; --inv-footer-caps-font:'Cormorant Garamond','Noto Serif Khmer',serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><svg viewBox="-34 -46 68 52" aria-hidden="true" class="kc-footer-lotus"><use href="#kc-bloom-g"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- photo preview --}}
  <div class="kc-lb" id="kc-lb" hidden role="dialog" aria-modal="true" aria-label="កម្រងរូបភាព">
    <div class="kc-lb-bg" id="kc-lb-bg"></div>
    <figure class="kc-lb-fig"><img id="kc-lb-img" alt="" draggable="false"></figure>
    <p class="kc-lb-cap" id="kc-lb-cap"></p>
    <p class="kc-lb-count" id="kc-lb-count"></p>
    <button type="button" class="kc-lb-btn x" id="kc-lb-x" aria-label="បិទ">&times;</button>
    <button type="button" class="kc-lb-btn prev" id="kc-lb-prev" aria-label="រូបមុន">&#8249;</button>
    <button type="button" class="kc-lb-btn next" id="kc-lb-next" aria-label="រូបបន្ទាប់">&#8250;</button>
  </div>
  <div class="kc-toast" id="kc-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything --}}
  @if ($musicFile)<audio id="kc-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
  <div class="kc-sound-wrap">
    @if ($songTitle !== '' && ($musicFile || $youtubeId))
      <p class="kc-now" id="kc-now"><span class="disc" aria-hidden="true"></span><span class="t">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $musicFile && ! $youtubeId)
      <a class="kc-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></a>
    @else
      <button type="button" class="kc-sound" id="kc-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="kc-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'paper' => $sfx('papercrackle1.mp3'),
          'send' => $sfx('envelope.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $kcAsset('js/invitations/kbach-chhouk.js') }}"></script>
