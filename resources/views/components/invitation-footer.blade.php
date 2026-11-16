{{--
  The one footer every invitation template ends with.

    <x-invitation-footer prefix="kw" :names="$coupleTitle" names-class="..." :date="$dateNumeric">
      <x-slot:ornament><svg aria-hidden="true"><use href="#kw-lotus"/></svg></x-slot:ornament>
      (optional extra content, shown above the brand line)
    </x-invitation-footer>

  prefix   — the template's own class prefix. Adds its existing classes/IDs ({p}-footer, {p}-top + id {p}-totop,
             {p}-divider, {p}-footer-names, {p}-footer-date, {p}-footer-love, {p}-brand) so a template's
             footer styles and back-to-top script keep working. Without it the shared default look is used.
  names / date / love — empty names or date are left out; love defaults to the Khmer «made with love» line.
  Theme a footer without its own CSS with --inv-footer-bg / -text / -muted / -accent (see footer.css).
  Social links come from config/roumdoul.php (.env); empty ones aren't shown.
--}}
@props([
    'prefix' => null,
    'names' => null,
    'namesClass' => '',
    'date' => null,
    'love' => 'រៀបចំឡើងដោយក្តីស្រឡាញ់',
    'topLabel' => 'ត្រឡប់ទៅដើម · Back to top',
])
@php
    $p = fn (string $part) => $prefix ? " {$prefix}-{$part}" : '';
    $social = collect(config('roumdoul.social', []))->filter(fn ($url) => is_string($url) && trim($url) !== '' && trim($url) !== '#');
    $icons = [
        'facebook' => '<path d="M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.02 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.507 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.631.772-1.631 1.563v1.877h2.773l-.443 2.91h-2.33V22c4.78-.756 8.438-4.92 8.438-9.94z"/>',
        'instagram' => '<path fill-rule="evenodd" clip-rule="evenodd" d="M12 2c-2.716 0-3.056.012-4.123.06-1.064.05-1.79.218-2.427.465a4.9 4.9 0 00-1.771 1.153A4.9 4.9 0 002.525 5.45c-.247.637-.416 1.363-.465 2.427C2.012 8.944 2 9.284 2 12s.012 3.056.06 4.123c.05 1.064.218 1.79.465 2.427a4.9 4.9 0 001.153 1.771 4.9 4.9 0 001.771 1.153c.637.247 1.363.416 2.427.465C8.944 21.988 9.284 22 12 22s3.056-.012 4.123-.06c1.064-.05 1.79-.218 2.427-.465a4.9 4.9 0 001.771-1.153 4.9 4.9 0 001.153-1.771c.247-.637.416-1.363.465-2.427.048-1.067.06-1.407.06-4.123s-.012-3.056-.06-4.123c-.05-1.064-.218-1.79-.465-2.427a4.9 4.9 0 00-1.153-1.771A4.9 4.9 0 0018.55 2.525c-.637-.247-1.363-.416-2.427-.465C15.056 2.012 14.716 2 12 2zm0 1.802c2.67 0 2.986.01 4.04.059.976.045 1.505.207 1.858.344.467.182.8.399 1.15.748.35.35.566.683.748 1.15.137.353.3.882.344 1.858.048 1.054.059 1.37.059 4.04s-.01 2.986-.059 4.04c-.045.976-.207 1.505-.344 1.858a3.1 3.1 0 01-.748 1.15 3.1 3.1 0 01-1.15.748c-.353.137-.882.3-1.858.344-1.054.048-1.37.059-4.04.059s-2.987-.01-4.04-.059c-.976-.045-1.505-.207-1.858-.344a3.1 3.1 0 01-1.15-.748 3.1 3.1 0 01-.748-1.15c-.137-.353-.3-.882-.344-1.858-.048-1.054-.059-1.37-.059-4.04s.01-2.986.059-4.04c.045-.976.207-1.505.344-1.858.182-.467.399-.8.748-1.15a3.1 3.1 0 011.15-.748c.353-.137.882-.3 1.858-.344 1.054-.048 1.37-.059 4.04-.059zm0 3.064a5.135 5.135 0 100 10.269 5.135 5.135 0 000-10.27zm0 8.468a3.333 3.333 0 110-6.666 3.333 3.333 0 010 6.666zm6.538-8.671a1.2 1.2 0 11-2.4 0 1.2 1.2 0 012.4 0z"/>',
        'tiktok' => '<path d="M16.5 2h-3.02v13.44a2.7 2.7 0 11-2.7-2.7c.157 0 .31.012.46.036V9.7a5.72 5.72 0 105.72 5.72V9.15a7.06 7.06 0 003.55 1.02V7.16a4.16 4.16 0 01-4.01-4.16V2z"/>',
        'telegram' => '<path d="M21.05 2.927a1.5 1.5 0 00-1.523-.245L2.98 9.36a1.31 1.31 0 00.088 2.457l4.377 1.462 1.68 5.393a1 1 0 001.697.372l2.457-2.457 4.522 3.34a1.5 1.5 0 002.37-.94l2.334-14.09a1.5 1.5 0 00-.452-1.61zM9.66 13.996l-.33 3.386-1.36-4.36 9.09-6.99c.19-.146.44.08.28.25l-7.4 7.4a.87.87 0 00-.28.314z"/>',
    ];
    $labels = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'telegram' => 'Telegram'];
@endphp

@once
  <link rel="stylesheet" href="{{ asset('css/invitations/shared/footer.css') }}?v={{ @filemtime(public_path('css/invitations/shared/footer.css')) }}">
@endonce

<footer {{ $attributes->merge(['class' => 'inv-footer'.$p('footer')]) }}>
  <button type="button" class="inv-footer__top{{ $p('top') }}" @if ($prefix) id="{{ $prefix }}-totop" @endif data-inv-top aria-label="{{ $topLabel }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
  </button>

  <div class="inv-footer__divider{{ $p('divider') }}" aria-hidden="true">
    <i></i>
    @if (isset($ornament) && trim($ornament) !== '')
      {{ $ornament }}
    @else
      <svg viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="currentColor"/></svg>
    @endif
    <i></i>
  </div>

  @if (filled($names))<p class="inv-footer__names{{ $p('footer-names') }}{{ $namesClass ? ' '.trim($namesClass) : '' }}">{{ $names }}</p>@endif
  @if (filled($date))<p class="inv-footer__date{{ $p('footer-date') }}">{{ $date }}</p>@endif
  @if (filled($love))<p class="inv-footer__love{{ $p('footer-love') }}">{{ $love }} <span class="hb" aria-hidden="true">&#9829;</span></p>@endif

  {{ $slot }}

  @if ($social->isNotEmpty())
    <div class="inv-footer__social">
      @foreach ($social as $network => $url)
        @if (isset($icons[$network]))
          <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ config('roumdoul.brand') }} on {{ $labels[$network] }}">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $icons[$network] !!}</svg>
          </a>
        @endif
      @endforeach
    </div>
  @endif

  <p class="inv-footer__brand{{ $p('brand') }}">CRAFTED BY <a href="{{ config('roumdoul.site_url') }}" target="_blank" rel="noopener"><b>{{ config('roumdoul.brand') }}</b></a> · {{ now()->year }}</p>
</footer>

@once
  <script>
    /* back to top for every invitation footer (templates may also bind their own) */
    document.addEventListener('click', function (e) {
      var b = e.target.closest && e.target.closest('[data-inv-top]');
      if (!b) return;
      var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
  </script>
@endonce
