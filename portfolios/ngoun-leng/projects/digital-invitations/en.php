<?php

// Case study — English. Empty sections are hidden from visitors. Markdown allowed.

return [
    'title' => 'Digital Invitation Platform',
    'summary' => 'Online invitations where every guest gets their own personal link — from Khmer weddings to playful one-off messages.',
    'intro' => 'A platform for modern digital invitations: pick a design, fill in the details once, and send each guest a link addressed to them by name.',

    'alt' => [
        'cover' => 'An invitation template open on screen',
    ],

    'overview' => "The Digital Invitation Platform lets a customer choose an invitation design, fill in their event details from their own dashboard, and generate a separate shareable link for every guest. Each link opens the invitation personalised with that guest's name.\n\nIt is sold through the same online shop as my other digital services, with plans that set how many guests an invitation can have and how long it stays online.",

    'challenge' => 'Invitations are usually shared as one image or one generic link sent to everyone. That works, but it loses the personal touch of a printed invitation with a name on it — and it gives the host no idea who has actually opened it or replied.',

    'idea' => 'Keep one invitation design, but give every guest their own link. The guest sees their name; the host sees who viewed it and who answered.',

    'approach' => "I built the full loop first with a single simple template — purchase, fill in details, add guests, share links — before investing in more designs. Once that loop worked end to end, new templates could be added on top of it without touching the core.\n\nEvery optional part of an invitation (map, countdown, schedule, gallery, music, RSVP) became a field in one shared catalogue. A template declares which fields it uses, and a pricing plan declares which of them it unlocks. One mechanism does both jobs.",

    'solution' => "A customer buys a plan, and once the order is marked as paid the invitation is provisioned in their dashboard. There they fill in a form that is generated from the fields their template uses, upload photos, and add guests up to the plan's limit. Each guest receives a unique link.\n\nOn the admin side, a new template — including its pricing plans — can be created on one screen without any separate product setup.",

    'features' => [
        ['title' => 'A personal link for every guest', 'body' => 'Each guest gets their own URL. The invitation greets them by name, and the host can see when it was viewed.', 'image' => 'projects/digital-invitations/feature-links.jpg', 'ratio' => '9/16'],
        ['title' => 'A workspace for the host', 'body' => 'Details, media, programme, guests and responses each have their own section, with in-context help that shows where each field appears on the invitation.', 'image' => 'projects/digital-invitations/feature-workspace.jpg', 'ratio' => '16/10'],
        ['title' => 'RSVP, collected in one place', 'body' => 'Guests answer yes or no with an optional note, and the host sees every reply in their dashboard.', 'image' => 'projects/digital-invitations/feature-rsvp.jpg', 'ratio' => '16/10'],
        ['title' => 'Designs with their own character', 'body' => 'Templates range from a playful date invitation with a runaway “No” button to a traditional Khmer wedding with a dedicated desktop layout.', 'image' => 'projects/digital-invitations/feature-templates.jpg', 'ratio' => '16/10'],
    ],

    'technical' => "- **Laravel** application with **Livewire** for the customer dashboard and **Filament** for the admin panel.\n- A single **field catalogue** drives the dashboard form, the template, and plan-based unlocking.\n- Invitations are only created when an order reaches a paid status, never at checkout, and that step is idempotent.\n- Adding guests runs inside a database transaction with a row lock, so double submissions cannot exceed the plan limit.\n- Photos are stored in **Cloudflare R2**; the database only keeps file paths. Video is embedded from links rather than uploaded.",

    // TODO: real results only — e.g. number of invitations created, if you want to share it.
    'outcome' => null,
    // TODO: what you learned, and what you would do differently.
    'reflection' => null,

    'gallery_captions' => [],
];
