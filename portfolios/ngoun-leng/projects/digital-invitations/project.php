<?php

// Facts about this project. Text lives in en.php / km.php. See projects/roumdoul/project.php for all options.

return [
    'category' => 'products',
    'status' => 'live',            // TODO: confirm it is live for real customers
    'date' => '2026-08',
    'date_end' => 'present',
    'order' => 100,
    'template' => 'system',
    'tech' => ['Laravel', 'Livewire', 'Filament', 'Tailwind CSS', 'Alpine.js', 'Cloudflare R2'],
    'url' => null,                 // TODO: public link to a demo template, if you want one shown
    'expertise' => ['technology', 'products', 'creative'],

    // Real screenshots can be taken from the demo pages: /templates/{slug}/demo
    'cover' => ['src' => 'projects/digital-invitations/cover.jpg', 'ratio' => '16/10', 'label' => 'Screenshot — an invitation template'],
    'gallery' => [
        ['src' => 'projects/digital-invitations/gallery-1.jpg', 'ratio' => '9/16', 'label' => 'Phone screenshot — guest view'],
        ['src' => 'projects/digital-invitations/gallery-2.jpg', 'ratio' => '16/10', 'label' => 'Screenshot — customer workspace'],
        ['src' => 'projects/digital-invitations/gallery-3.jpg', 'ratio' => '16/10', 'label' => 'Screenshot — desktop wedding layout'],
        ['src' => 'projects/digital-invitations/gallery-4.jpg', 'ratio' => '9/16', 'label' => 'Phone screenshot — RSVP'],
    ],
    'related' => ['ecommerce-projects', 'roumdoul'],
];
