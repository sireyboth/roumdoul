<?php

/*
|--------------------------------------------------------------------------
| Personal portfolios
|--------------------------------------------------------------------------
|
| Every folder inside `portfolios/` (project root) is one personal portfolio,
| served at /portfolio/{folder-name}/{locale}. Folders starting with "_" or
| "." are ignored. See portfolios/README.md for the content format.
|
*/

return [

    // Where portfolio content folders live.
    'path' => base_path('portfolios'),

    // URL prefix: /portfolio/{slug}/...
    'route_prefix' => 'portfolio',

    // Optimized images are published to public/{public_dir}/{slug}/ by
    // `php artisan portfolio:media`.
    'public_dir' => 'portfolios',

    // Responsive widths generated for every raster image.
    'image_widths' => [480, 960, 1600, 2400],

    // Languages the engine knows how to render. A portfolio picks which of
    // these it actually offers in its own portfolio.php.
    'locales' => [
        'en' => [
            'native' => 'English',
            'short' => 'EN',
            'hreflang' => 'en',
            'og' => 'en_US',
        ],
        'km' => [
            'native' => 'ខ្មែរ',
            'short' => 'ខ្មែរ',
            'hreflang' => 'km',
            'og' => 'km_KH',
        ],
    ],

    // Show dashed "add this content" markers for unfinished sections, missing
    // timeline entries and draft notes. Defaults to on while APP_DEBUG is on,
    // so visitors in production never see editing notes.
    'editor_hints' => env('PORTFOLIO_EDITOR_HINTS', env('APP_DEBUG', false)),

];
