<?php

/*
|--------------------------------------------------------------------------
| Ngoun Leng — portfolio settings
|--------------------------------------------------------------------------
| Language-neutral facts about this portfolio. Words that change per language
| live in content/en.php and content/km.php. Set a value to null to hide it.
| Live at /portfolio/ngoun-leng
*/

return [

    'name' => [
        'en' => 'Ngoun Leng',
        // TODO: confirm the Khmer spelling of your name, then put it here.
        'km' => 'Ngoun Leng',
    ],

    // Used by the monogram mark and favicon.
    'initials' => 'NL',

    'locales' => ['en', 'km'],
    'default_locale' => 'en',

    // Optional colour overrides for this portfolio (otherwise the shared palette is used).
    'theme' => [
        'light' => ['accent' => '#A34C3E'],
        'dark' => ['accent' => '#C77B68'],
    ],

    // Images are paths inside this folder's media/ directory.
    'portrait' => [
        'src' => 'portrait/portrait.jpg',      // 4:5, at least 1600 × 2000 px
        'secondary' => 'portrait/working.jpg', // 3:4, at least 1200 × 1600 px — used on About
        // Where to keep in frame when the photo is cropped: 'x% y%' (0% 0% = top-left, 50% 50% = centre).
        'focus' => '56% 40%',
    ],

    'seo' => [
        // 1200 × 630 px. Shown when a page is shared on social media / messaging apps.
        'og_image' => 'brand/og-image.jpg',
        'site_url' => null, // e.g. https://ngounleng.com once a custom domain points here
    ],

    'contact' => [
        // Shown publicly on the contact page. Leave null to show the form only.
        'email' => null,
        // Where new form messages are emailed (needs a real MAIL_MAILER in .env).
        // Messages are always saved in the admin panel: Portfolio → Messages.
        'notify_email' => null,
    ],

    // Fill in the URLs you want to show; entries with a null url are hidden.
    'socials' => [
        ['label' => 'LinkedIn', 'url' => null, 'group' => 'professional'],
        ['label' => 'GitHub', 'url' => null, 'group' => 'professional'],
        ['label' => 'YouTube', 'url' => null, 'group' => 'social'],
        ['label' => 'Facebook', 'url' => null, 'group' => 'social'],
        ['label' => 'TikTok', 'url' => null, 'group' => 'social'],
        ['label' => 'Telegram', 'url' => null, 'group' => 'social'],
    ],

    // The four work categories, in display order. Titles/descriptions are in content/{locale}.php.
    'disciplines' => ['technology', 'education', 'creative', 'products'],

    // Expertise groups, in display order (content/{locale}.php → expertise.groups).
    'expertise' => ['technology', 'automation', 'education', 'creative', 'products'],

    // Projects shown on the home page, in order (folder names in projects/).
    'featured' => ['roumdoul', 'digital-invitations', 'spreadsheet-education', 'animated-shorts'],

    // When you last updated the "Current exploration" list (YYYY-MM-DD).
    'now_updated' => '2026-11-07',
];
