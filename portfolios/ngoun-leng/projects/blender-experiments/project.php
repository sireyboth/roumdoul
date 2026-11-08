<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'creative',
    'status' => 'exploring',   // TODO: confirm
    'date' => null,           // TODO: 'YYYY' or 'YYYY-MM'
    'date_end' => null,
    'order' => 80,
    'template' => 'creative',
    'tech' => ['Blender'],
    'url' => null,
    'expertise' => ['creative'],

    'cover' => ['src' => 'projects/blender-experiments/cover.jpg', 'ratio' => '1/1', 'label' => 'Blender render'],
    'gallery' => [
        ['src' => 'projects/blender-experiments/gallery-1.jpg', 'ratio' => '1/1', 'label' => 'Render'],
        ['src' => 'projects/blender-experiments/gallery-2.jpg', 'ratio' => '4/5', 'label' => 'Render'],
        ['src' => 'projects/blender-experiments/gallery-3.jpg', 'ratio' => '16/9', 'label' => 'Render'],
    ],
    'related' => ['animated-shorts', 'video-production'],
];
