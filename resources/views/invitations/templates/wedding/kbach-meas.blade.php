@php
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Kbach Meas (ក្បាច់មាស): a royal traditional Khmer wedding — gold kbach ornament on deep crimson.
    // Cover: a golden temple gate (khloang dvear) frames the couple's photo: a pediment fringed with
    // kbach phni flames, nāga finials, carved pilasters; the guest's name on a gold plaque. A tap on
    // «បើកសំបុត្រ» splits the carved gate into two doors that swing open in 3D on a seam of light,
    // gold lotus petals float out, the page opens through an iris and the photo flies into the hero arch.
    // Every ornament is drawn here as SVG (procedurally: the arch is sampled in equal steps and the
    // flames follow its normal) and draws itself / shimmers like gold leaf.
    // Everything personal comes from $fields (Khmer copy is template text). On demo pages $invitation is
    // null, so every premium extra previews.
    // CSS public/css/invitations/kbach-meas.css · JS public/js/invitations/kbach-meas.js
    // Debug: ?open=1 skips the cover.
    // ------------------------------------------------------------------------------------
    $resolveUrl = fn (string $path) => str_starts_with($path, 'http') ? $path : Storage::disk('s3')->url($path);
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $kmAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => asset('images/invitations/assets/sfx/'.$file);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];
    $enMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $khDate = fn ($d) => 'ថ្ងៃ'.$khDays[$d->dayOfWeek].' ទី'.$khDigits($d->day).' ខែ'.$khMonths[$d->month - 1].' ឆ្នាំ'.$khDigits($d->year);

    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);
    $kh = fn (?string $s) => $isKh($s) ? ' km-kh' : '';
    $pretty = fn (string $n) => $isKh($n) ? $n : mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);

    $groomName = trim($fields['groom_name'] ?? '');
    $brideName = trim($fields['bride_name'] ?? '');
    $groomPretty = $groomName !== '' ? $pretty($groomName) : '';
    $bridePretty = $brideName !== '' ? $pretty($brideName) : '';
    $coupleTitle = collect([$groomPretty, $bridePretty])->filter()->implode(' & ');
    $initial = fn (string $n) => $n !== '' ? mb_strtoupper(mb_substr($n, 0, 1)) : '';
    $initials = trim($fields['couple_initials'] ?? '')
        ?: (collect([$groomName, $brideName])->map($initial)->filter()->implode('&') ?: '❖');

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $fields['accent_color'] ?? '') ? $fields['accent_color'] : '#c9a14a';
    $headline = trim($fields['headline'] ?? '') ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍';
    $titleImg = ! empty($fields['title_image']) ? $resolveUrl($fields['title_image']) : null;
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';

    $eventDate = \App\Support\Invitations\EventDate::parse($fields['event_date'] ?? null);
    $dateNumeric = $eventDate ? $eventDate->format('d · m · Y') : null;
    $dateKhmer = $eventDate ? $khDate($eventDate) : null;
    $period = fn (int $h) => $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'));
    $khTime = fn (int $h, string $min) => $khDigits(sprintf('%d:%s', $h % 12 ?: 12, $min)).' '.$period($h);
    $endTime = preg_match('/^(\d{1,2}):(\d{2})/', trim($fields['end_time'] ?? ''), $et) ? $khTime((int) $et[1], $et[2]) : null;
    $timeKhmer = $eventDate ? 'វេលាម៉ោង '.$khTime($eventDate->hour, $eventDate->format('i')).($endTime ? ' – '.$endTime : '') : null;
    $khmerLunarDate = $isUnlocked('khmer_date') && ! empty($fields['khmer_date']) ? trim($fields['khmer_date']) : null;

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
        ['លិខិតសូមអភ័យទោស', 'A humble apology', trim($fields['apology_message'] ?? '')],
    ])->filter(fn ($l) => $l[2] !== '');
    $closingLine = trim($fields['closing_line'] ?? '') ?: 'សូមអរគុណ';

    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])->filter()->map($resolveUrl)->values();
    $captions = collect(preg_split('/\r\n|\r|\n/', (string) ($fields['photo_captions'] ?? '')))->map(fn ($c) => trim($c))->values();
    $coverUrl = ! empty($fields['cover_image']) ? $resolveUrl($fields['cover_image']) : $gallery->first();
    $finaleBg = ! empty($fields['background_image']) ? $resolveUrl($fields['background_image']) : $gallery->last();
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

    // ================= kbach ornament, drawn in PHP =================
    $n1 = fn (float $v) => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.') ?: '0';
    $pt = fn (array $p) => $n1($p[0]).' '.$n1($p[1]);

    // The Khmer temple-gate arch (a pointed ogee) over a box: straight sides, then two S-curves meeting in a
    // point. Returned as segments (L = line, C = cubic) from the bottom-left, up, over and down the right.
    $archSegs = function (float $x, float $y, float $w, float $h, ?float $s = null): array {
        $s = $s ?? min($h * .5, $w * .62);
        $P = fn (float $fx, float $fy) => [$x + $w * $fx, $y + $s * $fy];

        return [
            ['L', [$x, $y + $h], [$x, $y + $s]],
            ['C', [$x, $y + $s], $P(0, .45), $P(.2, .32), $P(.32, .19)],
            ['C', $P(.32, .19), $P(.42, .1), $P(.47, .05), $P(.5, 0)],
            ['C', $P(.5, 0), $P(.53, .05), $P(.58, .1), $P(.68, .19)],
            ['C', $P(.68, .19), $P(.8, .32), $P(1, .45), [$x + $w, $y + $s]],
            ['L', [$x + $w, $y + $s], [$x + $w, $y + $h]],
        ];
    };
    $segD = function (array $segs, bool $close = false) use ($pt): string {
        $d = 'M'.$pt($segs[0][1]);
        foreach ($segs as $sg) {
            $d .= $sg[0] === 'L' ? ' L'.$pt($sg[2]) : ' C'.$pt($sg[2]).' '.$pt($sg[3]).' '.$pt($sg[4]);
        }

        return $d.($close ? ' Z' : '');
    };
    $segAt = function (array $sg, float $t): array {
        if ($sg[0] === 'L') {
            return [$sg[1][0] + ($sg[2][0] - $sg[1][0]) * $t, $sg[1][1] + ($sg[2][1] - $sg[1][1]) * $t];
        }
        $u = 1 - $t;
        $c = [$u * $u * $u, 3 * $u * $u * $t, 3 * $u * $t * $t, $t * $t * $t];

        return [
            $c[0] * $sg[1][0] + $c[1] * $sg[2][0] + $c[2] * $sg[3][0] + $c[3] * $sg[4][0],
            $c[0] * $sg[1][1] + $c[1] * $sg[2][1] + $c[2] * $sg[3][1] + $c[3] * $sg[4][1],
        ];
    };
    // n points in equal steps along the segments (between fractions $from–$to), each with the angle that turns
    // «up» into the outward normal — so a flame drawn pointing up stands out of the arch
    $sample = function (array $segs, int $n, float $from = 0, float $to = 1) use ($segAt): array {
        $dense = [];
        foreach ($segs as $sg) {
            for ($i = 0; $i < 48; $i++) {
                $dense[] = $segAt($sg, $i / 48);
            }
        }
        $dense[] = $segAt(end($segs), 1);
        $len = [0];
        for ($i = 1, $c = count($dense); $i < $c; $i++) {
            $len[$i] = $len[$i - 1] + hypot($dense[$i][0] - $dense[$i - 1][0], $dense[$i][1] - $dense[$i - 1][1]);
        }
        $total = end($len);
        $out = [];
        $j = 0;
        for ($k = 0; $k < $n; $k++) {
            $target = $total * ($from + ($to - $from) * ($n === 1 ? .5 : $k / ($n - 1)));
            while ($j < count($len) - 2 && $len[$j + 1] < $target) {
                $j++;
            }
            [$a, $b] = [$dense[$j], $dense[$j + 1]];
            $t = ($target - $len[$j]) / max(.0001, $len[$j + 1] - $len[$j]);
            $dx = $b[0] - $a[0];
            $dy = $b[1] - $a[1];
            $out[] = ['x' => $a[0] + $dx * $t, 'y' => $a[1] + $dy * $t, 'rot' => rad2deg(atan2(-$dx, $dy)) + 90];
        }

        return $out;
    };
    // a spiral (kanok curl) as a polyline
    $spiral = function (float $cx, float $cy, float $r0, float $r1, float $a0, float $turns, int $dir = 1, int $steps = 30) use ($pt): string {
        $d = '';
        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            $r = $r0 + ($r1 - $r0) * $t;
            $a = $a0 + $dir * $t * $turns * 2 * M_PI;
            $d .= ($i ? ' L' : 'M').$pt([$cx + cos($a) * $r, $cy + sin($a) * $r]);
        }

        return $d;
    };
    // a flame (kbach phni) / any defs piece at a point: the outer <g> places it, the inner .km-grow is what CSS scales
    $place = fn (string $ref, float $x, float $y, float $rot, float $s, int $i = 0, bool $mirror = false, string $cls = 'km-grow') => '<g transform="translate('.$n1($x).' '.$n1($y).') rotate('.$n1($rot).')"><g class="'.$cls.'" style="--i:'.$i.'"><use href="#'.$ref.'" transform="scale('.($mirror ? -$s : $s).' '.$s.')"/></g></g>';

    // The temple gate: wall band between the outer arch and the window, beads + pka chan along it, gold lines,
    // phni flames round the outside (leaning up towards the point), a tall finial on top, nāga heads at the
    // shoulders and carved pilasters down the sides. $o: x y w h (window), band, flames (per side), fs (flame size)
    $gate = function (array $o) use ($archSegs, $segD, $sample, $place, $n1): string {
        ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h] = $o;
        $b = $o['band'] ?? 18;
        $win = $archSegs($x, $y, $w, $h);
        $s = min($h * .5, $w * .62);
        $outer = $archSegs($x - $b, $y - $b * 1.7, $w + 2 * $b, $h + $b * 1.7, $s + $b * 1.25);
        $mid = $archSegs($x - $b / 2, $y - $b * .85, $w + $b, $h + $b * .85, $s + $b * .62);
        $wall = $segD($outer, true).' '.$segD($win, true);
        $out = '<path class="km-g-wall" fill-rule="evenodd" d="'.$wall.'"/>';
        // beads and little four-petal flowers along the middle of the band
        $beads = $sample($mid, (int) ($o['beads'] ?? 34));
        foreach ($beads as $k => $p) {
            $out .= $k % 2
                ? '<circle class="km-g-bead" cx="'.$n1($p['x']).'" cy="'.$n1($p['y']).'" r="'.$n1($b * .09).'"/>'
                : $place('km-pkachan', $p['x'], $p['y'], $p['rot'], $b / 46, $k, false, 'km-grow km-g-pk');
        }
        $out .= '<path class="km-ln" pathLength="1" d="'.$segD($outer).'"/>';
        $out .= '<path class="km-ln thin" pathLength="1" d="'.$segD($archSegs($x - $b * .82, $y - $b * 1.42, $w + $b * 1.64, $h + $b * 1.42, $s + $b * 1.04)).'"/>';
        $out .= '<path class="km-ln" pathLength="1" d="'.$segD($win).'"/>';
        // flames on the arch only (not the straight sides), the middle one replaced by the finial
        $curve = array_slice($outer, 1, 4);
        $n = (int) ($o['flames'] ?? 9) * 2 + 1;
        $fs = $o['fs'] ?? .7;
        foreach ($sample($curve, $n, .03, .97) as $k => $p) {
            if ($k === intdiv($n, 2)) {
                continue;
            }
            $right = $k > $n / 2;
            $order = $right ? $n - 1 - $k : $k;
            $grow = .75 + .45 * ($order / ($n / 2)); // bigger towards the point
            $out .= $place('km-phni', $p['x'], $p['y'] + 1, $p['rot'], $fs * $grow, $order, $right);
        }
        $top = $outer[2][4];
        $out .= $place('km-finial', $top[0], $top[1] + 2, 0, $fs * 1.25, (int) ($n / 2) + 1);
        // nāga heads where the arch meets the pilasters
        if ($o['naga'] ?? true) {
            $out .= $place('km-naga', $x - $b, $y - $b * 1.7 + $s + $b * 1.25, 0, $fs * 1.1, 0);
            $out .= $place('km-naga', $x + $w + $b, $y - $b * 1.7 + $s + $b * 1.25, 0, $fs * 1.1, 0, true);
        }

        return $out;
    };

    // a kanok vine running down (length $len): a swaying stem, a curl off every crest, a flame at every trough
    $kanok = function (float $len, float $amp = 9, float $per = 70) use ($pt, $spiral, $place): string {
        $stem = '';
        $steps = (int) ($len / 4);
        for ($i = 0; $i <= $steps; $i++) {
            $y = $len * $i / $steps;
            $stem .= ($i ? ' L' : 'M').$pt([$amp * sin(2 * M_PI * $y / $per), $y]);
        }
        $curls = '';
        $leaves = '';
        for ($y = $per / 4, $k = 0; $y < $len - 20; $y += $per / 2, $k++) {
            $side = $k % 2 ? -1 : 1;
            $x = $amp * $side;
            $cx = $x + $side * $amp * .95;
            $cy = $y + $per * .1;
            $r0 = hypot($x - $cx, $y - $cy);
            $curls .= ' '.$spiral($cx, $cy, $r0, $r0 * .16, atan2($y - $cy, $x - $cx), 1.2, $side, 26);
            $leaves .= $place('km-phni', -$x * .7, $y + $per * .02, -$side * 62 - 0, .32, $k, $side > 0, 'km-grow');
        }

        return '<path class="km-ln" pathLength="1" d="'.$stem.'"/><path class="km-ln thin" pathLength="1" d="'.trim($curls).'"/><g class="km-vine-leaves">'.$leaves.'</g>';
    };

    // section heading ornament: a pka chan in the middle, kanok scrolls running out both ways, flames at the ends
    $dividerArt = (function () use ($spiral, $place, $pt): string {
        $half = function (int $dir) use ($spiral, $place, $pt): string {
            $stem = 'M'.$pt([$dir * 16, 0]);
            for ($i = 1; $i <= 30; $i++) {
                $x = 16 + $i * 4;
                $stem .= ' L'.$pt([$dir * $x, -sin($i / 30 * M_PI * 2) * 5 * (1 - $i / 40)]);
            }
            $curls = $spiral($dir * 46, 9, 7, 1.2, -M_PI / 2, 1.15, -$dir, 24).' '.$spiral($dir * 86, -8, 6, 1, M_PI / 2, 1.15, -$dir, 24);
            $out = '<path class="km-ln" pathLength="1" d="'.$stem.'"/><path class="km-ln thin" pathLength="1" d="'.$curls.'"/>';
            $out .= $place('km-phni', $dir * 136, 0, $dir * 90, .42, 4, $dir < 0);
            $out .= $place('km-phni', $dir * 64, -4, $dir * 30, .3, 2, $dir < 0);
            $out .= $place('km-phni', $dir * 104, 5, $dir * 150, .3, 3, $dir > 0);
            $out .= '<circle class="km-g-bead" cx="'.($dir * 24).'" cy="0" r="1.6"/>';

            return $out;
        };

        return $half(-1).$half(1).$place('km-pkachan', 0, 0, 45, .62, 0, false, 'km-grow km-g-pk');
    })();
    $divider = fn (string $cls = '') => '<svg class="km-divider '.$cls.'" viewBox="-150 -22 300 44" aria-hidden="true">'.$dividerArt.'</svg>';

    // frame corners: double L lines, a kanok curl, two flames
    $cornerArt = '<path class="km-ln" pathLength="1" d="M70 3H12Q3 3 3 12V70"/><path class="km-ln thin" pathLength="1" d="M70 9H16Q9 9 9 16V70"/>'
        .'<path class="km-ln thin" pathLength="1" d="'.$spiral(26, 26, 11, 1.5, M_PI * 1.25, 1.35, 1, 30).'"/>'
        .$place('km-phni', 44, 14, 90, .38, 1).$place('km-phni', 14, 44, 180, .38, 1, true).$place('km-pkachan', 26, 26, 0, .26, 2, false, 'km-grow km-g-pk');
    $corners = fn (string $cls = '') => collect(['tl', 'tr', 'bl', 'br'])->map(fn ($c) => '<svg class="km-corner '.$c.' '.$cls.'" viewBox="0 0 74 74" aria-hidden="true">'.$cornerArt.'</svg>')->implode('');

    // a mandala of kbach: rings of flames, petals and pka chan (hero + countdown medallions)
    $mandala = function (float $r, int $flames = 24) use ($place, $n1): string {
        $out = '<circle class="km-ln" pathLength="1" r="'.$n1($r * .62).'"/><circle class="km-ln thin" pathLength="1" r="'.$n1($r * .7).'"/><circle class="km-ln thin" pathLength="1" r="'.$n1($r * .36).'"/>';
        for ($k = 0; $k < $flames; $k++) {
            $a = $k * 360 / $flames;
            $out .= $place('km-phni', sin(deg2rad($a)) * $r * .7, -cos(deg2rad($a)) * $r * .7, $a, $r / 135, $k, $k % 2 === 1);
            $out .= '<circle class="km-g-bead" cx="'.$n1(sin(deg2rad($a + 180 / $flames)) * $r * .66).'" cy="'.$n1(-cos(deg2rad($a + 180 / $flames)) * $r * .66).'" r="'.$n1($r * .012).'"/>';
        }
        $out .= '<circle class="km-ln thin" pathLength="1" r="'.$n1($r * .98).'"/>';
        for ($k = 0; $k < 12; $k++) {
            $a = $k * 30 + 15;
            $out .= $place('km-pkachan', sin(deg2rad($a)) * $r * .49, -cos(deg2rad($a)) * $r * .49, $a, $r / 260, $k, false, 'km-grow km-g-pk');
        }
        for ($k = 0; $k < 8; $k++) {
            $out .= $place('km-petal', 0, 0, $k * 45, $r / 120, $k);
        }

        return $out;
    };

    // the background patterns are tiled data-URIs coloured with the couple's gold
    $tile = fn (string $svg) => 'url("data:image/svg+xml,'.rawurlencode($svg).'")';
    $petal4 = fn (float $cx, float $cy, float $s, string $fill, float $op) => '<g transform="translate('.$cx.' '.$cy.') scale('.$s.')" fill="'.$fill.'" fill-opacity="'.$op.'">'
        .'<path d="M0-2C-5-6-5.5-12 0-18C5.5-12 5-6 0-2Z"/><path transform="rotate(90)" d="M0-2C-5-6-5.5-12 0-18C5.5-12 5-6 0-2Z"/>'
        .'<path transform="rotate(180)" d="M0-2C-5-6-5.5-12 0-18C5.5-12 5-6 0-2Z"/><path transform="rotate(270)" d="M0-2C-5-6-5.5-12 0-18C5.5-12 5-6 0-2Z"/><circle r="2.6"/></g>';
    $latticeDark = $tile('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64"><g fill="none" stroke="'.$accent.'" stroke-opacity=".14" stroke-width=".8"><path d="M32 0L64 32L32 64L0 32Z"/><path d="M32 8L56 32L32 56L8 32Z" stroke-opacity=".07"/></g>'.$petal4(32, 32, .55, $accent, .2).$petal4(0, 0, .35, $accent, .16).$petal4(64, 0, .35, $accent, .16).$petal4(0, 64, .35, $accent, .16).$petal4(64, 64, .35, $accent, .16).'</svg>');
    $latticeLight = $tile('<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 56 56"><g fill="none" stroke="#8c1c24" stroke-opacity=".07" stroke-width=".8"><path d="M28 0L56 28L28 56L0 28Z"/></g>'.$petal4(28, 28, .5, $accent, .22).$petal4(0, 0, .3, '#8c1c24', .08).$petal4(56, 0, .3, '#8c1c24', .08).$petal4(0, 56, .3, '#8c1c24', .08).$petal4(56, 56, .3, '#8c1c24', .08).'</svg>');
    // the Angkor border band (lozenges + pka chan between two rules)
    $bandTile = $tile('<svg xmlns="http://www.w3.org/2000/svg" width="40" height="22" viewBox="0 0 40 22"><rect width="40" height="22" fill="#3d080d"/><g fill="none" stroke="'.$accent.'" stroke-width="1"><path d="M0 2.5H40M0 19.5H40"/><path d="M0 11L10 5L20 11L10 17Z M20 11L30 5L40 11L30 17Z" stroke-opacity=".75"/></g>'.$petal4(10, 11, .26, $accent, .95).$petal4(30, 11, .26, $accent, .95).'<circle cx="20" cy="11" r="1.3" fill="'.$accent.'"/><circle cx="0" cy="11" r="1.3" fill="'.$accent.'"/><circle cx="40" cy="11" r="1.3" fill="'.$accent.'"/></svg>');

    // the photo window (same arch on the cover and in the hero: the photo flies from one into the other)
    $archMask = $tile('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 300" preserveAspectRatio="none"><path d="'.$segD($archSegs(0, 0, 200, 300), true).'"/></svg>');
    $archLine = $segD($archSegs(4, 4, 192, 292), true);

    // the cover gate (window 200×300 at 100,150 in a 400-wide drawing) and the hero gate (same window, wider band)
    $coverGate = $gate(['x' => 100, 'y' => 150, 'w' => 200, 'h' => 300, 'band' => 22, 'flames' => 11, 'fs' => .78, 'beads' => 40]);
    $heroGate = $gate(['x' => 100, 'y' => 150, 'w' => 200, 'h' => 300, 'band' => 20, 'flames' => 10, 'fs' => .72, 'beads' => 36]);
    $portraitGate = $gate(['x' => 30, 'y' => 70, 'w' => 140, 'h' => 210, 'band' => 14, 'flames' => 8, 'fs' => .5, 'beads' => 26]);

    // side columns of kanok vines (cover sides, every section's edges)
    $vineArt = $kanok(1600, 9, 74);
    // a still mandala (no draw-in) kept once in <defs>: wide screens show it big and faint beside every section
    $mandalaFlat = (function () use ($n1): string {
        $r = 100;
        $out = '<g fill="none" stroke="url(#km-gold-s)"><circle r="98" stroke-width=".8"/><circle r="70" stroke-width="1.4"/><circle r="62" stroke-width=".8"/><circle r="36" stroke-width="1"/></g>';
        for ($k = 0; $k < 24; $k++) {
            $a = $k * 15;
            $out .= '<g transform="translate('.$n1(sin(deg2rad($a)) * 70).' '.$n1(-cos(deg2rad($a)) * 70).') rotate('.$a.') scale('.($k % 2 ? '-.74 .74' : '.74').')"><use href="#km-phni"/></g>';
            $out .= '<circle cx="'.$n1(sin(deg2rad($a + 7.5)) * 66).'" cy="'.$n1(-cos(deg2rad($a + 7.5)) * 66).'" r="1.3"/>';
        }
        for ($k = 0; $k < 12; $k++) {
            $a = $k * 30 + 15;
            $out .= '<g transform="translate('.$n1(sin(deg2rad($a)) * 49).' '.$n1(-cos(deg2rad($a)) * 49).') rotate('.$a.') scale(.38)"><use href="#km-pkachan"/></g>';
        }
        for ($k = 0; $k < 8; $k++) {
            $out .= '<g transform="rotate('.($k * 45).') scale(.85)"><use href="#km-petal"/></g>';
        }

        return '<g id="km-mandala" fill="url(#km-gold-s)">'.$out.'</g>';
    })();
    $vineN = 0;
    $vines = function () use (&$vineN, $vineArt): string {
        return '<div class="km-vines" aria-hidden="true"><svg class="km-side-orn '.(($vineN++) % 2 ? 'r' : 'l').'" viewBox="-104 -104 208 208"><use href="#km-mandala"/></svg>'
            .'<svg class="km-vine l" viewBox="-34 0 68 1600" preserveAspectRatio="xMidYMin slice">'.$vineArt.'</svg><svg class="km-vine r" viewBox="-34 0 68 1600" preserveAspectRatio="xMidYMin slice">'.$vineArt.'</svg></div>';
    };

    $head = fn (string $en, string $khText) => '<header class="km-head km-r" data-r="rise">'
        .'<p class="km-eyebrow">'.e($en).'</p><h2 class="km-title">'.e($khText).'</h2>'.$divider().'</header>';

    // sections alternate deep crimson and ivory silk
    $toneN = 0;
    $tone = function () use (&$toneN): string {
        return ($toneN++) % 2 ? 'km-light' : 'km-dark';
    };
    $band = '<div class="km-band" aria-hidden="true"><span class="km-band-medal"><svg viewBox="-24 -24 48 48"><circle r="21" class="km-medal-bg"/><use href="#km-pkachan" fill="url(#km-gold)" transform="scale(.95)"/></svg></span></div>';

    $skipGate = request()->boolean('open');
@endphp
<title>{{ $coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Cinzel / Cormorant Garamond / Pinyon Script are Latin-only: the CSS stacks always follow them with Moul /
     Noto Serif Khmer / Moulpali; Kantumruy Pro carries both scripts for the body --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Pinyon+Script&family=Moul&family=Moulpali&family=Noto+Serif+Khmer:wght@400;500;600;700&family=Kantumruy+Pro:wght@300;400;500;600&display=swap">
<link rel="stylesheet" href="{{ $kmAsset('css/invitations/kbach-meas.css') }}">
<noscript><style>.km-gate{display:none}.km-page{visibility:visible!important}.km-r,.km-r *{opacity:1!important;transform:none!important;clip-path:none!important}.km-hero-photo{opacity:1!important}</style></noscript>

<div class="km{{ $skipGate ? ' is-open' : '' }}" style="--km-gold: {{ $accent }}; --km-lattice-dark: {{ $latticeDark }}; --km-lattice-light: {{ $latticeLight }}; --km-band-tile: {{ $bandTile }}; --km-arch-mask: {{ $archMask }};">
  <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
      {{-- gold leaf: a gradient that slowly slides back and forth, so every ornament using it shimmers --}}
      <linearGradient id="km-gold" x1="0" y1="0" x2="1" y2="1" spreadMethod="reflect">
        <stop offset="0" style="stop-color:color-mix(in srgb, var(--km-gold) 55%, #3a2207)"/>
        <stop offset=".28" style="stop-color:color-mix(in srgb, var(--km-gold) 55%, #fff3c4)"/>
        <stop offset=".46" style="stop-color:var(--km-gold)"/>
        <stop offset=".56" style="stop-color:color-mix(in srgb, var(--km-gold) 20%, #fffbe8)"/>
        <stop offset=".7" style="stop-color:var(--km-gold)"/>
        <stop offset="1" style="stop-color:color-mix(in srgb, var(--km-gold) 60%, #4a2c08)"/>
        <animateTransform class="km-shimmer" attributeName="gradientTransform" type="translate" values="-.7 -.7;.7 .7;-.7 -.7" dur="7s" repeatCount="indefinite"/>
      </linearGradient>
      {{-- the same gold, still (big areas and many small pieces, cheaper to paint) --}}
      <linearGradient id="km-gold-s" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" style="stop-color:color-mix(in srgb, var(--km-gold) 60%, #3a2207)"/>
        <stop offset=".4" style="stop-color:color-mix(in srgb, var(--km-gold) 50%, #fff3c4)"/>
        <stop offset=".65" style="stop-color:var(--km-gold)"/>
        <stop offset="1" style="stop-color:color-mix(in srgb, var(--km-gold) 60%, #4a2c08)"/>
      </linearGradient>
      <radialGradient id="km-wall" cx=".5" cy=".35" r=".75">
        <stop offset="0" stop-color="#8c1c24"/><stop offset=".6" stop-color="#5a0f14"/><stop offset="1" stop-color="#36070b"/>
      </radialGradient>
      {{-- kbach phni: a flame leaning its curled tip to the right; base at 0,0, pointing up --}}
      <g id="km-phni">
        <path d="M-6.5 0C-10-7-9-14.5-3.8-19.8C-.2-23.6 1-27.8-1.2-33.5C4.2-31 8.6-25.4 8.4-19C8.2-14.6 5.6-11.6 5.2-7.8C5-5 6-2.4 7.2 0Z"/>
        <path d="M.6-2.6C-2.6-7.6-2.2-13 1.4-17.2C3.2-19.4 3.6-22 3-24.4" fill="none" stroke="#5a0f14" stroke-width="1.1" stroke-linecap="round" stroke-opacity=".45"/>
      </g>
      {{-- the finial on the point of the arch: a tall flame with a flame either side --}}
      <g id="km-finial">
        <path d="M-10 0C-14-13-7-24-2.6-34C-.6-39-.8-46 0-58C.8-46 .6-39 2.6-34C7-24 14-13 10 0Z"/>
        <path d="M0-8L-5-22L0-38L5-22Z" fill="#5a0f14" fill-opacity=".35"/>
        <g transform="translate(-9 -2) rotate(-28) scale(.6)"><use href="#km-phni" transform="scale(-1 1)"/></g>
        <g transform="translate(9 -2) rotate(28) scale(.6)"><use href="#km-phni"/></g>
      </g>
      {{-- nāga finial: the body rises and curls outward, a crest of small flames on the curl --}}
      <g id="km-naga">
        <path d="M0 0C-3-10-9-17-18-19C-27-21-34-14-32-7C-30-1-22-1-21-7" fill="none" stroke="url(#km-gold-s)" stroke-width="5" stroke-linecap="round"/>
        <circle cx="-25" cy="-11" r="1.6" fill="#36070b"/>
        <g transform="translate(-11 -19) rotate(-15) scale(.42)"><use href="#km-phni"/></g>
        <g transform="translate(-20 -22) rotate(-50) scale(.46)"><use href="#km-phni"/></g>
        <g transform="translate(-29 -19) rotate(-88) scale(.46)"><use href="#km-phni"/></g>
        <g transform="translate(-35 -11) rotate(-125) scale(.42)"><use href="#km-phni"/></g>
      </g>
      {{-- kbach pka chan: four pointed petals + four small ones between them --}}
      <g id="km-pkachan">
        @for ($p = 0; $p < 4; $p++)<path transform="rotate({{ $p * 90 }})" d="M0-2.5C-5.5-6.5-6-13 0-19.5C6-13 5.5-6.5 0-2.5Z"/>@endfor
        @for ($p = 0; $p < 4; $p++)<path transform="rotate({{ $p * 90 + 45 }}) scale(.56)" d="M0-2.5C-5.5-6.5-6-13 0-19.5C6-13 5.5-6.5 0-2.5Z"/>@endfor
        <circle r="3.4"/><circle r="1.5" fill="#5a0f14"/>
      </g>
      {{-- a lotus petal (floating petals, mandala) --}}
      <g id="km-petal"><path d="M0 0C-7.5-6-8.5-17 0-28C8.5-17 7.5-6 0 0Z"/><path d="M0-3V-22" stroke="#5a0f14" stroke-opacity=".3" stroke-width="1" fill="none"/></g>
      {{-- a lotus bud seen from the side (chhouk) --}}
      {!! $mandalaFlat !!}
      <g id="km-lotus">
        <path d="M0 0C-17-1-31-8-37-19C-25-19-11-11 0 0Z"/><path d="M0 0C17-1 31-8 37-19C25-19 11-11 0 0Z"/>
        <path d="M0 0C-14-6-23-18-22-33C-12-26-5-15 0 0Z"/><path d="M0 0C14-6 23-18 22-33C12-26 5-15 0 0Z"/>
        <path d="M0 0C-9-12-9-28 0-41C9-28 9-12 0 0Z"/>
        <path d="M-24 3Q0 11 24 3" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
      </g>
    </defs>
  </svg>

  {{-- ============ 0 · COVER: the golden temple gate ============ --}}
  @unless ($skipGate)
    <div class="km-gate" id="km-gate" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$coupleTitle) }}">
      <div class="km-rays" aria-hidden="true"><i></i></div>
      <div class="km-facade" id="km-facade">
        <div class="km-facade-bg" aria-hidden="true"></div>
        {{-- wide screens: carved pilasters and big lotus medallions out on the sides --}}
        <div class="km-cover-side l" aria-hidden="true">
          <svg class="km-cover-vine" viewBox="-34 0 68 1600" preserveAspectRatio="xMidYMid slice">{!! $vineArt !!}</svg>
          <svg class="km-cover-mandala" viewBox="-110 -110 220 220">{!! $mandala(100, 20) !!}</svg>
        </div>
        <div class="km-cover-side r" aria-hidden="true">
          <svg class="km-cover-vine" viewBox="-34 0 68 1600" preserveAspectRatio="xMidYMid slice">{!! $vineArt !!}</svg>
          <svg class="km-cover-mandala" viewBox="-110 -110 220 220">{!! $mandala(100, 20) !!}</svg>
        </div>
        <div class="km-band km-band-top" aria-hidden="true"></div>

        <div class="km-cover-in">
          <p class="km-cover-kicker{{ $kh($headline) }}">{{ $headline }}</p>
          <div class="km-cover-art" id="km-cover-art">
            <svg class="km-gate-svg" viewBox="20 50 360 426" aria-hidden="true">
              {!! $coverGate !!}
              {{-- pilasters under the nāga, with a stepped plinth --}}
              <g class="km-pilaster">
                <rect x="58" y="310" width="20" height="148" fill="var(--km-band-fill)"/><rect x="322" y="310" width="20" height="148" fill="var(--km-band-fill)"/>
                <path class="km-ln" pathLength="1" d="M58 310V458M78 310V458M322 310V458M342 310V458"/>
                <path class="km-ln" pathLength="1" d="M40 458H360M48 466H352M34 474H366"/>
              </g>
            </svg>
            <div class="km-cover-photo" id="km-cover-photo">
              @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}" fetchpriority="high" draggable="false">
              @else
                <div class="km-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>
              @endif
              <svg class="km-photo-line" viewBox="0 0 200 300" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $archLine }}" vector-effect="non-scaling-stroke"/></svg>
            </div>
          </div>

          @if ($titleImg)
            <img class="km-cover-title" src="{{ $titleImg }}" alt="{{ $coupleTitle }}">
          @elseif ($coupleTitle)
            <h1 class="km-cover-names">
              @if ($groomPretty)<span class="{{ trim($kh($groomPretty)) }}">{{ $groomPretty }}</span>@endif
              @if ($groomPretty && $bridePretty)<svg class="km-amp" viewBox="-22 -22 44 44" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold)"/></svg>@endif
              @if ($bridePretty)<span class="{{ trim($kh($bridePretty)) }}">{{ $bridePretty }}</span>@endif
            </h1>
          @endif
          @if ($dateKhmer)<p class="km-cover-date">{{ $dateKhmer }}</p>@endif

          {{-- the guest's name on a gold plaque --}}
          <div class="km-plaque">
            <span class="km-plaque-in">
              <span class="km-plaque-label">សូមគោរពអញ្ជើញ</span>
              <b class="km-plaque-name{{ $kh($guest) }}">{{ $guest }}</b>
            </span>
          </div>

          <button type="button" class="km-open" id="km-open">
            <span class="km-open-shine" aria-hidden="true"></span>
            <svg viewBox="-24 -44 48 48" aria-hidden="true"><use href="#km-lotus" fill="currentColor"/></svg>
            <span>បើកសំបុត្រ</span>
          </button>
          <p class="km-cover-hint"><span class="en">Tap to open</span> · ចុចដើម្បីបើក</p>
        </div>
        <div class="km-band km-band-bottom" aria-hidden="true"></div>
      </div>
      <div class="km-seam" aria-hidden="true"></div>
      <div class="km-flash" aria-hidden="true"></div>
      <div class="km-petals" id="km-petals" aria-hidden="true"></div>
    </div>
  @endunless

  {{-- gold dust + tumbling gold petals: taps, bursts and a few drifting all the time --}}
  <canvas class="km-dust" id="km-dust" aria-hidden="true"></canvas>

  <main class="km-page" id="km-top">

    {{-- ============ 1 · HERO: the photo inside a gold gate, a mandala turning behind ============ --}}
    <section class="km-hero km-dark km-sec km-r" data-r="sec" id="km-hero">
      {!! $vines() !!}
      <svg class="km-hero-mandala" id="km-hero-mandala" viewBox="-110 -110 220 220" aria-hidden="true">{!! $mandala(104, 22) !!}</svg>
      <div class="km-hero-art">
        <svg class="km-gate-svg" viewBox="20 50 360 426" aria-hidden="true">
          {!! $heroGate !!}
          <g class="km-pilaster">
            <rect x="60" y="312" width="18" height="146" fill="var(--km-band-fill)"/><rect x="322" y="312" width="18" height="146" fill="var(--km-band-fill)"/>
            <path class="km-ln" pathLength="1" d="M60 312V458M78 312V458M322 312V458M340 312V458"/>
            <path class="km-ln" pathLength="1" d="M40 458H360M48 466H352M34 474H366"/>
          </g>
        </svg>
        <div class="km-hero-photo" id="km-hero-photo">
          @if ($coverUrl)<img src="{{ $coverUrl }}" alt="{{ $coupleTitle }}">@else<div class="km-photo-empty{{ $kh($initials) }}">{{ $initials }}</div>@endif
          <svg class="km-photo-line" viewBox="0 0 200 300" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $archLine }}" vector-effect="non-scaling-stroke"/></svg>
        </div>
      </div>
      <div class="km-hero-text">
        <p class="km-eyebrow km-hero-in" style="--d:0">The Royal Wedding of</p>
        <h2 class="km-hero-names">
          @if ($groomPretty)<span class="km-hero-in{{ $kh($groomPretty) }}" style="--d:1">{{ $groomPretty }}</span>@endif
          @if ($groomPretty && $bridePretty)<svg class="km-amp km-hero-in" style="--d:2" viewBox="-22 -22 44 44" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold)"/></svg>@endif
          @if ($bridePretty)<span class="km-hero-in{{ $kh($bridePretty) }}" style="--d:3">{{ $bridePretty }}</span>@endif
        </h2>
        @if ($dateKhmer)<p class="km-hero-date km-hero-in" style="--d:4">{{ $dateKhmer }}</p>@endif
        @if ($khmerLunarDate)<p class="km-hero-lunar km-hero-in" style="--d:5">{{ $khmerLunarDate }}</p>@endif
        @if ($venueName)<p class="km-hero-venue km-hero-in{{ $kh($venueName) }}" style="--d:6">{{ $venueName }}</p>@endif
      </div>
      <a class="km-scroll" href="#km-invite" aria-label="អានបន្ត" data-sfx="tap"><svg viewBox="-24 -44 48 48" aria-hidden="true"><use href="#km-lotus" fill="currentColor"/></svg></a>
    </section>
    {!! $band !!}

    {{-- ============ 2 · THE INVITATION: formal Khmer wording inside a kbach frame ============ --}}
    <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-invite">
      {!! $vines() !!}
      <article class="km-card km-invite km-r" data-r="unfold">
        {!! $corners() !!}
        <p class="km-eyebrow">Together with their families</p>
        @if ($groomParents || $brideParents)
          <div class="km-parents" data-stagger>
            <div><p class="km-label">មាតាបិតាខាងកូនប្រុស</p>@foreach ($groomParents as $line)<p class="km-parent">{{ $line }}</p>@endforeach</div>
            <svg class="km-parents-orn" viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold)"/></svg>
            <div><p class="km-label">មាតាបិតាខាងកូនស្រី</p>@foreach ($brideParents as $line)<p class="km-parent">{{ $line }}</p>@endforeach</div>
          </div>
        @endif
        <h3 class="km-invite-title">សូមគោរពអញ្ជើញ</h3>

        {{-- the guest's name on a gold plaque hanging from two cords (it swings with scroll speed) --}}
        <div class="km-hang" id="km-hang">
          <span class="km-hang-cords" aria-hidden="true"></span>
          <div class="km-plaque">
            <span class="km-plaque-in"><b class="km-plaque-name{{ $kh($guest) }}">{{ $guest }}</b></span>
          </div>
        </div>

        <p class="km-body km-r" data-r="words">{{ $receptionText }}</p>
        @if ($coupleTitle)
          <div class="km-couple">
            @if ($groomPretty)<div><p class="km-label">កូនប្រុសនាម</p><p class="km-couple-name{{ $kh($groomPretty) }}">{{ $groomPretty }}</p></div>@endif
            @if ($groomPretty && $bridePretty)<svg class="km-couple-orn" viewBox="-37 -44 74 50" aria-hidden="true"><use href="#km-lotus" fill="url(#km-gold)" style="color:var(--km-gold)"/></svg>@endif
            @if ($bridePretty)<div><p class="km-label">កូនស្រីនាម</p><p class="km-couple-name{{ $kh($bridePretty) }}">{{ $bridePretty }}</p></div>@endif
          </div>
        @endif
        @if ($eventDate)
          <div class="km-when">
            <p class="km-when-date">{{ $dateKhmer }}</p>
            @if ($khmerLunarDate)<p class="km-soft">{{ $khmerLunarDate }}</p>@endif
            <p class="km-accent-text">{{ $timeKhmer }}</p>
            @if ($venueName)<p class="km-soft{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          </div>
        @endif
      </article>
    </section>

    {{-- ============ 3 · BRIDE & GROOM: two small temple gates ============ --}}
    @if ($groomPhoto || $bridePhoto)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-couple">
        {!! $vines() !!}
        {!! $head('The bride & groom', 'កូនកំលោះ និងកូនក្រមុំ') !!}
        <div class="km-duo">
          @foreach ([[$groomPhoto, $groomPretty, 'កូនកំលោះ', 'Groom'], [$bridePhoto, $bridePretty, 'កូនក្រមុំ', 'Bride']] as $k => [$photo, $name, $role, $en])
            @if ($photo)
              <figure class="km-duo-item km-r" data-r="{{ $k ? 'door-r' : 'door-l' }}">
                <div class="km-portrait">
                  <svg class="km-gate-svg" viewBox="-10 6 220 294" aria-hidden="true">{!! $portraitGate !!}</svg>
                  <div class="km-portrait-photo"><img src="{{ $photo }}" alt="{{ $name }}" loading="lazy"></div>
                </div>
                <figcaption><span class="km-eyebrow">{{ $en }}</span><span class="km-label">{{ $role }}</span>@if ($name)<span class="km-duo-name{{ $kh($name) }}">{{ $name }}</span>@endif</figcaption>
              </figure>
            @endif
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 4 · A WORD FROM US: a gold-edged cartouche ============ --}}
    @if ($personalNote !== '')
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-note">
        {!! $vines() !!}
        <div class="km-cartouche km-r" data-r="wipe">
          <svg class="km-cart-lotus" viewBox="-40 -44 80 50" aria-hidden="true"><use href="#km-lotus" fill="url(#km-gold)" style="color:var(--km-gold)"/></svg>
          <p class="km-quote km-r" data-r="words">{{ $personalNote }}</p>
          @if ($coupleTitle)<p class="km-sign">— <span class="{{ trim($kh($coupleTitle)) }}">{{ $coupleTitle }}</span></p>@endif
        </div>
      </section>
    @endif

    {{-- ============ 5 · THE AUSPICIOUS DAY: Khmer lunar date + countdown in kbach medallions ============ --}}
    @if ($eventDate)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-date">
        {!! $vines() !!}
        {!! $head('The auspicious day', 'ថ្ងៃមង្គល') !!}
        <div class="km-day km-r" data-r="unfold">
          {!! $corners() !!}
          @if ($khmerLunarDate)
            <p class="km-day-lunar">{{ $khmerLunarDate }}</p>
            {!! $divider('sm') !!}
          @endif
          <div class="km-day-solar">
            <span class="km-day-side">{{ $khDays[$eventDate->dayOfWeek] }}</span>
            <span class="km-day-num">
              <svg viewBox="-60 -60 120 120" aria-hidden="true">{!! $mandala(56, 16) !!}</svg>
              <b>{{ $khDigits($eventDate->day) }}</b>
            </span>
            <span class="km-day-side">ខែ{{ $khMonths[$eventDate->month - 1] }}<br>{{ $khDigits($eventDate->year) }}</span>
          </div>
          <p class="km-day-en">{{ $enMonths[$eventDate->month - 1] }} {{ $eventDate->day }}, {{ $eventDate->year }}</p>
          <p class="km-accent-text">{{ $timeKhmer }}</p>
        </div>

        @if ($showCountdown)
          <div class="km-count km-r" data-r="rise" id="km-count" data-target="{{ $eventDate->toIso8601String() }}" role="timer" aria-live="off">
            @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $label)
              <div class="km-medal" style="--i:{{ $loop->index }}">
                <span class="km-medal-ring" aria-hidden="true"><svg viewBox="-50 -50 100 100">@for ($k = 0; $k < 12; $k++)<g transform="rotate({{ $k * 30 }}) translate(0 -42)"><use href="#km-phni" fill="url(#km-gold-s)" transform="scale(.32)"/></g>@endfor<circle r="36" fill="none" stroke="url(#km-gold-s)" stroke-width="1.6"/><circle r="32" fill="none" stroke="url(#km-gold-s)" stroke-width=".6"/></svg></span>
                <b data-u="{{ $u }}">០០</b>
                <span class="km-medal-label">{{ $label }}</span>
              </div>
            @endforeach
          </div>
          <p class="km-count-done" id="km-count-done" hidden>ថ្ងៃនេះហើយ — សូមស្វាគមន៍!</p>
        @endif
      </section>
    @endif

    {{-- ============ 6 · OUR STORY: palm-leaf manuscript (sleuk rith) strips; tap turns a leaf over ============ --}}
    @if ($story->isNotEmpty())
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-story">
        {!! $vines() !!}
        {!! $head('Our story', 'រឿងរ៉ាវស្នេហារបស់យើង') !!}
        <p class="km-hint km-r" data-r="rise">ចុចលើស្លឹករឹត ដើម្បីបង្វិលមើលរូប</p>
        <div class="km-leaves-wrap" id="km-leaves">
        <svg class="km-cord" preserveAspectRatio="none" viewBox="0 0 10 100" aria-hidden="true"><path d="M5 0V100" pathLength="1"/></svg>
        <ol class="km-leaves">
          @foreach ($story as $i => $c)
            @php([$place, $when] = $splitTitle($c['title'] ?? ''))
            <li class="km-leaf-wrap km-r" data-r="{{ $i % 2 ? 'unroll-r' : 'unroll' }}" style="--i:{{ $i }}">
              <button type="button" class="km-leaf" aria-label="{{ $place ?: 'រឿងទី '.$khDigits($i + 1) }}">
                <span class="km-leaf-in">
                  <span class="km-leaf-face front">
                    <span class="km-leaf-hole a" aria-hidden="true"></span><span class="km-leaf-hole b" aria-hidden="true"></span>
                    <span class="km-leaf-num">{{ $khDigits($i + 1) }}</span>
                    <span class="km-leaf-text">
                      @if ($place !== '')<span class="km-leaf-title">{{ $place }}</span>@endif
                      @if ($when !== '')<span class="km-leaf-when">{{ $when }}</span>@endif
                      @if (! empty($c['text']))<span class="km-leaf-body">{{ $c['text'] }}</span>@endif
                    </span>
                  </span>
                  <span class="km-leaf-face back">
                    @if ($gallery->isNotEmpty())
                      <img src="{{ $gallery[($i + 1) % $gallery->count()] }}" alt="" loading="lazy">
                    @endif
                    <span class="km-leaf-cap">{{ $place ?: $khDigits($i + 1) }}</span>
                  </span>
                </span>
              </button>
            </li>
          @endforeach
        </ol>
        </div>
      </section>
    @endif

    {{-- ============ 7 · PROGRAMME: a gold line drawing down, a lotus opening at each rite ============ --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-program">
        {!! $vines() !!}
        {!! $head('Order of ceremony', 'កម្មវិធីមង្គលការ') !!}
        @if ($scheduleImg)
          <figure class="km-card km-sched-img km-r" data-r="unfold">
            {!! $corners() !!}
            <button type="button" class="km-ph" data-full="{{ $scheduleImg }}" data-cap="កម្មវិធីមង្គលការ"><img src="{{ $scheduleImg }}" alt="កម្មវិធីមង្គលការ" loading="lazy"></button>
          </figure>
        @else
          <div class="km-rites-wrap" id="km-rites">
          <span class="km-rites-line" aria-hidden="true"><i></i></span>
          <ol class="km-rites">
            @foreach ($schedule as $i => $row)
              <li class="km-rite km-r" data-r="{{ $i % 2 ? 'slide-r' : 'slide-l' }}">
                <span class="km-rite-lotus" aria-hidden="true">
                  <svg viewBox="-40 -46 80 52"><use href="#km-lotus" fill="url(#km-gold-s)" style="color:var(--km-gold)"/></svg>
                </span>
                <span class="km-rite-card">
                  <span class="t">{{ $schedTime($row['time'] ?? '') }}</span>
                  <span class="l{{ $kh($row['label'] ?? '') }}">{{ $row['label'] ?? '' }}</span>
                </span>
              </li>
            @endforeach
          </ol>
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 8 · GALLERY: carved gold frames, tap = full preview ============ --}}
    @if ($gallery->isNotEmpty())
      <section class="km-sec km-wide {{ $tone() }} km-r" data-r="sec" id="km-gallery">
        {!! $vines() !!}
        {!! $head('Treasured moments', 'កម្រងរូបអនុស្សាវរីយ៍') !!}
        <div class="km-gal">
          @foreach ($gallery as $i => $url)
            @php($shape = $i % 5 === 0 || ($loop->last && $gallery->filter(fn ($u, $k) => $k % 5 !== 0)->count() % 2) ? 'wide' : ($i % 5 === 2 || $i % 5 === 3 ? 'arch' : 'tall'))
            <figure class="km-frame {{ $shape }} km-r" data-r="frame" style="--i:{{ $i % 3 }}">
              <button type="button" class="km-ph" data-full="{{ $url }}" data-cap="{{ $captions[$i] ?? '' }}" aria-label="{{ ($captions[$i] ?? '') ?: 'រូបភាពទី '.$khDigits($i + 1) }}">
                <img src="{{ $url }}" alt="{{ $captions[$i] ?? '' }}" loading="{{ $i < 3 ? 'eager' : 'lazy' }}" draggable="false">
                @if ($shape === 'arch')
                  <svg class="km-photo-line" viewBox="0 0 200 300" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $archLine }}" vector-effect="non-scaling-stroke"/></svg>
                @endif
              </button>
              @if ($shape !== 'arch'){!! $corners('sm') !!}@endif
              @if (($captions[$i] ?? '') !== '')<figcaption>{{ $captions[$i] }}</figcaption>@endif
            </figure>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 9 · VENUE ============ --}}
    @if ($showVenue)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-venue">
        {!! $vines() !!}
        {!! $head('The venue', 'ទីកន្លែងប្រារព្ធពិធី') !!}
        <div class="km-card km-venue km-r" data-r="unfold">
          {!! $corners() !!}
          <svg class="km-venue-pin" viewBox="-37 -44 74 50" aria-hidden="true"><use href="#km-lotus" fill="url(#km-gold)" style="color:var(--km-gold)"/></svg>
          @if ($venueName)<p class="km-venue-name{{ $kh($venueName) }}">{{ $venueName }}</p>@endif
          @if ($venueAddress)<p class="km-body">{{ $venueAddress }}</p>@endif
          @if ($timeKhmer)<p class="km-accent-text">{{ $timeKhmer }}</p>@endif
          @if ($mapEmbed)
            <div class="km-map"><iframe data-src="{{ $mapEmbed }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="ផែនទី"></iframe></div>
          @endif
          @if ($mapUrl)
            <a class="km-btn" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
              មើលផ្លូវ Google Maps
            </a>
          @endif
        </div>
      </section>
    @endif

    {{-- ============ 10 · GUEST NOTES: dress code as silk (sampot) swatches + notes ============ --}}
    @if ($showNotes)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-notes">
        {!! $vines() !!}
        {!! $head('For our honoured guests', 'ព័ត៌មានសម្រាប់ភ្ញៀវកិត្តិយស') !!}
        @if ($dressCode !== '')
          <div class="km-card km-dress km-r" data-r="unfold">
            {!! $corners() !!}
            <p class="km-label">ពណ៌សម្លៀកបំពាក់</p>
            <p class="km-eyebrow">Dress code</p>
            @if ($dressColours)
              <div class="km-rod" aria-hidden="true"><i></i></div>
              <ul class="km-silks" aria-label="ពណ៌សម្លៀកបំពាក់">
                @foreach ($dressColours as $i => $c)
                  <li style="--c: {{ $c['hex'] }}; --i: {{ $i }}">
                    <button type="button" class="km-silk" aria-label="{{ $c['name'] }}"><span class="km-silk-cloth" aria-hidden="true"></span></button>
                    <span class="km-silk-name{{ $kh($c['name']) }}">{{ $c['name'] }}</span>
                  </li>
                @endforeach
              </ul>
            @endif
            <p class="km-body">{{ $dressCode }}</p>
          </div>
        @endif
        @if ($whatToBring !== '' || $rsvpBy)
          <div class="km-notes" data-stagger>
            @if ($whatToBring !== '')
              <div class="km-note km-r" data-r="rise"><svg viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold-s)"/></svg><p class="km-label">សូមយកមកជាមួយ</p><p class="km-eyebrow">What to bring</p><p class="km-body">{{ $whatToBring }}</p></div>
            @endif
            @if ($rsvpBy)
              <div class="km-note km-r" data-r="rise"><svg viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold-s)"/></svg><p class="km-label">សូមឆ្លើយតបមុន</p><p class="km-eyebrow">Kindly reply by</p><p class="km-body">{{ $rsvpBy }}</p></div>
            @endif
          </div>
        @endif
      </section>
    @endif

    {{-- ============ 11 · RSVP: the reply is sealed in a gold envelope ============ --}}
    @if ($showRsvp)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-rsvp">
        {!! $vines() !!}
        {!! $head('Kindly reply', 'សូមបញ្ជាក់ការចូលរួម') !!}
        <div class="km-rsvp-stage">
          <div class="km-card km-rsvp km-r" data-r="unfold" id="km-rsvp-card">
            {!! $corners() !!}
            <p class="km-body">{{ $rsvpQuestion }}</p>
            @if ($rsvpBy)<p class="km-soft">សូមឆ្លើយតបមុន {{ $rsvpBy }}</p>@endif
            <div class="km-rsvp-opts" id="km-rsvp-opts" data-url="{{ $rsvpUrl }}" role="radiogroup" aria-label="ការឆ្លើយតប">
              <button type="button" class="km-opt" data-status="yes" role="radio" aria-checked="false"><svg viewBox="-37 -44 74 50" aria-hidden="true"><use href="#km-lotus" fill="currentColor"/></svg>ខ្ញុំនឹងចូលរួម</button>
              <button type="button" class="km-opt" data-status="no" role="radio" aria-checked="false"><svg viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="currentColor"/></svg>សោកស្ដាយ មិនអាចចូលរួមបាន</button>
            </div>
            <form class="km-rsvp-form" id="km-rsvp-form">
              <label class="km-sr" for="km-rsvp-note">សារជូនពរ</label>
              <textarea id="km-rsvp-note" rows="3" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea>
              <button type="submit" class="km-btn">បិទស្រោមសំបុត្រ ហើយផ្ញើ</button>
            </form>
            <p class="km-err" id="km-rsvp-err" role="alert"></p>
          </div>
          <div class="km-env" id="km-env" aria-hidden="true">
            <span class="km-env-back"></span><span class="km-env-paper"></span><span class="km-env-front"></span><span class="km-env-flap"></span>
            <span class="km-env-seal"><svg viewBox="-24 -24 48 48"><use href="#km-pkachan" fill="#5a0f14"/></svg></span>
          </div>
          <div class="km-card km-rsvp-done" id="km-rsvp-done" hidden role="status">
            {!! $corners() !!}
            <svg class="km-done-lotus" viewBox="-37 -44 74 50" aria-hidden="true"><use href="#km-lotus" fill="url(#km-gold)" style="color:var(--km-gold)"/></svg>
            <p class="km-script" id="km-rsvp-done-title">Thank you</p>
            <p class="km-body" id="km-rsvp-done-text"></p>
            <button type="button" class="km-link" id="km-rsvp-again">កែប្រែការឆ្លើយតប</button>
          </div>
        </div>
      </section>
    @endif

    {{-- ============ 12 · GIFT: a crimson-and-gold envelope (sraom chorng dai) that opens to the QR ============ --}}
    @if ($giftQr)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-gift-sec">
        {!! $vines() !!}
        {!! $head('Blessings', 'ចំណងដៃ') !!}
        <p class="km-body km-gift-msg km-r" data-r="rise">{{ $giftMessage }}</p>
        <div class="km-gift km-r" data-r="rise" id="km-gift">
          <div class="km-gift-card">
            <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
            @if ($giftAccount)<p class="km-gift-acc">{{ $giftAccount }}</p>@endif
          </div>
          <button type="button" class="km-gift-env" id="km-gift-open" aria-label="បើកស្រោមសំបុត្រចំណងដៃ">
            <span class="km-gift-back" aria-hidden="true"></span>
            <span class="km-gift-front" aria-hidden="true"><svg viewBox="-37 -44 74 50"><use href="#km-lotus" fill="url(#km-gold-s)" style="color:var(--km-gold)"/></svg></span>
            <span class="km-gift-flap" aria-hidden="true"></span>
            <span class="km-gift-seal" aria-hidden="true"><svg viewBox="-24 -24 48 48"><use href="#km-pkachan" fill="#5a0f14"/></svg></span>
            <span class="km-gift-hint">ចុចដើម្បីបើក</span>
          </button>
        </div>
        <div class="km-gift-btns" id="km-gift-btns">
          @if ($giftNumber)<button type="button" class="km-btn" id="km-copy" data-copy="{{ $giftNumber }}">ចម្លងលេខគណនី</button>@endif
          <a class="km-btn ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
        </div>
      </section>
    @endif

    {{-- ============ 13 · CONTACT + CUSTOM BUTTON ============ --}}
    @if ($contacts->isNotEmpty() || $showCta)
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-contact">
        {!! $vines() !!}
        {!! $head('Contact us', 'ទំនាក់ទំនង') !!}
        <div class="km-contacts" data-stagger>
          @foreach ($contacts as $i => $c)
            <a class="km-contact km-r" data-r="rise" href="tel:{{ $tel($c['phone']) }}" data-sfx="tap">
              <span class="ico" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></span>
              <span class="r">{{ $c['role'] }}</span>
              @if ($c['name'] !== '')<span class="n{{ $kh($c['name']) }}">{{ $c['name'] }}</span>@endif
              <span class="p">{{ $c['phone'] }}</span>
            </a>
          @endforeach
        </div>
        @if ($showCta)
          <a class="km-btn km-cta km-r" data-r="rise" href="{{ $ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $ctaLabel }}</a>
        @endif
      </section>
    @endif

    {{-- ============ 14 · THANKS / APOLOGY ============ --}}
    @if ($letterRows->isNotEmpty())
      <section class="km-sec {{ $tone() }} km-r" data-r="sec" id="km-letters">
        {!! $vines() !!}
        <div class="km-letters">
          @foreach ($letterRows as [$khHead, $en, $text])
            <article class="km-card km-letter km-r" data-r="{{ $loop->index ? 'door-r' : 'door-l' }}">
              {!! $corners() !!}
              <p class="km-eyebrow">{{ $en }}</p>
              <h3 class="km-letter-title">{{ $khHead }}</h3>
              {!! $divider('sm') !!}
              <p class="km-letter-text">{{ $text }}</p>
            </article>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ============ 15 · FINALE: a great gold lotus blooms ============ --}}
    {!! $band !!}
    <section class="km-finale km-dark km-sec km-r" data-r="sec" id="km-finale">
      @if ($finaleBg)<div class="km-finale-bg" aria-hidden="true"><img src="{{ $finaleBg }}" alt="" loading="lazy"></div>@endif
      {!! $vines() !!}
      <button type="button" class="km-bloom" id="km-bloom" aria-label="ផ្កាឈូកមាស">
        <svg viewBox="-120 -150 240 170" aria-hidden="true">
          <g class="km-bloom-glow"><circle cx="0" cy="-60" r="80"/></g>
          @foreach ([[9, 64, 'km-gold-s', .78], [7, 50, 'km-gold', .9], [5, 34, 'km-gold', 1]] as $row => [$cnt, $spread, $fill, $sc])
            <g class="km-bloom-row r{{ $row }}" transform="scale({{ $sc }})">
              @for ($k = 0; $k < $cnt; $k++)
                @php($a = ($k - ($cnt - 1) / 2) * ($spread * 2 / max(1, $cnt - 1)))
                <path class="km-bloom-petal" style="--a:{{ round($a, 1) }}deg; --k:{{ abs($k - ($cnt - 1) / 2) }}" fill="url(#{{ $fill }})" d="M0 0C-17-14-21-44 0-92C21-44 17-14 0 0Z"/>
              @endfor
            </g>
          @endforeach
          <path class="km-bloom-base" d="M-70 2Q0 22 70 2" fill="none" stroke="url(#km-gold-s)" stroke-width="3" stroke-linecap="round"/>
        </svg>
      </button>
      <p class="km-finale-line{{ $kh($closingLine) }} km-r" data-r="rise">{{ $closingLine }}</p>
      <p class="km-body km-r" data-r="rise">វត្តមានរបស់លោកអ្នក គឺជាកិត្តិយស និងជាពរជ័យដ៏ធំធេងបំផុត សម្រាប់ថ្ងៃមង្គលរបស់យើងខ្ញុំ។</p>
      @if ($coupleTitle)<p class="km-finale-names km-r{{ $kh($coupleTitle) }}" data-r="rise">{{ $coupleTitle }}</p>@endif
      <button type="button" class="km-btn km-throw" id="km-throw">
        <svg viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="currentColor"/></svg>
        បាចផ្កាឈូកមាសជូនពរ
      </button>
    </section>

    <x-invitation-footer :names="$coupleTitle" :names-class="$isKh($coupleTitle) ? 'km-kh' : ''" :date="$dateNumeric"
      class="km-footer"
      style="--inv-footer-bg:#2a0508; --inv-footer-text:#f6ead2; --inv-footer-muted:#d8bf8a; --inv-footer-accent:{{ $accent }}; --inv-footer-ring:rgba(201,161,74,.35); --inv-footer-names-font:'Cinzel','Moul',serif; --inv-footer-caps-font:'Cormorant Garamond','Noto Serif Khmer',serif; --inv-footer-kh-font:'Kantumruy Pro',sans-serif;">
      <x-slot:ornament><svg viewBox="-24 -24 48 48" aria-hidden="true"><use href="#km-pkachan" fill="url(#km-gold)"/></svg></x-slot:ornament>
    </x-invitation-footer>
  </main>

  {{-- full photo preview: zooms from the tapped photo, prev / next, swipe, ← → Esc --}}
  <div class="km-lb" id="km-lb" hidden role="dialog" aria-modal="true" aria-label="កម្រងរូបភាព">
    <div class="km-lb-bg" aria-hidden="true"></div>
    <div class="km-lb-stage" id="km-lb-stage"><img class="km-lb-img" id="km-lb-img" alt=""></div>
    <p class="km-lb-count" id="km-lb-count"></p>
    <p class="km-lb-cap" id="km-lb-cap"></p>
    <button type="button" class="km-lb-btn x" id="km-lb-x" aria-label="បិទ">&times;</button>
    <button type="button" class="km-lb-btn prev" id="km-lb-prev" aria-label="រូបមុន">&#8249;</button>
    <button type="button" class="km-lb-btn next" id="km-lb-next" aria-label="រូបបន្ទាប់">&#8250;</button>
  </div>
  <div class="km-toast" id="km-toast" role="status" aria-live="polite"></div>

  {{-- music + one sound button for everything --}}
  @if ($musicFile)<audio id="km-audio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
  <div class="km-sound-wrap">
    @if ($songTitle !== '' && ($musicFile || $youtubeId))
      <p class="km-now" id="km-now"><span class="disc" aria-hidden="true"></span><span class="t{{ $kh($songTitle) }}">{{ $songTitle }}</span></p>
    @endif
    @if ($musicUrl && ! $musicFile && ! $youtubeId)
      <a class="km-sound" href="{{ $musicUrl }}" target="_blank" rel="noopener noreferrer" aria-label="ស្ដាប់បទចម្រៀង"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></a>
    @else
      <button type="button" class="km-sound" id="km-sound" aria-pressed="false" aria-label="បិទសំឡេង">
        <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/><path d="M3 3l18 18"/></svg>
      </button>
    @endif
  </div>

  <script type="application/json" id="km-config">{!! json_encode([
      'youtube' => $youtubeId,
      'sfx' => [
          'tap' => $sfx('pop.mp3'),
          'leaf' => $sfx('papercrackle1.mp3'),
          'open' => $sfx('reveal.mp3'),
          'chime' => $sfx('chime.mp3'),
          'card' => $sfx('card.mp3'),
          'send' => $sfx('envelope.mp3'),
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
</div>

<script src="{{ $kmAsset('js/invitations/kbach-meas.js') }}"></script>
