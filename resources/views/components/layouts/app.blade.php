<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="UTF-8" />
  <script>
    (function () {
      var stored = localStorage.getItem('theme');
      document.documentElement.classList.toggle('dark', stored === 'dark');
      // Just switched language? Fade the page in (browsers with cross-page view transitions
      // animate it themselves — see resources/js/modules/lang.js).
      try {
        if (sessionStorage.getItem('roumdoul:lang-switch')) {
          sessionStorage.removeItem('roumdoul:lang-switch');
          if (!window.CSSViewTransitionRule) document.documentElement.classList.add('lang-entering');
        }
      } catch (e) {}
    })();
  </script>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Outfit:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <title>{{ $title ?? 'ROUMDOUL | Premium Digital Services' }}</title>
  {{-- Search results + Facebook/Telegram link previews. A page can pass its own
       ->layoutData(['description' => ..., 'ogImage' => ...]); otherwise the site defaults. --}}
  @php
    $metaTitle = $title ?? 'ROUMDOUL | Premium Digital Services';
    $metaDescription = \Illuminate\Support\Str::limit(trim(strip_tags($description ?? '')) ?: __('ROUMDOUL — premium digital services at fair prices: subscriptions, software licenses, design and Khmer digital invitations. Order online and get your account fast.'), 160);
    $metaImage = $ogImage ?? asset('images/Roumdoul_Logo.png');
  @endphp
  <meta name="description" content="{{ $metaDescription }}" />
  <link rel="canonical" href="{{ url()->current() }}" />
  <meta property="og:type" content="website" />
  <meta property="og:site_name" content="ROUMDOUL" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:title" content="{{ $metaTitle }}" />
  <meta property="og:description" content="{{ $metaDescription }}" />
  <meta property="og:image" content="{{ $metaImage }}" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $metaTitle }}" />
  <meta name="twitter:description" content="{{ $metaDescription }}" />
  <meta name="twitter:image" content="{{ $metaImage }}" />
  <x-js-translations />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>@media (prefers-reduced-motion: no-preference) { @view-transition { navigation: auto; } }</style>
  @livewireStyles
</head>

<body class="bg-plum-50 text-plum-900 transition-colors dark:bg-plum-950 dark:text-plum-100">

  <div id="app" class="flex min-h-screen flex-col">
    @persist('header')
      <livewire:components.header-section />
    @endpersist

    <main class="flex-1" data-page-transition>
      {{ $slot }}
    </main>

    @persist('footer')
      <livewire:components.footer-section />
    @endpersist

    @persist('cart-drawer')
      <livewire:components.cart-drawer />
    @endpersist
  </div>

  <x-loading-overlay />

  @livewireScripts
</body>

</html>
