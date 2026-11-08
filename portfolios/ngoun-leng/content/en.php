<?php

/*
|--------------------------------------------------------------------------
| Page copy — English
|--------------------------------------------------------------------------
| Every word on the portfolio pages that isn't project/note content.
| Markdown is allowed in longer text fields (`*italic*`, `**bold**`, links).
| Lines marked TODO are drafts or placeholders waiting for your real details.
*/

return [

    'meta' => [
        'title' => 'Ngoun Leng — Technology, education and creative work',
        'description' => 'Personal portfolio of Ngoun Leng: building digital systems, teaching practical digital skills, and graphic and video design.',
    ],

    // One line used under your name across the site.
    'role' => 'Builder, teacher, and graphic & video designer working across technology, education and design.',

    /* ------------------------------------------------------------------ */
    'home' => [
        'hero' => [
            'kicker' => 'Personal index',
            'headline' => ['Different disciplines.', 'One way of thinking.'],
            'lede' => 'I explore the intersection of technology, creativity, and practical problem-solving — building digital systems, sharing knowledge, and turning ideas into meaningful experiences.',
            'cta_work' => 'Explore the work',
            'cta_about' => 'About me',
            'caption' => 'Portrait',
        ],

        'intro' => [
            'label' => 'Introduction',
            'title' => 'More than one discipline.',
            'body' => "My work moves between software, spreadsheets, classrooms and 3D scenes. On the surface these look unrelated. Underneath, the work is the same: understand the problem properly, explore a few ways to approach it, then build something that actually gets used.\n\nA business system, a lesson on dynamic spreadsheets and an animated scene all ask the same questions — what is this for, who is it for, and what is the simplest structure that holds it together.",
            'process' => ['Understand', 'Explore', 'Build', 'Share'],
        ],

        'work' => [
            'label' => 'Selected work',
            'title' => 'Things I have built, taught and made.',
            'more' => 'See all projects',
        ],

        'areas' => [
            'label' => 'Areas of exploration',
            'title' => 'Four directions, one practice.',
        ],

        'now' => [
            'label' => 'Current exploration',
            'title' => 'On the desk right now.',
            'intro' => 'What I am learning, building or testing at the moment. Statuses are honest — most of this is unfinished, and that is the point.',
            'columns' => ['subject' => 'Subject', 'kind' => 'Mode', 'status' => 'Where it stands'],
            // kind: learning | building | experimenting — status: free text, keep it honest.
            'items' => [
                ['subject' => 'Software architecture', 'detail' => 'Structuring larger applications so they stay understandable as they grow.', 'kind' => 'learning', 'status' => 'Learning'],
                ['subject' => 'Business automation', 'detail' => 'Removing repeated manual steps from everyday business work.', 'kind' => 'building', 'status' => 'Ongoing'],
                ['subject' => 'Dynamic spreadsheet systems', 'detail' => 'Spreadsheets that behave like small applications, not static tables.', 'kind' => 'building', 'status' => 'Ongoing'],
                ['subject' => 'Blender animation', 'detail' => 'Modelling, lighting and movement — learning by making short scenes.', 'kind' => 'learning', 'status' => 'Learning'],
                ['subject' => 'Digital product development', 'detail' => 'Taking ideas from a working prototype to something people can use and pay for.', 'kind' => 'experimenting', 'status' => 'In progress'],
            ],
        ],

        'philosophy' => [
            'label' => 'How I work',
            'title' => 'Four habits behind everything here.',
            'principles' => [
                ['line' => 'Understand before building.', 'note' => 'Most wasted work comes from solving the wrong problem well. I start by asking how things actually work today.'],
                ['line' => 'Make complexity easier to understand.', 'note' => 'A system is only useful if the people using it can follow it. Clarity is a feature, not decoration.'],
                ['line' => 'Learn through experimentation.', 'note' => 'I learn tools by making real things with them — small, imperfect, finished.'],
                ['line' => 'Build things that have practical value.', 'note' => 'If nobody would miss it, it probably did not need to exist. Usefulness is the test.'],
            ],
        ],

        'closing' => [
            'headline' => 'Have an idea worth building?',
            'body' => 'I’m always interested in thoughtful ideas, meaningful projects, and opportunities to create something useful.',
            'cta' => 'Start a conversation',
        ],
    ],

    /* ------------------------------------------------------------------ */
    'disciplines' => [
        'technology' => [
            'title' => 'Technology & Systems',
            'description' => 'Web applications and business systems that organise real operations — sales, inventory, ordering, reporting.',
            'examples' => ['Business management SaaS', 'QR ordering', 'Sales & inventory', 'Sheets automation'],
        ],
        'education' => [
            'title' => 'Education & Knowledge',
            'description' => 'Teaching practical digital skills, with a focus on understanding systems rather than memorising features.',
            'examples' => ['Excel & Google Sheets', 'Project-based lessons'],
        ],
        'creative' => [
            'title' => 'Graphic & Video Design',
            'description' => 'Graphic design and video — visuals, motion and editing that communicate clearly.',
            'examples' => ['Graphic design', 'Video editing', 'Motion & 3D'],
        ],
        'products' => [
            'title' => 'Digital Products & Innovation',
            'description' => 'Small digital products and experiences, from personalised online invitations to e-commerce concepts.',
            'examples' => ['Digital invitations', 'E-commerce concepts', 'Creative campaigns'],
        ],
    ],

    /* ------------------------------------------------------------------ */
    'about' => [
        'meta_description' => 'About Ngoun Leng — how a practice across software, teaching and creative production came together.',
        'label' => 'About',
        'title' => 'Working between disciplines, on purpose.',
        'intro' => 'I work across technology, education, creative production and digital products. I build business systems, teach people to think in systems with spreadsheets, and make animated and video work — and I keep finding that each one makes me better at the others.',

        'story' => [
            'label' => 'My story',
            // TODO: this is a draft written from your brief. Rewrite it in your own words —
            // where it started, what pulled you toward each discipline, a turning point or two.
            'draft' => true,
            'body' => "My interests did not arrive in a neat order. Software, teaching and creative work grew alongside each other, and each one kept borrowing from the others.\n\nBuilding systems for businesses taught me to look at how work actually flows before deciding what to make. Teaching spreadsheets taught me that most people do not need more formulas — they need to see how the pieces connect. Graphic and video design taught me pacing, composition and patience.\n\nToday I use all three together: I design software the way I would explain a lesson, and I approach lessons the way I would structure a scene.",
        ],

        'thinking' => [
            'label' => 'How I think',
            'title' => 'The approach is the constant.',
            'items' => [
                ['title' => 'Curiosity', 'body' => 'I want to know why something works, not only how to use it.'],
                ['title' => 'Independent thinking', 'body' => 'I prefer to reason from the problem itself rather than copy the usual answer.'],
                ['title' => 'Practical experimentation', 'body' => 'Small working versions first. They teach more than long plans.'],
                ['title' => 'System thinking', 'body' => 'Every feature, lesson or scene is part of something larger. I try to see that whole.'],
                ['title' => 'Continuous learning', 'body' => 'Several of the things on this site I started as a beginner. Some I still am.'],
                ['title' => 'Creative problem-solving', 'body' => 'Constraints are useful. The best solutions often come from working within them.'],
            ],
        ],

        'journey' => [
            'label' => 'My journey',
            'title' => 'Milestones.',
            'empty' => 'A dated timeline is being prepared.',
            // TODO: replace these placeholders with real, dated entries.
            // Set 'verified' => true only when the details are accurate — unverified
            // entries are hidden from visitors and only shown as editor notes.
            'entries' => [
                ['year' => null, 'type' => 'Education', 'title' => 'Your education — school / university / course', 'body' => null, 'verified' => false],
                ['year' => null, 'type' => 'Experience', 'title' => 'A role or position you held', 'body' => null, 'verified' => false],
                ['year' => null, 'type' => 'Project', 'title' => 'Started building Roumdoul', 'body' => null, 'verified' => false],
                ['year' => null, 'type' => 'Learning', 'title' => 'Began learning Blender', 'body' => null, 'verified' => false],
                ['year' => null, 'type' => 'Milestone', 'title' => 'An accomplishment you are proud of', 'body' => null, 'verified' => false],
            ],
        ],

        'beyond' => [
            'label' => 'Beyond work',
            'title' => 'Outside the work.',
            // TODO: a few lines about interests, influences, what inspires you. Leave empty to hide.
            'body' => null,
            'interests' => [],
        ],

        'portrait_caption' => 'Ngoun Leng',
    ],

    /* ------------------------------------------------------------------ */
    'expertise' => [
        'meta_description' => 'What Ngoun Leng can help with — capabilities across technology, automation, education, creative production and digital products.',
        'label' => 'Expertise',
        'title' => 'Capabilities, not percentages.',
        'intro' => 'Grouped by what I can actually do. Levels are shown only where I am sure of them; tools are listed separately, because a tool is not a skill.',
        'matrix' => [
            'label' => 'Capability × project',
            'caption' => 'Which capabilities each project draws on.',
        ],
        'groups' => [
            'technology' => [
                'title' => 'Technology & Systems',
                'summary' => 'Designing and building software around how a business really operates.',
                // 'level' => 'confident' | 'working' | 'learning' | null (hidden). TODO: set levels you are sure of.
                'items' => [
                    ['name' => 'Web application development', 'level' => null],
                    ['name' => 'Business management systems', 'level' => null],
                    ['name' => 'Workflow design', 'level' => null],
                    ['name' => 'Database design', 'level' => null],
                    ['name' => 'SaaS product concepts', 'level' => null],
                    ['name' => 'System automation', 'level' => null],
                ],
            ],
            'automation' => [
                'title' => 'Automation & Data',
                'summary' => 'Turning spreadsheets into dependable, connected tools.',
                'items' => [
                    ['name' => 'Microsoft Excel', 'level' => null],
                    ['name' => 'Google Sheets', 'level' => null],
                    ['name' => 'Google Apps Script', 'level' => null],
                    ['name' => 'Dynamic reporting', 'level' => null],
                    ['name' => 'Data management', 'level' => null],
                    ['name' => 'Spreadsheet-based systems', 'level' => null],
                ],
            ],
            'education' => [
                'title' => 'Education & Knowledge',
                'summary' => 'Teaching that builds understanding, not dependence on steps.',
                'items' => [
                    ['name' => 'Practical teaching', 'level' => null],
                    ['name' => 'Curriculum development', 'level' => null],
                    ['name' => 'Project-based learning', 'level' => null],
                    ['name' => 'Digital skills education', 'level' => null],
                    ['name' => 'System thinking', 'level' => null],
                ],
            ],
            'creative' => [
                'title' => 'Graphic & Video Design',
                'summary' => 'Telling stories with movement, light, sound and timing.',
                'items' => [
                    ['name' => '3D animation', 'level' => null],
                    ['name' => 'Video production', 'level' => null],
                    ['name' => 'Visual storytelling', 'level' => null],
                    ['name' => 'Creative experimentation', 'level' => null],
                ],
            ],
            'products' => [
                'title' => 'Digital Products',
                'summary' => 'Shaping ideas into experiences people can use and understand.',
                'items' => [
                    ['name' => 'Product concepts', 'level' => null],
                    ['name' => 'Digital experiences', 'level' => null],
                    ['name' => 'UI/UX thinking', 'level' => null],
                    ['name' => 'Visual communication', 'level' => null],
                    ['name' => 'Creative direction', 'level' => null],
                ],
            ],
        ],
        'tools' => [
            'label' => 'Toolbox',
            'title' => 'Tools I work with.',
            'groups' => [
                ['title' => 'Building', 'items' => ['Laravel', 'Livewire', 'Filament', 'Tailwind CSS']],
                ['title' => 'Data & automation', 'items' => ['Microsoft Excel', 'Google Sheets', 'Google Apps Script']],
                ['title' => 'Creative', 'items' => ['Blender', 'DaVinci Resolve']],
            ],
        ],
    ],

    /* ------------------------------------------------------------------ */
    'work' => [
        'meta_description' => 'Projects by Ngoun Leng across technology, education, creative production and digital products.',
        'label' => 'Work',
        'title' => 'Work',
        'intro' => 'Systems, lessons, scenes and products — finished, in progress, and still being explored. Each one is marked honestly.',
    ],

    /* ------------------------------------------------------------------ */
    'notes' => [
        'meta_description' => 'Notes and experiments by Ngoun Leng — lessons from building software, teaching, and learning 3D.',
        'label' => 'Notes & Experiments',
        'title' => 'A working notebook.',
        'intro' => 'Lessons, experiments and observations from the work — written when there is something worth writing down.',
        'empty' => 'No notes published yet. The first ones are being written.',
        'planned_label' => 'Being written',
        'planned' => [
            'Lessons from building business management software',
            'Why spreadsheets should teach system thinking',
            'My journey learning Blender',
            'Building practical digital products',
        ],
    ],

    /* ------------------------------------------------------------------ */
    'contact' => [
        'meta_description' => 'Get in touch with Ngoun Leng about projects, collaboration or teaching.',
        'label' => 'Contact',
        'title' => 'Let’s talk.',
        'intro' => 'Have a project, a collaboration in mind, or a question about something on this site? Write to me — I read every message myself.',
        'invitation' => [
            'title' => 'Good reasons to get in touch',
            'items' => [
                'A business process that needs a proper system',
                'A training or teaching collaboration',
                'A creative or video project',
                'A digital product idea worth testing',
            ],
        ],
        'form_title' => 'Send a message',
    ],

    /* ------------------------------------------------------------------ */
    'footer' => [
        'statement' => 'Different disciplines. One way of thinking.',
    ],
];
