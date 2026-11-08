<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'products',
    'status' => 'exploring',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 120,
    'template' => 'creative',
    'tech' => [],                   // TODO: tools / stack used
    'url' => null,
    'expertise' => ['creative', 'products'],

    'cover' => ['src' => 'projects/creative-campaigns/cover.jpg', 'ratio' => '4/5', 'label' => 'Campaign visual'],
    'gallery' => [
        ['src' => 'projects/creative-campaigns/gallery-1.jpg', 'ratio' => '4/5', 'label' => 'Campaign visual'],
        ['src' => 'projects/creative-campaigns/gallery-2.jpg', 'ratio' => '1/1', 'label' => 'Campaign visual'],
        ['src' => 'projects/creative-campaigns/gallery-3.jpg', 'ratio' => '9/16', 'label' => 'Story format'],
    ],
    'related' => ['ecommerce-projects', 'video-production'],
];
