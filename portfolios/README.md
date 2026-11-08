# Personal portfolios

Each folder in here is one personal portfolio website, served at:

```
/portfolio/{folder-name}           → redirects to the visitor's language
/portfolio/{folder-name}/en        → English home
/portfolio/{folder-name}/km        → Khmer home
```

Live portfolios: **`ngoun-leng/`**. Folders starting with `_` or `.` are ignored.

All content is plain files: no database, no admin screen. Edit a file, refresh the page.

## Folder layout

```
portfolios/
└── ngoun-leng/
    ├── portfolio.php          name, languages, contact email, social links,
    │                          disciplines, featured projects, accent colour
    ├── content/
    │   ├── en.php             every word on the pages, in English
    │   └── km.php             the same keys, in Khmer
    ├── projects/
    │   └── roumdoul/          folder name = URL: /work/roumdoul
    │       ├── project.php    facts: discipline, status, date, tools, images, layout
    │       ├── en.php         title, summary + case-study sections (English)
    │       └── km.php         Khmer — may be partial (see "Languages")
    ├── notes/
    │   └── _example/          copy this to start a note
    │       ├── note.php       date, type, cover, related projects, draft flag
    │       ├── en.md          front matter (title, excerpt) + Markdown body
    │       └── km.md
    └── media/                 original images — see media/README.md
```

## Common tasks

| I want to… | Do this |
|---|---|
| Add a project | Copy a project folder, rename it (lowercase-with-dashes), edit its three files |
| Hide a project | `'hidden' => true` in its `project.php` |
| Change home-page projects | `featured` in `portfolio.php` |
| Add images | Put files in `media/` at the path the content names, then run `php artisan portfolio:media ngoun-leng` |
| Publish a note | Copy `notes/_example` to `notes/my-note`, write it, set `'draft' => false` |
| Show my email | `contact.email` in `portfolio.php` |
| Get emailed about messages | `contact.notify_email` in `portfolio.php` + a real `MAIL_MAILER` in `.env` |
| Read contact messages | Admin panel → **Portfolio → Messages** (always saved there) |
| Set skill levels | `level` on items in `content/{lang}.php → expertise.groups` (`confident`, `working`, `learning`), only where accurate |
| Fill the timeline | `about.journey.entries`; set `'verified' => true` on real entries (others stay hidden) |

### Project status values

`live` · `in_development` · `ongoing` · `exploring` · `concept` · `completed` · `paused` · `archived`.
Each one is drawn with its own dot shape, so status reads without relying on colour.

### Case-study layout (`template` in `project.php`)

- `system`: software. Title next to a facts table, screenshot on a plate.
- `creative`: animation and video. Dark, letterboxed "screening room" hero.
- `editorial`: education and products. Oversized title, offset image.

Case-study sections (all optional, in this order): `overview`, `challenge`, `idea`, `approach`,
`solution`, `features`, `technical`, `outcome`, `reflection`. Empty ones are hidden from visitors.

## Languages

- Each language is a separate file. Never mix languages in one field.
- If a Khmer project file has only `title`/`summary`, the Khmer page shows the English case
  study with a notice that it is not yet translated (and the correct `lang` attribute).
- Interface words (menu, buttons, statuses) shared by all portfolios live in `lang/en/portfolio.php`
  and `lang/km/portfolio.php`.

## Editor notes

While `APP_DEBUG=true` (or `PORTFOLIO_EDITOR_HINTS=true`), pages show dashed **Editor note** boxes
naming what is missing and which file to edit, plus draft notes and image file paths. Visitors in
production never see them.

## Adding another person's portfolio

1. Copy `ngoun-leng/` to `new-name/`.
2. Edit `portfolio.php` (name, initials, `locales`, disciplines) and the content files.
3. Delete the projects/notes/media that are not theirs.
4. Done: `/portfolio/new-name` works. The sitemap picks it up automatically.

A portfolio can offer just one language: `'locales' => ['en']`.

## Where the code lives

| Part | Location |
|---|---|
| Settings | `config/portfolio.php` |
| Content loading | `app/Portfolio/` (`Portfolio`, `Project`, `Note`, `Media`, `PortfolioContext`) |
| Pages + contact form | `app/Http/Controllers/Portfolio/` · routes in `routes/portfolio.php` |
| Image optimizer | `php artisan portfolio:media` (`app/Console/Commands/PortfolioMediaCommand.php`) |
| Views | `resources/views/portfolio/` (`<x-pf::…>` components in `components/`) |
| Design system | `resources/css/portfolio.css` (plain CSS, light + dark tokens at the top) |
| Interactions | `resources/js/portfolio.js` (all optional — every page works without JS) |
| Messages admin | `app/Filament/Resources/PortfolioMessages/` |
| Tests | `tests/Feature/PortfolioTest.php` |

Optimized images in `public/portfolios/` are generated files. Commit them after running
`portfolio:media` so deployments don't need an extra build step.
