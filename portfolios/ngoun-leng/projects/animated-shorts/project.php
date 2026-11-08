<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'creative',
    'status' => 'ongoing',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 70,
    'template' => 'creative',
    'tech' => ['Blender'],
    'url' => null,
    'expertise' => ['creative'],

    'cover' => ['src' => 'projects/animated-shorts/cover.jpg', 'ratio' => '21/9', 'label' => 'Animation still'],
    'gallery' => [
        ['src' => 'projects/animated-shorts/gallery-1.jpg', 'ratio' => '16/9', 'label' => 'Animation still'],
        ['src' => 'projects/animated-shorts/gallery-2.jpg', 'ratio' => '16/9', 'label' => 'Animation still'],
        ['src' => 'projects/animated-shorts/gallery-3.jpg', 'ratio' => '16/9', 'label' => 'Animation still'],
        ['src' => 'projects/animated-shorts/gallery-4.jpg', 'ratio' => '16/9', 'label' => 'Behind the scenes'],
    ],
    'related' => ['blender-experiments', 'video-production'],
];
