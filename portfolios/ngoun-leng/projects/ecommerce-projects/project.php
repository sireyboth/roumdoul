<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'products',
    'status' => 'exploring',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 110,
    'template' => 'editorial',
    'tech' => [],                   // TODO: tools / stack used
    'url' => null,
    'expertise' => ['products', 'creative'],

    'cover' => ['src' => 'projects/ecommerce-projects/cover.jpg', 'ratio' => '4/3', 'label' => 'Product page or concept'],
    'gallery' => [
        ['src' => 'projects/ecommerce-projects/gallery-1.jpg', 'ratio' => '16/10', 'label' => 'Concept'],
        ['src' => 'projects/ecommerce-projects/gallery-2.jpg', 'ratio' => '4/5', 'label' => 'Product presentation'],
    ],
    'related' => ['creative-campaigns', 'digital-invitations'],
];
