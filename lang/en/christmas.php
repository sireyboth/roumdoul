<?php

/*
 * Christmas party invitation — every word of the guest page and of its dashboard editor.
 * This template is English-only: always read these with the locale forced to 'en'
 * (App\Support\Invitations\ChristmasTemplate::t()), whatever language the site is in.
 */

return [
    'default_title' => 'Christmas Party',

    // guest page
    'page' => [
        'dear' => 'Dear :name,',
        'open' => 'Open Invitation',
        'back' => 'Back to cover',
        'invited' => 'You are invited to',
        'hosted_by' => 'Hosted by',
        'when' => 'When',
        'where' => 'Where',
        'view_map' => 'View map',
        'dress_code' => 'Dress code',
        'what_to_bring' => 'What to bring',
        'rsvp' => 'RSVP',
        'rsvp_by' => 'Please reply by',
        'rsvp_contact' => 'Contact',
        'rsvp_question' => 'Will you be joining us?',
        'rsvp_yes' => 'I will be there',
        'rsvp_no' => 'Sorry, I can’t come',
        'rsvp_note' => 'Add a note for the host (optional)',
        'rsvp_send' => 'Send',
        'rsvp_thanks' => 'Thank you for your reply!',
        'rsvp_error' => 'Your reply could not be sent. Please try again.',
        'music' => 'Play or pause the music',
        'honored_guest' => 'Honored Guest',
    ],

    // dashboard editor
    'editor' => [
        'intro' => 'Fill in the form — the preview on the right updates as you type. Press Save when you are done.',
        'intro_mobile' => 'Fill in the form — the preview below updates as you type. Press Save when you are done.',
        'required_note' => 'Fields marked * are required.',
        'more' => 'Add more details',
        'more_hint' => 'Optional — any line you leave empty is hidden on the invitation.',
        'extras' => 'Replies & music',
        'preview' => 'Live preview',
        'preview_hint' => 'Tap “Open Invitation” in the preview to see the details page.',
        'premium' => 'Premium',
        'premium_hint' => 'Not included in your plan — guests won’t see this.',
        'save' => 'Save',
        'saving' => 'Saving…',
        'saved' => 'Saved',
        'unsaved' => 'You have unsaved changes',
        'download' => 'Download as PNG',
        'downloading' => 'Preparing images…',
        'download_failed' => 'The images could not be created. Please try again.',
        'download_done' => 'Downloaded cover.png and details.png.',
        'reset' => 'Reset',
        'reset_confirm' => 'Reset the form? Every field will be cleared. Nothing is saved until you press Save.',
        'reset_done' => 'The form was cleared — press Save to keep this change.',
        'saved_toast' => 'Saved! Guests will see the changes immediately.',
        'fix_one' => 'Not saved yet — please fix “:field”.',
        'fix_many' => 'Not saved yet — :count fields need fixing (starting with “:field”).',
        'dress_none' => 'No dress code',
        'dress_other' => 'Other',
        'dress_other_placeholder' => 'Describe the dress code',
        'characters' => ':count / :max',
    ],

    // form fields: label, placeholder, hint
    'fields' => [
        'headline' => ['label' => 'Event title', 'placeholder' => 'Christmas Party'],
        'sender_name' => ['label' => 'Host name', 'placeholder' => 'e.g. The Smith Family'],
        'event_date' => ['label' => 'Date', 'placeholder' => ''],
        'start_time' => ['label' => 'Start time', 'placeholder' => ''],
        'venue_name' => ['label' => 'Venue name', 'placeholder' => 'e.g. Riverside Hall'],
        'venue_address' => ['label' => 'Address', 'placeholder' => 'e.g. 12 Sisowath Quay, Phnom Penh'],
        'guest_name' => ['label' => 'Guest name', 'placeholder' => 'e.g. Sophea', 'hint' => 'Only for this preview and the PNG download — each guest’s own name comes from their personal link.'],
        'end_time' => ['label' => 'End time', 'placeholder' => ''],
        'message' => ['label' => 'Message', 'placeholder' => 'e.g. Join us for dinner, carols and a gift exchange!'],
        'dress_code' => ['label' => 'Dress code', 'placeholder' => ''],
        'what_to_bring' => ['label' => 'What to bring', 'placeholder' => 'e.g. A small gift for the gift exchange'],
        'map_url' => ['label' => 'Map link', 'placeholder' => 'https://maps.app.goo.gl/…'],
        'rsvp_deadline' => ['label' => 'RSVP by', 'placeholder' => ''],
        'contact_phone' => ['label' => 'RSVP contact', 'placeholder' => 'e.g. 012 345 678 or @telegram'],
        'rsvp_enabled' => ['label' => 'Show RSVP buttons', 'placeholder' => '', 'hint' => 'Guests can answer yes or no — see their answers in the Responses tab.'],
        'rsvp_message' => ['label' => 'RSVP question', 'placeholder' => 'Will you be joining us?'],
        'music_url' => ['label' => 'Background music (YouTube link)', 'placeholder' => 'https://youtu.be/…'],
    ],

    // validation
    'errors' => [
        'required' => ':field is required.',
        'max' => ':field can be at most :max characters.',
        'invalid' => ':field is not valid.',
        'date_required' => 'Please choose the date.',
        'date_invalid' => 'Please enter a valid date.',
        'date_past' => 'The date can’t be in the past.',
        'start_time_required' => 'Please choose a start time.',
        'time_invalid' => 'Please enter a valid time.',
        'end_before_start' => 'The end time must be after the start time (:time).',
        'rsvp_after_event' => 'The RSVP date must be on or before the event date.',
        'url' => 'Please enter a valid link starting with https://',
    ],
];
