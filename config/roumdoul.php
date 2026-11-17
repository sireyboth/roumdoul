<?php

/*
 * ROUMDOUL brand details shown on guest-facing pages (the shared invitation footer,
 * resources/views/components/invitation-footer.blade.php).
 *
 * Social links can be overridden from .env without a code deploy; the defaults below are the
 * official profiles. An empty link is simply not shown. The «Chat on Telegram» button uses
 * telegram_chat (the shop's phone chat). Keep them in sync with socialLinks in
 * resources/js/modules/config.js (the storefront footer).
 */
return [

    'brand' => 'ROUMDOUL',

    // where «CRAFTED BY ROUMDOUL» links to
    'site_url' => env('ROUMDOUL_SITE_URL', env('APP_URL', '/')),

    'social' => [
        'facebook' => env('ROUMDOUL_FACEBOOK_URL', 'https://www.facebook.com/roumdoulofficial'),
        'instagram' => env('ROUMDOUL_INSTAGRAM_URL', 'https://www.instagram.com/roumd_oul/'),
        'tiktok' => env('ROUMDOUL_TIKTOK_URL', 'https://www.tiktok.com/@roumdoul1'),
        'telegram' => env('ROUMDOUL_TELEGRAM_URL', 'https://t.me/roumdoul_official'),
    ],

    // the «Chat on Telegram» button (same chat as the storefront footer)
    'telegram_chat' => env('ROUMDOUL_TELEGRAM_CHAT_URL', 'https://t.me/+85515578707'),

];
