<!doctype html>
<html lang="en">

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
  <link rel="preconnect" href="https://translate.google.com" />
  <link rel="preconnect" href="https://translate.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&display=swap"
    rel="stylesheet" />
  <title>{{ $title ?? 'JingLong Security' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>

<body class="bg-white text-gray-900 transition-colors dark:bg-gray-950 dark:text-gray-100">
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
  </div>

  @livewireScripts
</body>

</html>
