<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:ital,wght@0,100..700;1,100..700&family=Outfit:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />

  @php
    $ogTitle = $fields['headline'] ?? ($recipientName ? "A special invitation for {$recipientName}" : 'You have a special invitation');
    $ogDescription = $fields['message'] ?? 'Tap to see what it is! 💌';
    $ogImage = ! empty($fields['cover_image'])
      ? \Illuminate\Support\Facades\Storage::disk('s3')->url($fields['cover_image'])
      : asset('images/Roumdoul_Logo.png');
  @endphp

  <title>{{ $ogTitle }} | ROUMDOUL</title>

  {{-- Open Graph / Telegram, WhatsApp, Messenger, etc. link preview cards --}}
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:title" content="{{ $ogTitle }}" />
  <meta property="og:description" content="{{ $ogDescription }}" />
  <meta property="og:image" content="{{ $ogImage }}" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="{{ $ogTitle }}" />
  <meta name="twitter:description" content="{{ $ogDescription }}" />
  <meta name="twitter:image" content="{{ $ogImage }}" />

  @vite(['resources/css/app.css'])
  @livewireStyles
</head>

<body class="bg-plum-50 text-plum-900 dark:bg-plum-950 dark:text-plum-100">
  @include($view, ['recipientName' => $recipientName, 'fields' => $fields, 'invitation' => $invitation])

  @livewireScripts
</body>

</html>
