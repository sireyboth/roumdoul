<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'technology',
    'status' => 'in_development',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 20,
    'template' => 'system',
    'tech' => [],                   // TODO: tools / stack used
    'url' => null,
    'expertise' => ['technology', 'products'],

    'cover' => ['src' => 'projects/qr-table-ordering/cover.jpg', 'ratio' => '4/5', 'label' => 'Phone screenshot — menu & order flow'],
    'gallery' => [
        ['src' => 'projects/qr-table-ordering/gallery-1.jpg', 'ratio' => '16/10', 'label' => 'Screenshot'],
        ['src' => 'projects/qr-table-ordering/gallery-2.jpg', 'ratio' => '4/5', 'label' => 'Mobile screenshot'],
    ],
    'related' => ['roumdoul', 'sales-inventory-system'],
];
