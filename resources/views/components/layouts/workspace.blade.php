<!doctype html>
<html lang="{{ app()->getLocale() }}">

<head>
  <meta charset="UTF-8" />
  <script>
    (function () {
      var stored = localStorage.getItem('theme');
      document.documentElement.classList.toggle('dark', stored === 'dark');
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
  <x-js-translations />
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>

{{-- Standalone workspace shell for editing one invitation — no storefront header/footer/cart,
     the page component renders its own sidebar. Opened in a new tab from /dashboard. --}}
<body class="bg-plum-50 text-plum-900 transition-colors dark:bg-plum-950 dark:text-plum-100">

  {{ $slot }}

  <x-toasts />

  @livewireScripts
</body>

</html>
