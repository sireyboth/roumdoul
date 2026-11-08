<?php

/*
| Language-neutral facts about this project. Text lives in en.php / km.php.
| status: live | in_development | ongoing | exploring | concept | completed | paused | archived
| template (case-study layout): system | creative | editorial
*/

return [
    'category' => 'technology',
    'status' => 'in_development',   // TODO: confirm
    'date' => null,                 // TODO: 'YYYY' or 'YYYY-MM' when it started
    'date_end' => null,             // 'present', 'YYYY', 'YYYY-MM' or null
    'order' => 10,
    'template' => 'system',
    'tech' => [],                   // TODO: e.g. ['Laravel', 'MySQL']
    'url' => null,
    'expertise' => ['technology', 'automation', 'products'],

    // Images live in media/projects/roumdoul/. Missing files show a placeholder.
    'cover' => ['src' => 'projects/roumdoul/cover.jpg', 'ratio' => '16/10', 'label' => 'Product screenshot — dashboard'],
    'gallery' => [
        ['src' => 'projects/roumdoul/screen-1.jpg', 'ratio' => '16/10', 'label' => 'Screenshot'],
        ['src' => 'projects/roumdoul/screen-2.jpg', 'ratio' => '16/10', 'label' => 'Screenshot'],
        ['src' => 'projects/roumdoul/screen-3.jpg', 'ratio' => '4/5', 'label' => 'Mobile screenshot'],
    ],
    'related' => ['sales-inventory-system', 'qr-table-ordering'],
];
