<?php

/*
 * ROUMDOUL brand details shown on guest-facing pages (the shared invitation footer,
 * resources/views/components/invitation-footer.blade.php).
 *
 * Social links come from .env so they can change without a deploy of code. An empty link is
 * simply not shown. Telegram defaults to the shop's chat (the same number the storefront uses).
 */
return [

    'brand' => 'ROUMDOUL',

    // where «CRAFTED BY ROUMDOUL» links to
    'site_url' => env('ROUMDOUL_SITE_URL', env('APP_URL', '/')),

    'social' => [
        'facebook' => env('ROUMDOUL_FACEBOOK_URL', ''),
        'instagram' => env('ROUMDOUL_INSTAGRAM_URL', ''),
        'tiktok' => env('ROUMDOUL_TIKTOK_URL', ''),
        'telegram' => env('ROUMDOUL_TELEGRAM_URL', 'https://t.me/+85515578707'),
    ],

];
