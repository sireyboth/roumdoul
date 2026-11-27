<?php

/*
|--------------------------------------------------------------------------
| Live preview recipes (the editor's phone preview)
|--------------------------------------------------------------------------
|
| The editor's live preview is a simple wireframe of the REAL template: the same sections in the same
| order and arrangement, drawn with plain shared pieces in the Roumdoul colours. Which sections, in
| which order, comes from the template's RECIPE below, keyed by the template's `view` (the same value
| as the admin row's «view», e.g. 'invitations.templates.wedding.kbach-meas').
|
| A recipe is a list of blocks, top to bottom. Each block is one file in
|   resources/views/livewire/pages/dashboard/previews/blocks/{block}.blade.php
| and the other keys are that block's options — each block file lists its options at the top.
| Blocks: cover, hero, invitation, couple, note, date, countdown, countup, story, programme, gallery,
| venue, guest_notes, rsvp, gift, contact, letters, finale, marquee, music, chat, fields, box, buttons.
|
| Fields the template doesn't use (or the plan doesn't include) are never drawn, so a block can name a
| field the template lacks. Section labels and box names are English, translated with lang/*.json.
|
| NEW TEMPLATE? Add its recipe here (copy the closest one and adjust it to the template's sections).
| Without a recipe the preview uses `default` below.
|
*/

return [

    // Used by any template that has no recipe yet: every field in the editor's form order.
    'default' => [
        ['block' => 'fields', 'label' => 'Who is hosting', 'items' => ['sender_name' => 'lg', 'host_names' => 'md', 'celebrant_name' => 'xl', 'turning_age' => 'lg']],
        ['block' => 'couple', 'photo' => 'circle'],
        ['block' => 'invitation', 'label' => 'Parents', 'parents' => 2, 'guest' => false, 'reception' => false, 'names' => false, 'date' => false],
        ['block' => 'date'],
        ['block' => 'venue', 'map' => false],
        ['block' => 'contact'],
        ['block' => 'guest_notes'],
        ['block' => 'fields', 'label' => 'Invitation wording', 'items' => [
            'headline' => 'lg', 'opening_line' => 'sm', 'message' => 'md', 'love_reasons' => 'sm', 'reception_message' => 'sm',
            'rsvp_message' => 'sm', 'thanks_message' => 'sm', 'apology_message' => 'sm', 'closing_line' => 'lg', 'song_title' => 'sm',
        ]],
        ['block' => 'gift'],
    ],

    'recipes' => [

        // ---------------------------------------------------------------- weddings (templates/wedding/)

        'invitations.templates.wedding.eternal-vows' => [
            ['block' => 'cover', 'initials' => true, 'photo' => 'arch'],
            ['block' => 'hero', 'photo' => 'arch'],
            ['block' => 'invitation', 'headline' => true, 'parents' => 3],
            ['block' => 'couple', 'photo' => 'oval'],
            ['block' => 'note'],
            ['block' => 'date', 'box' => 'Clock'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 3, 'count' => 6],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'arch'],
        ],

        'invitations.templates.wedding.blossom-swing' => [
            ['block' => 'cover'],
            ['block' => 'invitation', 'parents' => 2, 'names' => 'stacked'],
            ['block' => 'couple', 'photo' => 'oval'],
            ['block' => 'note'],
            ['block' => 'date'],
            ['block' => 'story'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 2, 'count' => 5],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'arch', 'names' => false],
        ],

        'invitations.templates.wedding.front-page' => [
            ['block' => 'cover', 'photo' => 'wide', 'headline' => false, 'venue' => true, 'initials' => 'bottom'],
            ['block' => 'marquee'],
            ['block' => 'invitation', 'parents' => 2, 'date' => false],
            ['block' => 'note', 'label' => 'Pull quote'],
            ['block' => 'date', 'box' => 'Scratch card'],
            ['block' => 'gallery', 'label' => 'Photo strips', 'strip' => true, 'count' => 4],
            ['block' => 'box', 'label' => 'Scratch cards', 'key' => 'photo_gallery'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'story', 'label' => 'Our places', 'style' => 'Map with pins'],
            ['block' => 'programme'],
            ['block' => 'venue', 'dress_code' => true],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'letters', 'side' => true],
            ['block' => 'contact', 'side' => true],
            ['block' => 'finale', 'closing' => false, 'date' => true],
        ],

        'invitations.templates.wedding.golden-hour' => [
            ['block' => 'cover', 'photo' => 'wide'],
            ['block' => 'hero', 'photo' => 'wide'],
            ['block' => 'marquee'],
            ['block' => 'invitation', 'parents' => 3],
            ['block' => 'couple', 'photo' => 'portrait'],
            ['block' => 'note'],
            ['block' => 'date'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 2, 'count' => 6],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'wide'],
        ],

        'invitations.templates.wedding.kbach-chhouk' => [
            ['block' => 'cover', 'photo' => 'arch'],
            ['block' => 'hero', 'photo' => 'arch', 'headline' => true],
            ['block' => 'invitation', 'parents' => 3],
            ['block' => 'couple', 'photo' => 'arch'],
            ['block' => 'note'],
            ['block' => 'date', 'box' => 'Lunar card and calendar'],
            ['block' => 'programme'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'gallery', 'cols' => 2],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'portrait'],
        ],

        'invitations.templates.wedding.kbach-meas' => [
            ['block' => 'cover', 'photo' => 'arch'],
            ['block' => 'hero', 'photo' => 'arch', 'khmer_date' => true],
            ['block' => 'invitation', 'parents' => 3],
            ['block' => 'couple', 'photo' => 'arch'],
            ['block' => 'note'],
            ['block' => 'date', 'box' => 'Weekday · day · month'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 2],
            ['block' => 'venue'],
            ['block' => 'guest_notes', 'side' => false],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'gift'],
            ['block' => 'contact'],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'wide'],
        ],

        'invitations.templates.wedding.love-bubble' => [
            ['block' => 'cover', 'photo' => 'wide'],
            ['block' => 'hero', 'photo' => 'portrait', 'headline' => true],
            ['block' => 'marquee'],
            ['block' => 'chat'],
            ['block' => 'couple', 'photo' => 'portrait', 'parents' => true],
            ['block' => 'date', 'box' => 'Ticket'],
            ['block' => 'music'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 2],
            ['block' => 'venue'],
            ['block' => 'guest_notes', 'side' => false],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'contact'],
            ['block' => 'box', 'label' => 'Sticker board', 'key' => 'photo_gallery'],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'circle'],
        ],

        'invitations.templates.wedding.memory-hall' => [
            ['block' => 'cover'],
            ['block' => 'hero', 'photo' => 'wide', 'headline' => true, 'venue' => false],
            ['block' => 'invitation', 'parents' => 2, 'names' => false],
            ['block' => 'couple', 'photo' => 'portrait'],
            ['block' => 'note'],
            ['block' => 'date'],
            ['block' => 'story'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 3, 'count' => 6],
            ['block' => 'venue'],
            ['block' => 'guest_notes', 'side' => false],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'gift'],
            ['block' => 'contact'],
            ['block' => 'letters'],
            ['block' => 'finale', 'names' => false],
        ],

        'invitations.templates.wedding.paper-bloom' => [
            ['block' => 'cover', 'photo' => 'wide'],
            ['block' => 'hero', 'photo' => 'arch'],
            ['block' => 'invitation', 'parents' => 3],
            ['block' => 'couple', 'photo' => 'portrait'],
            ['block' => 'note'],
            ['block' => 'date'],
            ['block' => 'story', 'style' => 'Photo strip'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'cols' => 3, 'count' => 6],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'portrait'],
        ],

        'invitations.templates.wedding.vellum-garden' => [
            ['block' => 'cover', 'headline' => false, 'date' => false, 'initials' => 'bottom'],
            ['block' => 'hero', 'photo' => 'arch'],
            ['block' => 'invitation', 'parents' => 2, 'reception' => false],
            ['block' => 'note'],
            ['block' => 'date'],
            ['block' => 'story', 'style' => 'Cards'],
            ['block' => 'story', 'label' => 'Our places', 'style' => 'Map with pins'],
            ['block' => 'programme'],
            ['block' => 'fields', 'label' => 'Reception card', 'items' => ['@guest' => 'md', 'reception_message' => 'sm']],
            ['block' => 'box', 'label' => 'Scratch cards', 'key' => 'photo_gallery'],
            ['block' => 'gallery', 'cols' => 2, 'count' => 5],
            ['block' => 'venue'],
            ['block' => 'fields', 'label' => 'Dress code', 'items' => ['dress_code' => 'md']],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true, 'cta' => false],
            ['block' => 'letters', 'side' => true],
            ['block' => 'finale', 'closing' => false],
        ],

        // ---------------------------------------------------------------- Khmer traditional weddings

        'invitations.templates.khmer-traditional-wedding' => [
            ['block' => 'cover', 'headline' => false, 'names' => false, 'venue' => true],
            ['block' => 'invitation', 'label' => 'Top of the invitation', 'parents' => 2, 'reception' => false, 'names' => 'side', 'message' => true],
            ['block' => 'couple', 'photo' => 'arch', 'full_photo' => true, 'bride_first' => true],
            ['block' => 'story'],
            ['block' => 'countdown'],
            ['block' => 'programme'],
            ['block' => 'fields', 'label' => 'Reception card', 'items' => ['@guest' => 'md', 'reception_message' => 'sm']],
            ['block' => 'gallery', 'cols' => 1, 'count' => 2],
            ['block' => 'venue'],
            ['block' => 'gift'],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'contact', 'cta' => false],
            ['block' => 'letters'],
            ['block' => 'finale', 'closing' => false, 'date' => true],
        ],

        'invitations.templates.khmer-traditional-2' => [
            ['block' => 'cover', 'headline' => false, 'names' => false, 'date' => false, 'initials' => true],
            ['block' => 'invitation', 'label' => 'Top of the invitation', 'parents' => 2, 'reception' => false, 'names' => 'columns', 'message' => true],
            ['block' => 'couple', 'photo' => 'arch', 'full_photo' => true, 'bride_first' => true],
            ['block' => 'story'],
            ['block' => 'countdown'],
            ['block' => 'programme'],
            ['block' => 'fields', 'label' => 'Reception card', 'items' => ['@guest' => 'md', 'reception_message' => 'sm']],
            ['block' => 'gallery', 'cols' => 1, 'count' => 2],
            ['block' => 'venue'],
            ['block' => 'gift'],
            ['block' => 'rsvp', 'stacked' => true],
            ['block' => 'contact', 'cta' => false],
            ['block' => 'letters'],
            ['block' => 'finale', 'closing' => false, 'date' => true],
        ],

        // ---------------------------------------------------------------- ceremony

        'invitations.templates.kak-thern' => [
            ['block' => 'hero', 'headline' => true, 'opening' => true, 'photo' => 'portrait', 'names' => false, 'guest' => true, 'venue' => false],
            ['block' => 'invitation', 'parents' => false, 'guest' => false, 'reception' => false, 'names' => false, 'message' => true, 'hosts' => true],
            ['block' => 'programme'],
            ['block' => 'venue'],
            ['block' => 'gift', 'label' => 'Donation'],
            ['block' => 'gallery', 'cols' => 1, 'count' => 2],
            ['block' => 'rsvp'],
            ['block' => 'contact', 'cta' => true],
            ['block' => 'finale', 'label' => 'Blessing', 'photo' => 'circle', 'photo_key' => 'cover_image', 'closing' => false, 'thanks' => true, 'names' => false],
            ['block' => 'countdown'],
        ],

        // ---------------------------------------------------------------- other events

        'invitations.templates.private-screening' => [
            ['block' => 'gallery', 'label' => 'Opening photos', 'cols' => 3, 'count' => 3],
            ['block' => 'hero', 'photo' => false, 'names' => 'stacked', 'venue' => false],
            ['block' => 'gallery', 'label' => 'Photo carousel', 'strip' => true, 'count' => 5],
            ['block' => 'gallery', 'label' => 'Gallery', 'cols' => 2, 'count' => 5],
            ['block' => 'note', 'label' => 'Letter', 'signature' => true],
            ['block' => 'date', 'box' => 'Big day number', 'countdown' => false],
            ['block' => 'fields', 'label' => 'Details', 'items' => ['event_date' => 'sm', 'venue_name' => 'sm', 'venue_address' => 'sm']],
            ['block' => 'programme'],
            ['block' => 'venue'],
            ['block' => 'rsvp', 'buttons' => 3, 'always' => true],
            ['block' => 'finale', 'closing' => false],
        ],

        'invitations.templates.admire' => [
            ['block' => 'fields', 'label' => 'First screen', 'items' => ['@guest' => 'sm', 'headline' => 'lg']],
            ['block' => 'box', 'label' => 'Star game'],
            ['block' => 'gallery', 'label' => 'Floating photos', 'cols' => 3, 'count' => 6],
            ['block' => 'note', 'signature' => true, 'cta' => true],
        ],

        'invitations.templates.date-asking' => [
            ['block' => 'fields', 'label' => 'The card', 'items' => [
                '@guest' => 'sm', 'cover_image' => 'square', 'headline' => 'xl', 'message' => 'sm', 'sender_name' => 'sm', 'event_date' => 'sm',
            ]],
            ['block' => 'countdown'],
            ['block' => 'venue', 'map' => false],
            ['block' => 'buttons', 'labels' => ['Yes', 'No']],
            ['block' => 'fields', 'label' => 'Your own button', 'items' => ['cta_label' => 'sm']],
        ],

        'invitations.templates.birthday.chrome-bow' => [
            ['block' => 'fields', 'label' => 'Cover', 'items' => [
                '@guest' => 'sm', 'cover_image' => 'circle', 'turning_age' => '2xl', 'headline' => 'sm', 'celebrant_name' => 'xl',
            ]],
            ['block' => 'fields', 'label' => 'Top of the invitation', 'items' => [
                'headline' => 'sm', 'turning_age' => '2xl', 'celebrant_name' => 'xl', 'event_date' => 'sm',
            ]],
            ['block' => 'fields', 'label' => 'Ticket', 'items' => [
                '@guest' => 'sm', 'reception_message' => 'sm', 'celebrant_name' => 'md', 'event_date' => 'sm',
                'khmer_date' => 'xs', 'venue_name' => 'sm', 'sender_name' => 'sm',
            ]],
            ['block' => 'note', 'signature' => true],
            ['block' => 'box', 'label' => 'Cake with candles', 'key' => 'turning_age'],
            ['block' => 'date'],
            ['block' => 'story'],
            ['block' => 'programme'],
            ['block' => 'gallery', 'label' => 'Mood board', 'cols' => 2],
            ['block' => 'venue'],
            ['block' => 'guest_notes'],
            ['block' => 'rsvp'],
            ['block' => 'gift'],
            ['block' => 'contact', 'side' => true],
            ['block' => 'letters'],
            ['block' => 'finale', 'photo' => 'portrait', 'names' => false],
        ],

        'invitations.templates.anniversary-category.river-love' => [
            ['block' => 'fields', 'label' => 'Cover', 'items' => [
                'together_since' => 'xs', 'sender_name' => 'sm', '@guest' => 'sm', 'headline' => 'xl', 'song_title' => 'xs',
            ]],
            ['block' => 'fields', 'label' => 'Letter', 'items' => ['together_since' => 'sm', 'message' => 'md', 'sender_name' => 'sm']],
            ['block' => 'gallery', 'label' => 'Scratch cards', 'cols' => 2, 'count' => 4],
            ['block' => 'gallery', 'label' => 'Film strips', 'strip' => true, 'count' => 5],
            ['block' => 'fields', 'label' => 'Paper boats', 'items' => ['love_reasons' => 'sm']],
            ['block' => 'story', 'label' => 'Our places', 'style' => 'Map with pins'],
            ['block' => 'music'],
            ['block' => 'countup'],
            ['block' => 'finale', 'names' => false, 'thanks' => true],
        ],

        'invitations.templates.anniversary-category.rose-bloom' => [
            ['block' => 'fields', 'label' => 'Cover', 'items' => ['headline' => 'lg', '@guest' => 'sm']],
            ['block' => 'fields', 'label' => 'Poster', 'items' => [
                'sender_name' => 'sm', '@guest' => 'xl', 'cover_image' => 'wide', 'together_since' => 'md', 'venue_name' => 'sm',
            ]],
            ['block' => 'fields', 'label' => 'Letter', 'items' => ['message' => 'md', 'sender_name' => 'sm']],
            ['block' => 'music'],
            ['block' => 'gallery', 'label' => 'Scratch cards', 'cols' => 2, 'count' => 4],
            ['block' => 'gallery', 'label' => 'Polaroids', 'strip' => true, 'count' => 5],
            ['block' => 'fields', 'label' => 'Reasons I love you', 'items' => ['love_reasons' => 'sm']],
            ['block' => 'story', 'label' => 'Our places', 'style' => 'Map with pins'],
            ['block' => 'buttons', 'labels' => ['Yes', 'Yes, obviously']],
            ['block' => 'finale', 'names' => false, 'thanks' => true],
        ],
    ],
];
