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
        'tiktok' => env('ROUMDOUL_TIKTOK_URL', 'https://www.tiktok.com/@roumd_oul'),
        'telegram' => env('ROUMDOUL_TELEGRAM_URL', 'https://t.me/roumdoul_official'),
    ],

    // the «Chat on Telegram» button (same chat as the storefront footer)
    'telegram_chat' => env('ROUMDOUL_TELEGRAM_CHAT_URL', 'https://t.me/+85515578707'),

    // How customers pay (manual for now): the KHQR shown on the order confirmation page and on a
    // pending order in the dashboard (resources/views/components/shop/payment-modal.blade.php).
    // One tab per method; the first one is the default tab (ABA first, it's the one we prefer).
    // Put each QR picture at public/{qr_image}; until it exists, the box shows the name + account ID only.
    // The wall-clock zone customers type invitation times in (the dashboard's datetime-local input
    // has no zone). App\Support\Invitations\EventDate reads every stored event_date in this zone, so
    // a countdown ends at the time the customer entered, whatever zone the guest's phone is in.
    // Not app.timezone: that one stays UTC for the database timestamps.
    'event_timezone' => env('ROUMDOUL_EVENT_TIMEZONE', 'Asia/Phnom_Penh'),

    'payment' => [
        'methods' => [
            'aba' => [
                'label' => 'ABA',
                'qr_image' => env('ROUMDOUL_PAY_ABA_QR_IMAGE', 'images/payment/aba.png'),
                'account_name' => env('ROUMDOUL_PAY_ABA_ACCOUNT_NAME', 'NGOUNLENG THAI'),
                'account_id' => env('ROUMDOUL_PAY_ABA_ACCOUNT_ID', '001 788 766'),
            ],
            'wing' => [
                'label' => 'Wing',
                'qr_image' => env('ROUMDOUL_PAY_WING_QR_IMAGE', 'images/payment/khqr.png'),
                'account_name' => env('ROUMDOUL_PAY_WING_ACCOUNT_NAME', 'NGOUNLENG THAI'),
                'account_id' => env('ROUMDOUL_PAY_WING_ACCOUNT_ID', '102 492 369'),
            ],
        ],
    ],

];
