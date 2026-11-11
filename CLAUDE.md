# ROUMDOUL system

Laravel 12 + Livewire 4 + Filament 5 (admin) + Tailwind 4 (Vite). PHP 8.2+.
A storefront (subscriptions like Gemini/ChatGPT) plus **Digital Invitations**: customers buy a
template, fill in details in their dashboard, and send one personalized link per guest.

## Commands

- Run locally: `php artisan serve` (http://127.0.0.1:8000) and `npm run dev`
- Tests: `php artisan test`
- Format PHP: `vendor/bin/pint`

## Where things are

- Invitation templates: `resources/views/invitations/templates/` — read
  `docs/invitation-templates.md` before building or changing one (field catalog, plan gating,
  how to add a template).
- Every template renders through the shared shell `resources/views/invitations/show.blade.php`.
- Template demo: `/templates/{slug}/demo` (needs a row in admin).
  Review any template file without admin: `/demo/{path}` (e.g. `/demo/birthday`,
  `/demo/kak-thern`; add `?to=Name` for the guest name).
- Template-specific CSS/JS/images live in `public/` (`public/css/invitations/`,
  `public/js/invitations/`, `public/images/invitations/{template}/`).

## Rules

- Local `.env` uses SQLite. Never re-enable the production database credentials locally.
- Media goes through `Storage::disk('s3')` (falls back to local storage when no R2 keys are set);
  never store files in the database.
- Guest-facing invitation pages are always Khmer; the rest of the site defaults to English.
- On demo pages `$invitation` is `null` — treat that as "every premium field unlocked".
- Khmer wedding template: the desktop layout is separate — don't change the mobile layout when
  working on desktop.

## Remember

<!-- Tell Claude "remember ..." and it will add a line here. -->

- **Every new invitation template:** (1) make it dynamic — every name, date, place, message, photo,
  colour and music comes from `$fields` (catalog keys from `docs/invitation-templates.md`), with
  a sensible default only when the field is empty; no hardcoded customer content. Premium bits
  use `$invitation ? $invitation->fieldUnlocked($key) : true`. (2) Give the user a demo link to
  review: `http://127.0.0.1:8000/demo/{path}` (the file path under
  `resources/views/invitations/templates/` without `.blade.php`, e.g. `/demo/birthday` or `/demo/wedding/{name}`).
  If the template uses a field the sample data in `InvitationTemplateDemoController::file()`
  doesn't fill, add sample data there so every section shows in the demo.
