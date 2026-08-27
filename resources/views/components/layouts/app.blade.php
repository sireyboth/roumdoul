<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <script>
    (function () {
      var stored = localStorage.getItem('theme');
      document.documentElement.classList.toggle('dark', stored === 'dark');
      if (localStorage.getItem('lang') === 'en') {
        document.documentElement.classList.add('translating');
      }
    })();
  </script>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="preconnect" href="https://translate.google.com" />
  <link rel="preconnect" href="https://translate.googleapis.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Outfit:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <title>{{ $title ?? 'ROUMDOUL | Premium Digital Services' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>

<body class="bg-plum-50 text-plum-900 transition-colors dark:bg-plum-950 dark:text-plum-100">
  {{-- Shown only while localStorage lang=en is being re-applied (see lang.js); avoids a Khmer-then-English flash on load/navigation. --}}
  <div class="translate-loader fixed inset-0 z-9999 items-center justify-center bg-plum-50 dark:bg-plum-950">
    <div class="h-8 w-8 animate-spin rounded-full border-2 border-brand-200 border-t-brand-600 dark:border-plum-700 dark:border-t-brand-400"></div>
  </div>

  <div id="app" class="flex min-h-screen flex-col">
    @persist('header')
      <livewire:components.header-section />
    @endpersist

    <main class="flex-1">
      {{ $slot }}
    </main>

    @persist('footer')
      <livewire:components.footer-section />
    @endpersist

    @persist('cart-drawer')
      <livewire:components.cart-drawer />
    @endpersist
  </div>

  @livewireScripts
</body>

</html>
