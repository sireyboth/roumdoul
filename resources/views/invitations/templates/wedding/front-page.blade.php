@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    // ------------------------------------------------------------------------------------
    // Front Page — a modern editorial wedding invitation: the couple's own "wedding issue".
    // Cream paper + ink with one accent colour, a magazine cover, giant date numerals, a
    // marquee band, photo-booth strips that "print" into view and a contents-page program.
    //
    // Everything personal comes from $fields (catalog keys, docs/invitation-templates.md):
    //   groom_name / bride_name / couple_initials → cover masthead, invitation, strips, closing
    //   groom_father/mother, bride_father/mother  → the byline above «សូមគោរពអញ្ជើញ» (លោក / លោកស្រី added)
    //   recipient name (the guest link)           → cover line + the invitation (default: the formal greeting)
    //   reception_message → invitation wording    message → the pull quote
    //   event_date → masthead issue line, giant numerals (scratch to reveal), Khmer date + time, countdown
    //   khmer_date (premium) → lunar date line    countdown_enabled (premium) → live countdown
    //   cover_image → cover photo (default: first gallery photo) and the story's feature photo
    //   photo_gallery (premium) + photo_captions → photo-booth strips (4 frames each, up to 4 strips) + preview
    //   story_chapters (premium) → «The story so far» numbered chapters
    //   event_schedule (premium) or schedule_image → «Contents» program
    //   venue_name, venue_address (premium), map_url → location + map + directions   dress_code → line under it
    //   rsvp_enabled (premium) + rsvp_message → RSVP (posts to the invitation RSVP endpoint)
    //   qr_code (premium) + gift_account + gift_message → wedding gift card
    //   contact_phone, groom_phone, bride_phone → contacts     thanks_message / apology_message → letters
    //   music_file / music_url → background music (autoplays when allowed, else on the first tap)
    //   accent_color → the one accent (default vermilion)
    // On demo pages $invitation is null → everything unlocked.
    // Styles: public/css/invitations/front-page.css   Behaviour: public/js/invitations/front-page.js
    // Sounds: images/invitations/assets/sfx/* (only after the first tap; the sound button mutes all).
    // ------------------------------------------------------------------------------------
    $isUnlocked = fn (string $key) => $invitation ? $invitation->fieldUnlocked($key) : true;
    $resolveUrl = fn (string $path) => preg_match('#^(https?:)?//#i', $path) ? $path : Storage::disk('s3')->url($path);
    $text = fn (string $key) => trim((string) ($fields[$key] ?? ''));
    $lines = fn (string $key) => collect(preg_split('/\R/', $text($key)))->map(fn ($l) => trim($l))->filter()->values()->all();
    $fpAsset = fn (string $file) => asset($file).'?v='.@filemtime(public_path($file));
    $sfx = fn (string $file) => $fpAsset('images/invitations/assets/sfx/'.$file);
    $isKh = fn (?string $s) => (bool) preg_match('/\p{Khmer}/u', (string) $s);

    $khDigits = fn ($v) => strtr((string) $v, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']);
    $khMonths = ['មករា', 'កុម្ភៈ', 'មីនា', 'មេសា', 'ឧសភា', 'មិថុនា', 'កក្កដា', 'សីហា', 'កញ្ញា', 'តុលា', 'វិច្ឆិកា', 'ធ្នូ'];
    $khDays = ['អាទិត្យ', 'ច័ន្ទ', 'អង្គារ', 'ពុធ', 'ព្រហស្បតិ៍', 'សុក្រ', 'សៅរ៍'];

    // ---- the couple ----
    $groom = $text('groom_name') ?: 'កូនប្រុស';
    $bride = $text('bride_name') ?: 'កូនស្រី';
    $groomUp = mb_strtoupper($groom);
    $brideUp = mb_strtoupper($bride);
    $couple = $groomUp.' & '.$brideUp;
    $initial = fn (string $n) => mb_strtoupper(mb_substr($n, 0, 1));
    $initials = $text('couple_initials') ?: $initial($groom).'&'.$initial($bride);

    // ---- the guest ----
    $guest = trim((string) ($recipientName ?? ''));
    $guestLine = $guest !== '' ? $guest : 'ឯកឧត្តម លោកជំទាវ លោក លោកស្រី អ្នកនាង កញ្ញា';

    // ---- the date ----
    $date = null;
    if ($text('event_date') !== '') {
        try {
            $date = Carbon::parse($text('event_date'));
        } catch (\Throwable) {
            $date = null;
        }
    }
    $dateKh = $date ? 'ថ្ងៃ'.$khDays[$date->dayOfWeek].' ទី'.$khDigits($date->day).' ខែ'.$khMonths[$date->month - 1].' ឆ្នាំ'.$khDigits($date->year) : null;
    $timeKh = $date ? 'វេលាម៉ោង '.$khDigits($date->format('g:i')).' '.($date->hour < 12 ? 'ព្រឹក' : ($date->hour < 17 ? 'រសៀល' : 'ល្ងាច')) : null;
    $dateDots = $date ? $date->format('d.m.Y') : null;
    $issueLine = $date ? 'ខែ'.$khMonths[$date->month - 1].' '.$khDigits($date->year) : 'ពិធីមង្គលការ';
    $lunar = ($isUnlocked('khmer_date') && $text('khmer_date') !== '') ? $text('khmer_date') : null;
    $showCountdown = $date && $date->isFuture() && ! empty($fields['countdown_enabled']) && $isUnlocked('countdown_enabled');

    // ---- parents (byline) ----
    $parent = function (string $key, string $title) use ($text): ?string {
        $n = $text($key);

        return $n === '' ? null : (str_starts_with($n, 'លោក') ? $n : $title.' '.$n);
    };
    $groomParents = array_values(array_filter([$parent('groom_father', 'លោក'), $parent('groom_mother', 'លោកស្រី')]));
    $brideParents = array_values(array_filter([$parent('bride_father', 'លោក'), $parent('bride_mother', 'លោកស្រី')]));

    $reception = $text('reception_message') ?: 'អញ្ជើញចូលរួមជាអធិបតី និងជាភ្ញៀវកិត្តិយស ដើម្បីប្រសិទ្ធិពរជ័យ សិរីសួស្តី ជ័យមង្គល ក្នុងពិធីអាពាហ៍ពិពាហ៍ កូនប្រុស-កូនស្រី របស់យើងខ្ញុំ ដោយមេត្រីភាព។';
    $quote = $text('message') ?: 'ពីថ្ងៃនេះតទៅ យើងនឹងសរសេររឿងរ៉ាវតែមួយជាមួយគ្នា ហើយទំព័រដំបូងនៃរឿងនោះ យើងចង់ឲ្យមានវត្តមានលោកអ្នក។';

    // ---- photos ----
    $gallery = collect($isUnlocked('photo_gallery') ? ($fields['photo_gallery'] ?? []) : [])
        ->filter(fn ($p) => is_string($p) && trim($p) !== '')
        ->map(fn ($p) => $resolveUrl(trim($p)))->values();
    $cover = $text('cover_image') !== '' ? $resolveUrl($text('cover_image')) : $gallery->first();
    $captions = $lines('photo_captions');
    $shots = $gallery->map(fn ($src, $i) => ['src' => $src, 'cap' => $captions[$i] ?? ''])->take(16)->values();
    // photo-booth strips: 4 frames each; a short last strip borrows from the start so every strip is full
    $strips = [];
    if ($shots->isNotEmpty()) {
        $count = max(1, (int) ceil($shots->count() / 4));
        for ($s = 0; $s < $count; $s++) {
            $strips[] = collect(range(0, 3))->map(fn ($k) => $shots[($s * 4 + $k) % $shots->count()] + ['n' => ($s * 4 + $k) % $shots->count() + 1])->all();
        }
    }
    $featurePhoto = $gallery->count() > 1 ? $gallery[1] : $cover;

    // ---- story ----
    $story = collect($isUnlocked('story_chapters') ? ($fields['story_chapters'] ?? []) : [])
        ->filter(fn ($c) => is_array($c) && trim(($c['title'] ?? '').($c['text'] ?? '')) !== '')
        ->take(8)->values();

    // ---- program ----
    $schedule = collect($isUnlocked('event_schedule') ? ($fields['event_schedule'] ?? []) : [])
        ->filter(fn ($r) => is_array($r) && trim(($r['time'] ?? '').($r['label'] ?? '')) !== '')->values();
    $scheduleImg = ($isUnlocked('schedule_image') && $text('schedule_image') !== '') ? $resolveUrl($text('schedule_image')) : null;
    // "06:00 AM" → ["06:00", "ព្រឹក"]; anything else shows as typed
    $schedTime = function (?string $t) use ($khDigits): array {
        $t = trim((string) $t);
        if (! preg_match('/^(\d{1,2})[:.](\d{2})\s*(?:([ap])\.?\s*m\.?)?$/i', $t, $m)) {
            return [$t, ''];
        }
        $h = (int) $m[1];
        if (! empty($m[3])) {
            $h = $h % 12 + (strtolower($m[3]) === 'p' ? 12 : 0);
        }

        return [sprintf('%02d:%s', $h % 12 ?: 12, $m[2]), $h < 12 ? 'ព្រឹក' : ($h === 12 ? 'ថ្ងៃត្រង់' : ($h < 17 ? 'រសៀល' : 'ល្ងាច'))];
    };

    // ---- venue + map (a pasted Google Maps link pins the spot; else search the address) ----
    $venueName = $text('venue_name');
    $venueAddress = $isUnlocked('venue_address') ? $text('venue_address') : '';
    $mapLink = ($isUnlocked('map_url') && preg_match('#^https?://#i', $text('map_url'))) ? $text('map_url') : null;
    if ($venueAddress !== '' && preg_match('#^https?://\S+$#i', $venueAddress)) {
        $mapLink = $mapLink ?: $venueAddress;
        $venueAddress = '';
    }
    $mapQuery = $venueAddress ?: $venueName;
    $mapPinned = false;
    if ($mapLink && preg_match('#[@=](-?\d{1,3}\.\d+),\s*(-?\d{1,3}\.\d+)#', urldecode($mapLink), $pin)) {
        $mapQuery = $pin[1].','.$pin[2];
        $mapPinned = true;
    }
    $mapUrl = $mapLink ?: ($mapQuery ? 'https://maps.google.com/?q='.urlencode($mapQuery) : null);
    $mapEmbed = ($mapPinned || $venueAddress !== '') && $mapQuery ? 'https://www.google.com/maps?q='.urlencode($mapQuery).'&z=16&output=embed' : null;
    $dressCode = $isUnlocked('dress_code') ? $text('dress_code') : '';

    // ---- RSVP, gift, contacts, letters ----
    $showRsvp = ! empty($fields['rsvp_enabled']) && $isUnlocked('rsvp_enabled');
    $rsvpUrl = ($invitation && ($recipient ?? null)) ? route('invitation.rsvp', $recipient) : null;
    $rsvpAsk = $text('rsvp_message') ?: 'វត្តមានរបស់លោកអ្នក នឹងធ្វើឲ្យថ្ងៃរបស់យើងកាន់តែពេញលេញ។ តើលោកអ្នកនឹងអញ្ជើញចូលរួមដែរឬទេ?';
    $giftQr = ($isUnlocked('qr_code') && $text('qr_code') !== '') ? $resolveUrl($text('qr_code')) : null;
    $giftAccount = $giftQr ? $text('gift_account') : '';
    $giftText = $text('gift_message') ?: 'សម្រាប់ភ្ញៀវកិត្តិយសដែលមិនអាចអញ្ជើញមកដល់ ឬចង់ផ្ញើចំណងដៃពីចម្ងាយ សូមស្កេន QR នេះ។ សូមអរគុណ!';
    $contacts = collect([
        ['role' => 'ខាងកូនប្រុស', 'name' => $groomUp, 'phone' => $text('groom_phone')],
        ['role' => 'ខាងកូនស្រី', 'name' => $brideUp, 'phone' => $text('bride_phone')],
        ['role' => 'ព័ត៌មានបន្ថែម', 'name' => '', 'phone' => $text('contact_phone')],
    ])->filter(fn ($c) => $c['phone'] !== '')->values();
    $letters = collect([
        ['សេចក្តីថ្លែងអំណរគុណ', 'With gratitude', $text('thanks_message')],
        ['លិខិតសូមអភ័យទោស', 'A note of apology', $text('apology_message')],
    ])->filter(fn ($l) => $l[2] !== '')->values();

    // ---- music: an uploaded song wins over a link ----
    $musicFile = (! empty($fields['music_file']) && $isUnlocked('music_file')) ? $resolveUrl($fields['music_file']) : null;
    $musicUrl = $isUnlocked('music_url') ? $text('music_url') : '';
    $youtubeId = null;
    if (! $musicFile && $musicUrl !== '') {
        if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/', $musicUrl, $m)) {
            $youtubeId = $m[1];
        } elseif (preg_match('#^https?://\S+\.(mp3|m4a|aac|ogg|wav)(\?\S*)?$#i', $musicUrl)) {
            $musicFile = $musicUrl;
        }
    }
    $hasMusic = $musicFile || $youtubeId;

    $accent = preg_match('/^#[0-9a-f]{6}$/i', $text('accent_color')) ? $text('accent_color') : '#c2412d';

    // a barcode drawn from the date digits (pure decoration)
    $barcodeDigits = $date ? $date->format('dmY') : '00000000';
    $bars = '';
    $bx = 0;
    foreach (str_split($barcodeDigits.strrev($barcodeDigits)) as $i => $d) {
        foreach ([1 + ((int) $d % 3), 1 + (((int) $d + $i) % 2)] as $k => $w) {
            if ($k === 0) {
                $bars .= '<rect x="'.$bx.'" y="0" width="'.$w.'" height="40"/>';
            }
            $bx += $w + 1;
        }
    }
    $barW = $bx;

    // the running header's page numbers: «Nº 02» …
    $pg = 1;
    $page = function () use (&$pg) { $pg++; return sprintf('%02d', $pg); };

    $config = [
        'music' => ['file' => $musicFile, 'youtube' => $youtubeId],
        'countdown' => $showCountdown ? $date->toIso8601String() : null,
        'sfx' => [
            'tap' => $sfx('pop.mp3'),
            'open' => $sfx('reveal.mp3'),
            'photo' => $sfx('card.mp3'),
            'paper' => $sfx('papercrackle1.mp3'),
            'chime' => $sfx('chime.mp3'),
            'tape' => $sfx('cassette.mp3'),
        ],
    ];
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Archivo:wdth,wght@62..125,300..800&family=Moul&family=Noto+Serif+Khmer:wght@400;600;700&family=Kantumruy+Pro:wght@300..700&display=swap">
<link rel="stylesheet" href="{{ $fpAsset('css/invitations/front-page.css') }}">

<div class="fp" id="fpTop" style="--fp-accent: {{ $accent }};">

  {{-- running header: the issue line + the page you're on (appears once the cover is scrolled past) --}}
  <header class="fp-run" id="fpRun" aria-hidden="true">
    <span class="fp-run-l">THE WEDDING ISSUE</span>
    <span class="fp-run-c{{ $isKh($couple) ? ' fp-kh' : '' }}">{{ $couple }}</span>
    <span class="fp-run-r">Nº <b id="fpRunNo">01</b></span>
  </header>

  {{-- ─────────── 01 · the cover ─────────── --}}
  <section class="fp-cover" id="fpCover" data-page="01">
    @if ($cover)
      <figure class="fp-cover-ph">
        <img src="{{ $cover }}" alt="{{ $couple }}" fetchpriority="high" decoding="async">
      </figure>
    @endif

    <div class="fp-cover-type">
      <p class="fp-mast-row fp-in" style="--d:.1s">
        <span>Nº 01</span><span class="fp-kht">{{ $issueLine }}</span><span>THE WEDDING ISSUE</span>
      </p>

      <h1 class="fp-mast" aria-label="{{ $groom }} និង {{ $bride }}">
        <span class="fp-mask"><span class="fp-mast-name{{ $isKh($groom) ? ' fp-kh' : '' }}" data-fit style="--d:.25s">{{ $groomUp }}</span></span>
        <span class="fp-mask"><span class="fp-mast-amp" style="--d:.45s">&amp;</span></span>
        <span class="fp-mask"><span class="fp-mast-name{{ $isKh($bride) ? ' fp-kh' : '' }}" data-fit style="--d:.6s">{{ $brideUp }}</span></span>
      </h1>

      @if ($dateKh || $venueName !== '')
        <p class="fp-deck fp-in" style="--d:.8s">{{ collect([$dateKh, $venueName])->filter()->implode(' — ') }}</p>
      @endif

      <div class="fp-coverlines">
        <p class="fp-cl-kicker fp-in" style="--d:.85s">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
        @if ($date)
          <p class="fp-cl-date fp-in" style="--d:1s">{{ $date->format('d') }}<i>.</i>{{ $date->format('m') }}<i>.</i>{{ $date->format('y') }}</p>
        @endif
        <p class="fp-cl-guest fp-in" style="--d:1.15s"><span>សូមគោរពអញ្ជើញ</span><b>{{ $guestLine }}</b></p>
        <a class="fp-btn fp-open fp-in" style="--d:1.3s" href="#fpInvite" data-sfx="open">
          បើកអានលិខិតអញ្ជើញ <span aria-hidden="true">&#8595;</span>
        </a>
      </div>

      <div class="fp-cover-foot fp-in" style="--d:1.4s" aria-hidden="true">
        <span class="fp-cover-ini">{{ $initials }}</span>
        <svg class="fp-barcode" viewBox="0 0 {{ $barW }} 40" preserveAspectRatio="none">{!! $bars !!}</svg>
        <span class="fp-cover-code">{{ $barcodeDigits }}</span>
      </div>
    </div>
  </section>

  {{-- marquee band --}}
  <div class="fp-marquee" aria-hidden="true">
    <div class="fp-marquee-track">
      @for ($r = 0; $r < 2; $r++)
        <span>សិរីមង្គលអាពាហ៍ពិពាហ៍</span><i>✦</i><span class="fp-mq-lat">{{ $couple }}</span><i>✦</i>
        @if ($dateDots)<span class="fp-mq-lat">{{ $dateDots }}</span><i>✦</i>@endif
        <span>សូមគោរពអញ្ជើញ</span><i>✦</i><span class="fp-mq-lat">SAVE THE DATE</span><i>✦</i>
      @endfor
    </div>
  </div>

  <main class="fp-main">

    {{-- ─────────── 02 · the invitation ─────────── --}}
    <section class="fp-sec fp-invite" id="fpInvite" data-page="{{ $page() }}">
      <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>The Invitation · លិខិតអញ្ជើញ</span></p>

      @if ($groomParents || $brideParents)
        <div class="fp-byline fp-rv">
          @if ($groomParents)
            <div><p class="fp-label">ខាងកូនប្រុស</p>@foreach ($groomParents as $p)<p class="fp-parent">{{ $p }}</p>@endforeach</div>
          @endif
          @if ($brideParents)
            <div><p class="fp-label">ខាងកូនស្រី</p>@foreach ($brideParents as $p)<p class="fp-parent">{{ $p }}</p>@endforeach</div>
          @endif
        </div>
      @endif

      <div class="fp-invite-grid">
        <div class="fp-invite-main">
          <h2 class="fp-h-kh fp-mask fp-rv"><span>សូមគោរពអញ្ជើញ</span></h2>
          <p class="fp-guest fp-rv{{ $isKh($guestLine) ? ' fp-kh' : '' }}">{{ $guestLine }}</p>
          <p class="fp-lede fp-rv">{{ $reception }}</p>

          <div class="fp-names fp-rv">
            <p><span class="fp-label">កូនប្រុសនាម</span><b class="{{ $isKh($groom) ? 'fp-kh' : '' }}">{{ $groomUp }}</b></p>
            <span class="fp-names-amp" aria-hidden="true">&amp;</span>
            <p><span class="fp-label">កូនស្រីនាម</span><b class="{{ $isKh($bride) ? 'fp-kh' : '' }}">{{ $brideUp }}</b></p>
          </div>
        </div>

        <aside class="fp-quote fp-rv">
          <span class="fp-quote-mark" aria-hidden="true">“</span>
          <blockquote>{{ $quote }}</blockquote>
          <p class="fp-quote-by">— {{ $couple }}</p>
        </aside>
      </div>
    </section>

    {{-- ─────────── 03 · the date: giant numerals under a scratch foil ─────────── --}}
    @if ($date)
      <section class="fp-sec fp-date" id="fpDate" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Save the date · កត់ត្រាទុក</span></p>
        <div class="fp-date-grid">
          <div class="fp-date-card fp-rv">
            <x-scratch-card class="fp-scratch" ratio="4/3" :heart="false" cover="កោសដើម្បីមើលថ្ងៃ" hint="កោសលើផ្ទាំងនេះ"
              caption="{{ $date->format('l · j F Y') }}" label="កោសដើម្បីបង្ហាញថ្ងៃរៀបការ"
              :reveal-sound="$sfx('reveal.mp3')">
              <div class="fp-numerals" aria-hidden="true">
                <span class="fp-num-d">{{ $date->format('d') }}</span>
                <span class="fp-num-m">{{ $date->format('m') }}</span>
                <span class="fp-num-y">{{ $date->format('Y') }}</span>
                <span class="fp-num-wd">{{ mb_strtoupper($date->format('l')) }}</span>
              </div>
            </x-scratch-card>
          </div>

          <div class="fp-date-info">
            <h2 class="fp-h-kh fp-mask fp-rv"><span>{{ $dateKh }}</span></h2>
            <p class="fp-date-time fp-rv">{{ $timeKh }}</p>
            @if ($lunar)<p class="fp-date-lunar fp-rv">{{ $lunar }}</p>@endif

            @if ($showCountdown)
              <div class="fp-count fp-rv" id="fpCount">
                <p><b data-u="d">00</b><span>ថ្ងៃ</span></p>
                <p><b data-u="h">00</b><span>ម៉ោង</span></p>
                <p><b data-u="m">00</b><span>នាទី</span></p>
                <p><b data-u="s">00</b><span>វិនាទី</span></p>
              </div>
            @endif
          </div>
        </div>
      </section>
    @endif

    {{-- ─────────── 04 · photo booth: strips that print into view ─────────── --}}
    @if ($strips)
      <section class="fp-sec fp-booth" id="fpBooth" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Photo booth · អនុស្សាវរីយ៍</span></p>
        <header class="fp-booth-head">
          <h2 class="fp-h-big fp-mask fp-rv"><span>Photo<em>booth</em></span></h2>
          <p class="fp-sub-kh fp-rv">រូបភាពអនុស្សាវរីយ៍របស់យើង — ចុចលើរូបដើម្បីមើលធំ</p>
        </header>
        <div class="fp-strips" style="--n: {{ count($strips) }}">
          @foreach ($strips as $s => $strip)
            <div class="fp-strip fp-print{{ $s % 3 === 1 ? ' is-mono' : '' }}" style="--i: {{ $s }}; --tilt: {{ [-2.2, 1.6, -1.2, 2.4][$s % 4] }}deg">
              @foreach ($strip as $shot)
                <button type="button" class="fp-frame fp-ph" data-full="{{ $shot['src'] }}" data-cap="{{ $shot['cap'] }}" aria-label="មើលរូបទី {{ $khDigits($shot['n']) }}">
                  <img src="{{ $shot['src'] }}" alt="" loading="lazy" decoding="async" draggable="false">
                </button>
              @endforeach
              <p class="fp-strip-label"><span class="{{ $isKh($initials) ? 'fp-kh' : '' }}">{{ $initials }}</span><span>{{ $date ? $date->format('d.m.y') : '♥' }}</span></p>
            </div>
          @endforeach
        </div>
      </section>
    @endif

    {{-- ─────────── 05 · the story so far ─────────── --}}
    @if ($story->isNotEmpty())
      <section class="fp-sec fp-story" id="fpStory" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Feature · ដំណើរស្នេហា</span></p>
        <div class="fp-story-grid">
          <div class="fp-story-side">
            <h2 class="fp-h-big fp-mask fp-rv"><span>The story<em>so far</em></span></h2>
            <p class="fp-sub-kh fp-rv">ដំណើររឿងស្នេហារបស់យើង</p>
            @if ($featurePhoto)
              <figure class="fp-story-ph fp-rv">
                <button type="button" class="fp-ph" data-full="{{ $featurePhoto }}" aria-label="មើលរូបធំ"><img src="{{ $featurePhoto }}" alt="" loading="lazy" decoding="async"></button>
                <figcaption>{{ $couple }}</figcaption>
              </figure>
            @endif
          </div>
          <ol class="fp-chapters">
            @foreach ($story as $i => $c)
              <li class="fp-chapter fp-rv">
                <span class="fp-chapter-no" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                <div>
                  @if (trim($c['title'] ?? '') !== '')<h3>{{ trim($c['title']) }}</h3>@endif
                  @if (trim($c['text'] ?? '') !== '')<p>{{ trim($c['text']) }}</p>@endif
                </div>
              </li>
            @endforeach
          </ol>
        </div>
      </section>
    @endif

    {{-- ─────────── 06 · program, as a contents page ─────────── --}}
    @if ($scheduleImg || $schedule->isNotEmpty())
      <section class="fp-sec fp-program" id="fpProgram" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Programme · កម្មវិធី</span></p>
        <div class="fp-program-grid">
          <header>
            <h2 class="fp-h-big fp-mask fp-rv"><span>The<em>big day</em></span></h2>
            <p class="fp-sub-kh fp-rv">កម្មវិធីសិរីមង្គលអាពាហ៍ពិពាហ៍</p>
            @if ($dateKh)<p class="fp-program-date fp-rv">{{ $dateKh }}</p>@endif
          </header>
          @if ($scheduleImg)
            <figure class="fp-program-img fp-rv">
              <button type="button" class="fp-ph" data-full="{{ $scheduleImg }}" aria-label="មើលកម្មវិធីធំ"><img src="{{ $scheduleImg }}" alt="កម្មវិធី" loading="lazy"></button>
            </figure>
          @else
            <ol class="fp-toc">
              @foreach ($schedule as $i => $row)
                @php [$t, $period] = $schedTime($row['time'] ?? ''); @endphp
                <li class="fp-rv" style="--d: {{ $i * .06 }}s">
                  <span class="fp-toc-time">{{ $t }}@if ($period)<small>{{ $period }}</small>@endif</span>
                  <i aria-hidden="true"></i>
                  <span class="fp-toc-label">{{ $row['label'] ?? '' }}</span>
                  <span class="fp-toc-no" aria-hidden="true">{{ sprintf('%02d', $i + 1) }}</span>
                </li>
              @endforeach
            </ol>
          @endif
        </div>
      </section>
    @endif

    {{-- ─────────── 07 · location ─────────── --}}
    @if ($venueName !== '' || $venueAddress !== '' || $mapUrl)
      <section class="fp-sec fp-venue" id="fpVenue" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Location · ទីតាំង</span></p>
        <div class="fp-venue-grid">
          <div>
            <p class="fp-label fp-rv">ទីកន្លែងប្រារព្ធពិធី</p>
            @if ($venueName !== '')<h2 class="fp-venue-name fp-mask fp-rv{{ $isKh($venueName) ? ' fp-kh' : '' }}"><span>{{ $venueName }}</span></h2>@endif
            @if ($venueAddress !== '')<p class="fp-venue-addr fp-rv">{{ $venueAddress }}</p>@endif
            @if ($dressCode !== '')<p class="fp-venue-dress fp-rv"><span class="fp-label">សម្លៀកបំពាក់</span>{{ $dressCode }}</p>@endif
            @if ($mapUrl)
              <a class="fp-btn fp-rv" href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer">មើលផែនទី · Directions <span aria-hidden="true">&#8599;</span></a>
            @endif
          </div>
          @if ($mapEmbed)
            <figure class="fp-map fp-rv">
              <iframe src="{{ $mapEmbed }}" title="ផែនទី {{ $venueName }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </figure>
          @endif
        </div>
      </section>
    @endif

    {{-- ─────────── 08 · RSVP + gift ─────────── --}}
    @if ($showRsvp || $giftQr)
      <section class="fp-sec fp-reply" id="fpReply" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Reply · ឆ្លើយតប</span></p>
        <div class="fp-reply-grid{{ $showRsvp && $giftQr ? ' is-two' : '' }}">
          @if ($showRsvp)
            <div class="fp-rsvp fp-rv">
              <h2 class="fp-h-big"><span>R.S.V.P.</span></h2>
              <p class="fp-h-kh-sm">សូមបញ្ជាក់ការចូលរួម</p>
              <p class="fp-body">{{ $rsvpAsk }}</p>
              <div class="fp-rsvp-opts" id="fpRsvpOpts" data-url="{{ $rsvpUrl }}">
                <button type="button" class="fp-opt" data-status="yes">ចូលរួម</button>
                <button type="button" class="fp-opt" data-status="no">មិនអាចចូលរួម</button>
              </div>
              <form class="fp-rsvp-form" id="fpRsvpForm">
                <input type="text" id="fpRsvpNote" maxlength="500" placeholder="ផ្ញើសារជូនពរ (មិនចាំបាច់)" autocomplete="off" aria-label="សារជូនពរ">
                <button type="submit" class="fp-btn">បញ្ជូន</button>
              </form>
              <p class="fp-rsvp-thanks" id="fpRsvpThanks" role="status"></p>
            </div>
          @endif
          @if ($giftQr)
            <div class="fp-gift fp-rv">
              <p class="fp-label">Wedding gift</p>
              <h3 class="fp-h-kh-sm">ចំណងដៃ</h3>
              <p class="fp-body">{{ $giftText }}</p>
              <figure class="fp-qr">
                <img src="{{ $giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" loading="lazy">
                @if ($giftAccount !== '')<figcaption>{{ $giftAccount }}</figcaption>@endif
              </figure>
              <a class="fp-btn fp-btn-ghost" href="{{ $giftQr }}" download target="_blank" rel="noopener noreferrer">រក្សាទុក QR</a>
            </div>
          @endif
        </div>
      </section>
    @endif

    {{-- ─────────── 09 · letters + contacts ─────────── --}}
    @if ($letters->isNotEmpty() || $contacts->isNotEmpty())
      <section class="fp-sec fp-letters" id="fpLetters" data-page="{{ $page() }}">
        <p class="fp-folio fp-rv"><span>Nº {{ sprintf('%02d', $pg) }}</span><span>Letters · លិខិត</span></p>
        @if ($letters->isNotEmpty())
          <div class="fp-letters-grid" style="--n: {{ $letters->count() }}">
            @foreach ($letters as [$heading, $en, $body])
              <article class="fp-letter fp-rv">
                <p class="fp-label">{{ $en }}</p>
                <h3 class="fp-h-kh-sm">{{ $heading }}</h3>
                <p class="fp-body">{{ $body }}</p>
              </article>
            @endforeach
          </div>
        @endif
        @if ($contacts->isNotEmpty())
          <div class="fp-contacts fp-rv">
            <p class="fp-label">ទំនាក់ទំនង · Get in touch</p>
            <div class="fp-contacts-row">
              @foreach ($contacts as $c)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $c['phone']) }}">
                  <span class="fp-c-role">{{ $c['role'] }}</span>
                  @if ($c['name'] !== '')<span class="fp-c-name{{ $isKh($c['name']) ? ' fp-kh' : '' }}">{{ $c['name'] }}</span>@endif
                  <span class="fp-c-phone">{{ $c['phone'] }}</span>
                </a>
              @endforeach
            </div>
          </div>
        @endif
      </section>
    @endif

    {{-- ─────────── closing: the back cover ─────────── --}}
    <section class="fp-sec fp-close" id="fpClose">
      <p class="fp-close-ghost" aria-hidden="true">THANK YOU</p>
      <h2 class="fp-close-kh fp-mask fp-rv"><span>សូមអរគុណ</span></h2>
      <p class="fp-body fp-rv">វត្តមានរបស់លោកអ្នក គឺជាកាដូដ៏មានតម្លៃបំផុត សម្រាប់ថ្ងៃដ៏មានអត្ថន័យរបស់យើងខ្ញុំ។</p>
      <p class="fp-close-names fp-rv{{ $isKh($couple) ? ' fp-kh' : '' }}">{{ $groomUp }} <em>&amp;</em> {{ $brideUp }}</p>
      @if ($dateDots)<p class="fp-close-date fp-rv">{{ $dateDots }}</p>@endif
    </section>
  </main>

  <x-invitation-footer prefix="fp" :names="$couple" :names-class="$isKh($couple) ? 'fp-kh' : ''" :date="$dateDots ? str_replace('.', ' · ', $dateDots) : null"
    style="--inv-footer-bg: #161311; --inv-footer-text: #f1ebe0; --inv-footer-muted: #b9afa1; --inv-footer-accent: {{ $accent }}; --inv-footer-ring: #161311; --inv-footer-names-font: 'Bodoni Moda', 'Noto Serif Khmer', serif; --inv-footer-caps-font: 'Archivo', 'Kantumruy Pro', sans-serif; --inv-footer-kh-font: 'Kantumruy Pro', sans-serif;">
    <x-slot:ornament><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.2 7.8L22 12l-7.8 2.2L12 22l-2.2-7.8L2 12l7.8-2.2z" fill="currentColor"/></svg></x-slot:ornament>
  </x-invitation-footer>

  {{-- sound + music controls --}}
  <div class="fp-ctrl">
    @if ($hasMusic)
      <button class="fp-ctrl-btn" id="fpMusic" type="button" aria-pressed="false" aria-label="ចាក់ / ផ្អាកតន្ត្រី">
        <span class="fp-eq" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
    @endif
    <button class="fp-ctrl-btn" id="fpSound" type="button" aria-pressed="false" aria-label="បិទសំឡេង">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9v6h4l5 4V5L8 9H4z" fill="currentColor"/><path class="on" d="M16 8.5a5 5 0 0 1 0 7M18.5 6a8.5 8.5 0 0 1 0 12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path class="off" d="M16.5 9.5l5 5m0-5l-5 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </button>
  </div>

  {{-- photo preview --}}
  <div class="fp-lb" id="fpLb" role="dialog" aria-modal="true" aria-label="មើលរូបភាព" hidden>
    <figure><img id="fpLbImg" alt=""><figcaption id="fpLbCap"></figcaption></figure>
    <button type="button" class="fp-lb-x" id="fpLbX" aria-label="បិទ">&#10005;</button>
  </div>

  @if ($musicFile)<audio id="fpAudio" src="{{ $musicFile }}" preload="none" loop></audio>@endif
</div>

<script id="fp-config" type="application/json">{!! json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
<script src="{{ $fpAsset('js/invitations/front-page.js') }}"></script>
