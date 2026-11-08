<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'education',
    'status' => 'ongoing',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 60,
    'template' => 'editorial',
    'tech' => ['Microsoft Excel', 'Google Sheets'],
    'url' => null,
    'expertise' => ['education', 'automation'],

    'cover' => ['src' => 'projects/spreadsheet-education/cover.jpg', 'ratio' => '3/2', 'label' => 'Lesson material or class photo'],
    'gallery' => [
        ['src' => 'projects/spreadsheet-education/gallery-1.jpg', 'ratio' => '16/9', 'label' => 'Lesson still'],
        ['src' => 'projects/spreadsheet-education/gallery-2.jpg', 'ratio' => '16/10', 'label' => 'Example system built in class'],
    ],
    'related' => ['google-sheets-automation'],
];
