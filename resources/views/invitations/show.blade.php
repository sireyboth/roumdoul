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
  <title>{{ $fields['headline'] ?? 'You have a special invitation' }} | ROUMDOUL</title>
  @vite(['resources/css/app.css'])
  @livewireStyles
</head>

<body class="bg-plum-50 text-plum-900 dark:bg-plum-950 dark:text-plum-100">
  @include($view, ['recipientName' => $recipientName, 'fields' => $fields, 'invitation' => $invitation])

  @livewireScripts
</body>

</html>
