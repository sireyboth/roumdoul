@php
    /** @var \App\Portfolio\PortfolioContext $pf */
    $portfolio = $pf->portfolio;
    $locales = config('portfolio.locales');
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' — '.$pf->name() : $pf->copy('meta.title', $pf->name());
    $description = trim($__env->yieldContent('description')) ?: $pf->copy('meta.description');
    $ogImage = trim($__env->yieldContent('og_image')) ?: $portfolio->media()->url($portfolio->get('seo.og_image'))
        ?: $portfolio->media()->url($portfolio->get('portrait.src'));
    $favicon = $pf->image('brand/favicon.svg');
    $accentLight = $portfolio->get('theme.light.accent');
    $accentDark = $portfolio->get('theme.dark.accent');
@endphp
<!doctype html>
<html lang="{{ $locales[$pf->locale]['hreflang'] ?? $pf->locale }}" data-locale="{{ $pf->locale }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  {{--
    Runs before first paint:
    1. Theme — stored choice, else the system preference. No flash.
    2. "pf-js" — lets reveal animations hide content before it is painted; removed again
       if portfolio.js hasn't started within 3s, so content can never stay hidden.
    3. Page transition type — tells the CSS whether this navigation is a language switch
       (horizontal wipe), going back (reverse slide) or a normal step forward.
  --}}
  <script>
    (function () {
      var root = document.documentElement, t = null;
      try { t = localStorage.getItem('pf-theme'); } catch (e) {}
      if (t !== 'light' && t !== 'dark') t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      root.dataset.theme = t;

      root.classList.add('pf-js');
      setTimeout(function () { if (!window.__pfReady) root.classList.remove('pf-js'); }, 3000);

      var page = /\/(en|km)(\/[^?#]*)?$/;
      addEventListener('pagereveal', function (e) {
        if (!e.viewTransition) return;
        // A skipped transition (e.g. tab in background) rejects these — expected, not an error.
        e.viewTransition.ready.catch(function () {});
        e.viewTransition.updateCallbackDone.catch(function () {});
        // The page transition is the entrance animation: show content immediately instead of
        // also running the scroll-reveal (which would make the incoming page look empty).
        window.__pfVt = true;
        root.classList.add('pf-vt');
        e.viewTransition.finished.finally(function () { root.classList.remove('pf-vt'); });
        if (!window.navigation || !navigation.activation || !navigation.activation.from) return;
        var a = navigation.activation, from = new URL(a.from.url), to = new URL(a.entry.url);
        var f = from.pathname.replace(/\/$/, '').match(page), n = to.pathname.replace(/\/$/, '').match(page);
        if (f && n && f[1] !== n[1] && (f[2] || '') === (n[2] || '')) {
          e.viewTransition.types.add('pf-lang');
          e.viewTransition.types.add('pf-to-' + n[1]); // direction of the wipe
        } else if (a.navigationType === 'traverse' && a.from.index > a.entry.index) {
          e.viewTransition.types.add('pf-back');
        } else {
          e.viewTransition.types.add('pf-forward');
        }
      });
    })();
  </script>
  <title>{{ $fullTitle }}</title>
  @if ($description)<meta name="description" content="{{ $description }}">@endif
  <link rel="canonical" href="{{ $pf->canonical() }}">
  @foreach ($portfolio->locales() as $code)
    <link rel="alternate" hreflang="{{ $locales[$code]['hreflang'] }}" href="{{ $pf->switchUrl($code) }}">
  @endforeach
  <link rel="alternate" hreflang="x-default" href="{{ $pf->switchUrl($portfolio->defaultLocale()) }}">

  <meta property="og:site_name" content="{{ $pf->name() }}">
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta property="og:title" content="{{ $fullTitle }}">
  @if ($description)<meta property="og:description" content="{{ $description }}">@endif
  <meta property="og:url" content="{{ $pf->canonical() }}">
  <meta property="og:locale" content="{{ $locales[$pf->locale]['og'] }}">
  @foreach ($portfolio->locales() as $code)
    @if ($code !== $pf->locale)<meta property="og:locale:alternate" content="{{ $locales[$code]['og'] }}">@endif
  @endforeach
  @if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
  @else
    <meta name="twitter:card" content="summary">
  @endif

  <meta name="color-scheme" content="light dark">
  <meta name="theme-color" content="#F4F1EA" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#191A18" media="(prefers-color-scheme: dark)">
  @if ($favicon)<link rel="icon" href="{{ $favicon['src'] }}" type="image/svg+xml">@endif

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  {{-- Khmer faces are only requested on Khmer pages (plus the language switcher's "ខ្មែរ" label, via text=). --}}
  @if ($pf->isKhmer())
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&family=Noto+Serif+Khmer:wght@400;500;600&display=swap">
  @else
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Manrope:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@500&text={{ urlencode('ខ្មែរ') }}&display=swap">
  @endif

  @if ($accentLight || $accentDark)
    <style>
      @if ($accentLight):root { --pf-accent: {{ $accentLight }}; }@endif
      @if ($accentDark):root[data-theme="dark"] { --pf-accent: {{ $accentDark }}; }@endif
    </style>
  @endif

  @vite(['resources/css/portfolio.css', 'resources/js/portfolio.js'])
  @stack('head')
</head>
<body class="pf @yield('body_class')">
  <a class="pf-skip" href="#main">{{ $pf->t('nav.skip') }}</a>

  @include('portfolio.partials.header')

  <main id="main" class="pf-main" tabindex="-1">
    @yield('content')
  </main>

  @include('portfolio.partials.footer')

  {{-- Shared image viewer for every [data-lightbox] button on the page. --}}
  <dialog class="pf-lightbox" data-lightbox-dialog aria-label="{{ $pf->t('common.enlarge') }}">
    <div class="pf-lightbox__bar">
      <span class="pf-lightbox__count" data-lightbox-count></span>
      <button type="button" class="pf-lightbox__close" data-lightbox-close>{{ $pf->t('nav.close') }}</button>
    </div>
    <figure class="pf-lightbox__stage">
      <img data-lightbox-img alt="">
      <figcaption data-lightbox-caption></figcaption>
    </figure>
    <button type="button" class="pf-lightbox__nav pf-lightbox__nav--prev" data-lightbox-prev aria-label="{{ $pf->t('common.previous') }}">←</button>
    <button type="button" class="pf-lightbox__nav pf-lightbox__nav--next" data-lightbox-next aria-label="{{ $pf->t('common.next') }}">→</button>
  </dialog>

  {{--
    After a language switch, scroll back to the block that was at the top of the screen.
    Inline (not in portfolio.js) so it runs before the first paint — the page transition
    then animates in at the right place instead of at the top. The saving side lives in
    portfolio.js (initLanguageSwitch); keep the selector below in sync with it.
  --}}
  <script>
    (function () {
      var saved = null;
      try { saved = JSON.parse(sessionStorage.getItem('pf-lang-scroll') || 'null'); sessionStorage.removeItem('pf-lang-scroll'); } catch (e) { return; }
      if (!saved || saved.path !== location.pathname || location.hash) return;
      var items = [].filter.call(
        document.querySelectorAll('main section, main header, main article, main li, main [id]'),
        function (el) { return !el.closest('[hidden]') && !el.hasAttribute('data-reveal'); }
      );
      var el = items.length === saved.count ? items[saved.index] : null;
      if (el) window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - saved.offset);
    })();
  </script>
</body>
</html>
