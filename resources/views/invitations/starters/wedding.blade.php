{{--
  STARTER for a new wedding template — copy this file to resources/views/invitations/templates/wedding/{name}.blade.php,
  rename the `st` prefix, then design it (layout, ornaments, colours, fonts, the cover + its opening). It already does
  the boring, easy-to-get-wrong parts with the shared pieces, so a new template only adds its look:

    · App\Support\Invitations\WeddingData  — every field prepared (Khmer dates, map, gift, contacts, dress colours, gating)
    · <x-invite-kit>  + InviteKit (JS)      — music + mute + sfx, reveals, FLIP photo preview, countdown, RSVP, copy, fly()
    · <x-invitation-footer>                 — the required shared footer

  It supports every wedding field and hides each section when its field is empty / locked. Kept deliberately plain:
  this is a skeleton, not a design. Rules still apply (CLAUDE.md + docs/invitation-templates.md): Latin fonts always
  followed by a Khmer font, no letter-spacing on Khmer, dress code as swatches, sfx on every interaction, advanced
  transitions (memory: template quality bar). Preview: /demo/starter (renders this file with the wedding sample).
--}}
@php
    use App\Support\Invitations\WeddingData as W;

    $w = W::from($fields, $invitation, $recipient ?? null, ['accent' => '#b0654b', 'closing_line' => 'Happily ever after']);
    $kh = fn (?string $s) => W::isKhmer($s) ? ' ik-kh' : '';
    $guest = ! empty($recipientName) ? $recipientName : 'លោក លោកស្រី អ្នកនាង កញ្ញា';
    $skipCover = request()->boolean('open');
@endphp
<title>{{ $w->coupleTitle ?: 'សិរីមង្គលអាពាហ៍ពិពាហ៍' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Noto+Serif+Khmer:wght@400;600&family=Kantumruy+Pro:wght@400;500;600&display=swap">
<style>
  .st{ --ik-accent:{{ $w->accent }}; --ik-cap-font:"Cormorant Garamond","Noto Serif Khmer",serif; --serif:"Cormorant Garamond","Noto Serif Khmer",serif;
    min-height:100vh; background:#f7f1e8; color:#3b2a24; font-family:"Kantumruy Pro",sans-serif; line-height:1.8; text-align:center; }
  .st *{ box-sizing:border-box; } .st img{ display:block; max-width:100%; }
  .st.js .st-page{ visibility:hidden; } .st.is-open .st-page{ visibility:visible; }
  .st-cover{ position:fixed; inset:0; z-index:80; display:grid; place-items:center; align-content:center; gap:10px; padding:24px; background:#efe6d8; transition:opacity .6s ease, visibility 0s .6s; }
  .st-cover.is-gone{ opacity:0; visibility:hidden; }
  .st-cover-photo, .st-hero-photo{ width:min(70vw, 300px); aspect-ratio:3/4; overflow:hidden; border-radius:150px 150px 12px 12px; }
  .st-cover-photo img, .st-hero-photo img{ width:100%; height:100%; object-fit:cover; }
  .st-names{ margin:0; font-family:var(--serif); font-size:2.4rem; font-weight:600; line-height:1.2; }
  .st-btn{ display:inline-flex; gap:8px; align-items:center; justify-content:center; margin-top:14px; padding:12px 26px; border:0; border-radius:999px; background:var(--ik-accent); color:#fff; font:600 1rem "Kantumruy Pro",sans-serif; text-decoration:none; cursor:pointer; }
  .st-btn.ghost{ background:transparent; color:var(--ik-accent); box-shadow:inset 0 0 0 1.5px var(--ik-accent); }
  .st-sec{ width:min(100%, 600px); margin:0 auto; padding:64px 18px; }
  .st-h{ margin:0 0 18px; font-family:var(--serif); font-size:2rem; color:var(--ik-accent); }
  .st-card{ padding:28px 20px; border-radius:16px; background:#fffdf9; box-shadow:0 14px 30px -18px rgba(60,40,30,.45); }
  .st-soft{ color:#8a7468; }
  .st-cal{ display:grid; grid-template-columns:repeat(7, 1fr); gap:4px; max-width:340px; margin:0 auto 12px; }
  .st-cal span{ aspect-ratio:1; display:grid; place-items:center; } .st-cal .on{ border-radius:50%; background:var(--ik-accent); color:#fff; font-weight:700; }
  .st-count{ display:grid; grid-template-columns:repeat(4, 1fr); gap:10px; margin-top:18px; }
  .st-count b{ display:block; font-family:var(--serif); font-size:2rem; } .st-count b.flip{ animation:st-flip .5s ease; }
  @keyframes st-flip{ from{ transform:rotateX(90deg); opacity:.3; } }
  .st-gal{ display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:8px; }
  .st-gal button{ aspect-ratio:1; padding:0; border:0; overflow:hidden; border-radius:10px; cursor:zoom-in; } .st-gal img{ width:100%; height:100%; object-fit:cover; }
  .st-swatches{ display:flex; flex-wrap:wrap; justify-content:center; gap:14px; margin:16px 0 8px; padding:0; list-style:none; }
  .st-swatches i{ display:block; width:52px; height:52px; margin:0 auto 6px; border-radius:50%; background:var(--c); box-shadow:0 0 0 3px #fff, 0 6px 12px -4px rgba(0,0,0,.35); }
  .st-opts{ display:grid; gap:8px; margin-top:14px; } .st-opts button{ padding:12px; border-radius:12px; border:1.5px solid var(--ik-accent); background:none; font:inherit; cursor:pointer; }
  .st-opts [aria-checked="true"]{ background:var(--ik-accent); color:#fff; }
  .st-form{ display:none; } .st-form.show{ display:block; } .st-form textarea{ width:100%; margin-top:10px; padding:10px; border-radius:10px; border:1px solid #ddd; font:inherit; }
  .st-map{ width:100%; aspect-ratio:16/10; margin-top:14px; border:0; border-radius:12px; }
</style>

<div class="st{{ $skipCover ? ' is-open' : '' }}">
  {{-- ===== cover: the couple photo + open (the photo flies into the hero) ===== --}}
  @unless ($skipCover)
    <div class="st-cover" id="st-cover" role="dialog" aria-modal="true" aria-label="{{ trim('លិខិតអញ្ជើញ '.$w->coupleTitle) }}">
      <p class="st-soft">សិរីមង្គលអាពាហ៍ពិពាហ៍</p>
      <div class="st-cover-photo" id="st-cover-photo">@if ($w->cover)<img src="{{ $w->cover }}" alt="{{ $w->coupleTitle }}">@endif</div>
      @if ($w->titleImage)<img src="{{ $w->titleImage }}" alt="{{ $w->coupleTitle }}" style="max-height:80px">@else<h1 class="st-names{{ $kh($w->coupleTitle) }}">{{ $w->coupleTitle }}</h1>@endif
      <p>សូមគោរពអញ្ជើញ <b class="{{ trim($kh($guest)) }}">{{ $guest }}</b></p>
      <button type="button" class="st-btn" id="st-open">បើកសំបុត្រ</button>
    </div>
  @endunless

  <main class="st-page">
    <section class="st-sec">
      <div class="st-hero-photo" id="st-hero-photo" style="margin:0 auto">@if ($w->cover)<img src="{{ $w->cover }}" alt="">@endif</div>
      <p class="st-soft ik-r" data-ik="up">{{ $w->headline }}</p>
      <h2 class="st-names ik-r{{ $kh($w->coupleTitle) }}" data-ik="up">{{ $w->coupleTitle }}</h2>
      @if ($w->dateKhmer)<p class="ik-r" data-ik="up">{{ $w->dateKhmer }}</p>@endif
    </section>

    {{-- invitation --}}
    <section class="st-sec"><div class="st-card ik-r" data-ik="fold">
      @if ($w->groomParents || $w->brideParents)
        <p>@foreach ($w->groomParents as $p){{ $p }}<br>@endforeach</p><p>@foreach ($w->brideParents as $p){{ $p }}<br>@endforeach</p>
      @endif
      <h3 class="st-h">សូមគោរពអញ្ជើញ</h3>
      <p class="st-names{{ $kh($guest) }}" style="font-size:1.6rem">{{ $guest }}</p>
      <p class="ik-r" data-ik="words">{{ $w->receptionText }}</p>
      <p>{{ $w->dateKhmer }}</p>
      @if ($w->khmerDate)<p class="st-soft">{{ $w->khmerDate }}</p>@endif
      <p style="color:var(--ik-accent)">{{ $w->timeKhmer }}</p>
    </div></section>

    @if ($w->groomPhoto || $w->bridePhoto)
      <section class="st-sec"><h3 class="st-h">The bride &amp; groom</h3>
        <div class="st-gal" style="grid-template-columns:1fr 1fr">
          @foreach ([[$w->groomPhoto, $w->groom], [$w->bridePhoto, $w->bride]] as [$photo, $name])
            @if ($photo)<figure class="ik-r" data-ik="zoom" style="margin:0"><img src="{{ $photo }}" alt="{{ $name }}" style="border-radius:12px"><figcaption class="{{ trim($kh($name)) }}">{{ $name }}</figcaption></figure>@endif
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->message !== '')<section class="st-sec"><p class="ik-r" data-ik="words" style="font-family:var(--serif);font-size:1.3rem;font-style:italic">{{ $w->message }}</p></section>@endif

    {{-- save the date --}}
    @if ($w->calendar)
      <section class="st-sec"><h3 class="st-h">Save the date</h3><div class="st-card ik-r" data-ik="zoom">
        <p><b>{{ $w->calendar['title'] }}</b></p>
        <div class="st-cal">@foreach (W::KH_DAYS_SHORT as $d)<span class="st-soft">{{ $d }}</span>@endforeach
          @foreach ($w->calendar['cells'] as $c)<span class="{{ $c === $w->calendar['day'] ? 'on' : '' }}">{{ $c ? W::khDigits($c) : '' }}</span>@endforeach</div>
        @if ($w->showCountdown)
          <div class="st-count" id="st-count" data-target="{{ $w->isoDate }}">
            @foreach (['d' => 'ថ្ងៃ', 'h' => 'ម៉ោង', 'm' => 'នាទី', 's' => 'វិនាទី'] as $u => $l)<div><b data-u="{{ $u }}">០០</b><span class="st-soft">{{ $l }}</span></div>@endforeach
          </div>
        @endif
      </div></section>
    @endif

    @if ($w->story->isNotEmpty())
      <section class="st-sec"><h3 class="st-h">Our story</h3>
        @foreach ($w->story as $c)<div class="st-card ik-r" data-ik="{{ $loop->odd ? 'left' : 'right' }}" style="margin-bottom:12px">
          <b class="{{ trim($kh($c['place'])) }}">{{ $c['place'] }}</b> <span class="st-soft">{{ $c['when'] }}</span><p>{{ $c['text'] }}</p></div>@endforeach
      </section>
    @endif

    @if ($w->scheduleImage || $w->schedule->isNotEmpty())
      <section class="st-sec"><h3 class="st-h">កម្មវិធី</h3>
        @if ($w->scheduleImage)
          <button type="button" data-ik-photo data-group="sched" data-full="{{ $w->scheduleImage }}" style="border:0;padding:0;background:none"><img src="{{ $w->scheduleImage }}" alt="កម្មវិធី"></button>
        @else
          @foreach ($w->schedule as $row)<p class="ik-r" data-ik="left"><b style="color:var(--ik-accent)">{{ $row['time'] }}</b> · {{ $row['label'] }}</p>@endforeach
        @endif
      </section>
    @endif

    {{-- gallery: data-ik-photo = opens the shared FLIP preview --}}
    @if ($w->gallery->isNotEmpty())
      <section class="st-sec"><h3 class="st-h">Moments</h3>
        <div class="st-gal">
          @foreach ($w->gallery as $i => $url)
            <button type="button" class="ik-r" data-ik="mask" data-ik-photo data-group="gallery" data-g="{{ $i }}" data-full="{{ $url }}" data-cap="{{ $w->captions[$i] ?? '' }}" aria-label="{{ ($w->captions[$i] ?? '') ?: 'រូបភាពទី '.W::khDigits($i + 1) }}"><img src="{{ $url }}" alt="" loading="lazy"></button>
          @endforeach
        </div>
      </section>
    @endif

    @if ($w->showVenue)
      <section class="st-sec"><h3 class="st-h">ទីតាំង</h3><div class="st-card ik-r" data-ik="up">
        @if ($w->venueName)<p><b class="{{ trim($kh($w->venueName)) }}">{{ $w->venueName }}</b></p>@endif
        @if ($w->venueAddress)<p>{{ $w->venueAddress }}</p>@endif
        @if ($w->mapEmbed)<iframe class="st-map" src="{{ $w->mapEmbed }}" loading="lazy" title="ផែនទី"></iframe>@endif
        @if ($w->mapUrl)<a class="st-btn" href="{{ $w->mapUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">មើលផែនទី</a>@endif
      </div></section>
    @endif

    {{-- guest notes: dress code is ALWAYS swatches --}}
    @if ($w->dressCode !== '' || $w->whatToBring !== '' || $w->rsvpBy)
      <section class="st-sec"><h3 class="st-h">ព័ត៌មានសម្រាប់ភ្ញៀវ</h3>
        @if ($w->dressCode !== '')<div class="st-card ik-r" data-ik="up">
          <b>ពណ៌សម្លៀកបំពាក់</b>
          @if ($w->dressColours)<ul class="st-swatches">@foreach ($w->dressColours as $c)<li style="--c:{{ $c['hex'] }}"><i></i><span class="{{ trim($kh($c['name'])) }}">{{ $c['name'] }}</span></li>@endforeach</ul>@endif
          <p>{{ $w->dressCode }}</p></div>@endif
        @if ($w->whatToBring !== '')<p class="ik-r" data-ik="up"><b>សូមយកមកជាមួយ:</b> {{ $w->whatToBring }}</p>@endif
        @if ($w->rsvpBy)<p class="ik-r" data-ik="up"><b>សូមឆ្លើយតបមុន:</b> {{ $w->rsvpBy }}</p>@endif
      </section>
    @endif

    @if ($w->showRsvp)
      <section class="st-sec"><h3 class="st-h">RSVP</h3><div class="st-card ik-r" data-ik="fold" id="st-rsvp-card">
        <p>{{ $w->rsvpQuestion }}</p>
        <div class="st-opts" id="st-rsvp-opts" role="radiogroup" aria-label="ការឆ្លើយតប">
          <button type="button" role="radio" aria-checked="false" data-status="yes">ខ្ញុំនឹងចូលរួម</button>
          <button type="button" role="radio" aria-checked="false" data-status="no">សោកស្ដាយ មិនអាចចូលរួមបាន</button>
        </div>
        <form class="st-form" id="st-rsvp-form"><textarea id="st-rsvp-note" rows="2" maxlength="500" placeholder="សរសេរពាក្យជូនពរ (មិនចាំបាច់)"></textarea><button type="submit" class="st-btn">ផ្ញើ</button></form>
        <p id="st-rsvp-thanks" role="status"></p>
      </div></section>
    @endif

    @if ($w->giftQr)
      <section class="st-sec"><h3 class="st-h">ចំណងដៃ</h3><div class="st-card ik-r" data-ik="zoom">
        <p>{{ $w->giftMessage }}</p>
        <img src="{{ $w->giftQr }}" alt="QR code សម្រាប់ផ្ញើចំណងដៃ" style="width:220px;margin:12px auto" loading="lazy">
        @if ($w->giftAccount)<p style="white-space:pre-line">{{ $w->giftAccount }}</p>@endif
        @if ($w->giftNumber)<button type="button" class="st-btn" data-copy="{{ $w->giftNumber }}">ចម្លងលេខគណនី</button>@endif
        <a class="st-btn ghost" href="{{ $w->giftQr }}" download target="_blank" rel="noopener noreferrer" data-sfx="tap">រក្សាទុក QR</a>
      </div></section>
    @endif

    @if ($w->contacts->isNotEmpty() || $w->ctaLabel)
      <section class="st-sec"><h3 class="st-h">ទំនាក់ទំនង</h3>
        @foreach ($w->contacts as $c)<p><span class="st-soft">{{ $c['role'] }}</span> {{ $c['name'] }} · <a href="tel:{{ $c['tel'] }}" data-sfx="tap">{{ $c['phone'] }}</a></p>@endforeach
        @if ($w->ctaLabel)<a class="st-btn" href="{{ $w->ctaUrl }}" target="_blank" rel="noopener noreferrer" data-sfx="tap">{{ $w->ctaLabel }}</a>@endif
      </section>
    @endif

    @foreach ($w->letters as [$khHead, $en, $text])
      <section class="st-sec"><div class="st-card ik-r" data-ik="up"><h3 class="st-h">{{ $khHead }}</h3><p>{{ $text }}</p></div></section>
    @endforeach

    <section class="st-sec">
      @if ($w->background)<img src="{{ $w->background }}" alt="" class="ik-r" data-ik="zoom" style="border-radius:16px;margin-bottom:16px" loading="lazy">@endif
      <p class="st-names ik-r" data-ik="blur">{{ $w->closingLine }}</p>
    </section>

    <x-invitation-footer :names="$w->coupleTitle" :names-class="W::isKhmer($w->coupleTitle) ? 'ik-kh' : ''" :date="$w->dateNumeric"
      style="--inv-footer-bg:#3b2a24; --inv-footer-text:#f7f1e8; --inv-footer-muted:#cdbcaa; --inv-footer-accent:{{ $w->accent }};" />
  </main>

  <x-invite-kit :w="$w" wait />
</div>

<script>
  (function () {
    var root = document.querySelector('.st');
    root.classList.add('js');
    var cover = document.getElementById('st-cover');
    var kit = InviteKit({ root: root, cover: !!cover });
    kit.words('[data-ik="words"]');
    kit.lightbox();
    kit.countdown(document.getElementById('st-count'));
    root.querySelectorAll('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { kit.copy(b.getAttribute('data-copy'), 'បានចម្លងលេខគណនី ✓'); }); });
    var thanks = document.getElementById('st-rsvp-thanks');
    kit.rsvp({
      opts: document.getElementById('st-rsvp-opts'), form: document.getElementById('st-rsvp-form'), note: document.getElementById('st-rsvp-note'),
      url: @json($w->rsvpUrl),
      onDone: function (status, ok) {
        thanks.textContent = !ok ? 'មានបញ្ហាបន្តិច សូមព្យាយាមម្ដងទៀត' : (status === 'yes' ? 'សូមអរគុណ! ♥' : 'សូមអរគុណដែលបានជូនដំណឹង។');
        if (ok) { var r = thanks.getBoundingClientRect(); kit.burst(r.left + r.width / 2, r.top); }
      }
    });
    var start = function () { root.classList.add('is-open'); kit.reveal('.ik-r'); };
    if (!cover) { start(); return; }
    kit.lock(true);
    document.getElementById('st-open').addEventListener('click', function () {
      kit.startSong(); kit.sfx('reveal', .4);
      start();                                   // show the page behind the cover first…
      var hero = document.getElementById('st-hero-photo');
      hero.style.visibility = 'hidden';
      kit.settle(function () {                   // …let its first paint finish, then move
        cover.classList.add('is-gone');
        kit.fly(document.getElementById('st-cover-photo'), hero).then(function () {
          hero.style.visibility = ''; kit.sfx('chime', .3); kit.lock(false);
          var r = hero.getBoundingClientRect(); kit.burst(r.left + r.width / 2, r.top + r.height / 3, { n: 16 });
        });
      });
    });
  })();
</script>
