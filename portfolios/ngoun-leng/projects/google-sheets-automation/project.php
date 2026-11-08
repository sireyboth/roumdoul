<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'technology',
    'status' => 'ongoing',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 40,
    'template' => 'system',
    'tech' => ['Google Sheets', 'Google Apps Script'],
    'url' => null,
    'expertise' => ['automation', 'technology'],

    'cover' => ['src' => 'projects/google-sheets-automation/cover.jpg', 'ratio' => '3/2', 'label' => 'Spreadsheet screenshot — a working system'],
    'gallery' => [
        ['src' => 'projects/google-sheets-automation/gallery-1.jpg', 'ratio' => '16/10', 'label' => 'Screenshot — attendance'],
        ['src' => 'projects/google-sheets-automation/gallery-2.jpg', 'ratio' => '16/10', 'label' => 'Screenshot — report'],
    ],
    'related' => ['spreadsheet-education', 'sales-inventory-system'],
];
