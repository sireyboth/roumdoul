<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="/favicon.png" />
  <title>{{ $title }} | ROUMDOUL</title>
  @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center gap-6 bg-plum-50 px-4 text-center text-plum-900 dark:bg-plum-950 dark:text-plum-100">
  <a href="/" class="flex items-center gap-2.5">
    <img src="/images/Roumdoul_Logo.png" alt="ROUMDOUL" class="h-12 w-12 rounded-full object-contain" />
    <span class="text-xl font-extrabold tracking-tight text-brand-800 dark:text-white">ROUMDOUL</span>
  </a>

  <div class="flex flex-col items-center gap-3">
    <span class="text-6xl font-black text-brand-700 dark:text-brand-400">{{ $code }}</span>
    <h1 class="text-xl font-bold text-plum-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
    <p class="max-w-md text-sm leading-relaxed text-plum-600 dark:text-plum-400">{{ $message }}</p>
  </div>

  <a href="/" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition-colors hover:bg-brand-700">
    ត្រឡប់ទៅទំព័រដើម / Back to homepage
  </a>
</body>
</html>
