<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'creative',
    'status' => 'ongoing',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 90,
    'template' => 'creative',
    'tech' => ['DaVinci Resolve'],
    'url' => null,
    'expertise' => ['creative', 'education'],

    'cover' => ['src' => 'projects/video-production/cover.jpg', 'ratio' => '16/9', 'label' => 'Video still'],
    'gallery' => [
        ['src' => 'projects/video-production/gallery-1.jpg', 'ratio' => '16/9', 'label' => 'Video still'],
        ['src' => 'projects/video-production/gallery-2.jpg', 'ratio' => '16/9', 'label' => 'Edit timeline'],
    ],
    'related' => ['animated-shorts'],
];
