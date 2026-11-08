<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'technology',
    'status' => 'in_development',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 30,
    'template' => 'system',
    'tech' => [],                   // TODO: tools / stack used
    'url' => null,
    'expertise' => ['technology', 'automation'],

    'cover' => ['src' => 'projects/sales-inventory-system/cover.jpg', 'ratio' => '16/10', 'label' => 'Product screenshot — sales or stock view'],
    'gallery' => [
        ['src' => 'projects/sales-inventory-system/gallery-1.jpg', 'ratio' => '16/10', 'label' => 'Screenshot'],
        ['src' => 'projects/sales-inventory-system/gallery-2.jpg', 'ratio' => '16/10', 'label' => 'Screenshot'],
    ],
    'related' => ['roumdoul', 'google-sheets-automation'],
];
