<?php

/*
| Interface strings shared by every portfolio (navigation, buttons, labels).
| A portfolio's own words live in portfolios/{slug}/content/{locale}.php.
*/

return [
    'nav' => [
        'home' => 'Home',
        'about' => 'About',
        'work' => 'Work',
        'expertise' => 'Expertise',
        'notes' => 'Notes & Experiments',
        'notes_short' => 'Notes',
        'contact' => 'Contact',
        'menu' => 'Menu',
        'close' => 'Close',
        'skip' => 'Skip to content',
        'primary' => 'Main',
        'breadcrumb' => 'Breadcrumb',
        'back_to_top' => 'Back to top',
    ],

    'theme' => [
        'label' => 'Colour theme',
        'light' => 'Light',
        'dark' => 'Dark',
        'switch_to_dark' => 'Switch to dark theme',
        'switch_to_light' => 'Switch to light theme',
    ],

    'language' => [
        'label' => 'Language',
        'not_translated' => 'This content is not yet available in English and is shown in its original language.',
    ],

    'status' => [
        'live' => 'Live',
        'in_development' => 'In development',
        'ongoing' => 'Ongoing',
        'exploring' => 'Exploring',
        'concept' => 'Concept',
        'completed' => 'Completed',
        'paused' => 'Paused',
        'archived' => 'Archived',
    ],

    'kind' => [
        'learning' => 'Learning',
        'building' => 'Building',
        'experimenting' => 'Experimenting',
    ],

    'level' => [
        'confident' => 'Confident',
        'working' => 'Working knowledge',
        'learning' => 'Currently learning',
    ],

    'date' => [
        'present' => 'present',
        'updated' => 'Updated :date',
    ],

    'common' => [
        'read_case_study' => 'Read the case study',
        'view_project' => 'View project',
        'all_work' => 'All work',
        'visit' => 'Visit project',
        'category' => 'Discipline',
        'status' => 'Status',
        'date' => 'Date',
        'year' => 'Year',
        'tools' => 'Tools',
        'link' => 'Link',
        'index' => 'Index',
        'related' => 'Related work',
        'next_project' => 'Next project',
        'previous' => 'Previous',
        'next' => 'Next',
        'of' => 'of',
        'image_required' => 'Image required',
        'image_hint' => ':label · :ratio',
        'enlarge' => 'View larger',
        'close_viewer' => 'Close image viewer',
        'reading_time' => ':min min read',
        'see_related' => 'See related work',
        'examples' => 'Examples',
    ],

    'work' => [
        'filter_label' => 'Filter by discipline',
        'status_label' => 'Filter by status',
        'all' => 'All projects',
        'any_status' => 'Any status',
        'view_label' => 'Layout',
        'view_index' => 'Index',
        'view_gallery' => 'Gallery',
        'showing' => 'Showing :count of :total',
        'empty' => 'No projects match this filter yet.',
        'reset' => 'Clear filters',
        'col_no' => 'No.',
        'col_project' => 'Project',
    ],

    'case' => [
        'overview' => 'Overview',
        'challenge' => 'The challenge',
        'idea' => 'The idea',
        'approach' => 'Approach & process',
        'solution' => 'Solution',
        'features' => 'Key features',
        'technical' => 'Technical details',
        'outcome' => 'Outcome',
        'reflection' => 'Reflection',
        'gallery' => 'Gallery',
        'contents' => 'Contents',
        'not_written' => 'Not written yet',
        'only_in' => 'The full case study is currently written in English.',
        'in_progress_note' => 'This project is still in progress. The page will grow as the work does.',
    ],

    'notes' => [
        'types' => [
            'article' => 'Article',
            'note' => 'Note',
            'experiment' => 'Experiment',
            'reflection' => 'Reflection',
            'discovery' => 'Discovery',
            'observation' => 'Observation',
        ],
        'all' => 'Everything',
        'filter_label' => 'Filter by type',
        'draft' => 'Draft — only visible while editing',
        'related_projects' => 'Related projects',
        'back' => 'All notes',
        'only_in' => 'This note is currently only available in English.',
    ],

    'contact' => [
        'fields' => [
            'name' => 'Your name',
            'email' => 'Email address',
            'subject' => 'Subject',
            'message' => 'Message',
            'optional' => 'optional',
        ],
        'send' => 'Send message',
        'sending' => 'Sending…',
        'sent_title' => 'Message received.',
        'sent_body' => 'Thank you — your message has been saved and I will read it personally. Replies usually come by email.',
        'send_another' => 'Send another message',
        'errors' => [
            'spam' => 'Your message could not be accepted. Please reload the page and try again.',
            'too_fast' => 'That was very quick — please take a moment, then send again.',
            'summary' => 'Please check the highlighted fields.',
        ],
        'privacy' => 'Your details are used only to reply to you.',
        'email_label' => 'Email',
        'elsewhere' => 'Elsewhere',
        'professional' => 'Professional',
    ],

    'footer' => [
        'index' => 'Index',
        'elsewhere' => 'Elsewhere',
        'colophon' => 'Colophon',
        'colophon_text' => 'Set in Instrument Serif and Manrope, with Noto Khmer for Khmer text.',
        'rights' => 'All rights reserved.',
    ],

    'editor' => [
        'label' => 'Editor note',
        'missing_section' => 'Add “:section” in :file',
        'missing_image' => 'Add file: :path',
        'visible_note' => 'Editor notes are only shown while APP_DEBUG or PORTFOLIO_EDITOR_HINTS is on.',
    ],
];
