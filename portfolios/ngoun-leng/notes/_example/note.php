<?php

/*
| TEMPLATE — folders starting with "_" are ignored by the site.
| To publish a note: copy this folder to notes/your-note-slug/, edit the files,
| and set 'draft' => false. The folder name becomes the URL:
|   /portfolio/ngoun-leng/en/notes/your-note-slug
*/

return [
    'date' => '2026-11-07',
    // article | note | experiment | reflection | discovery | observation
    'type' => 'article',
    // Optional image in media/, e.g. 'notes/your-note-slug/cover.jpg' (16:9, at least 1600 × 900 px)
    'cover' => null,
    // Folder names from projects/ this note relates to.
    'related' => ['spreadsheet-education'],
    // Drafts are only visible while APP_DEBUG (or PORTFOLIO_EDITOR_HINTS) is on.
    'draft' => true,
];
