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

- Local `.env` uses MySQL (`DB_CONNECTION=mysql`, local server). Never put the production
  database credentials in the local `.env`.
- Media always goes to S3/R2 through `Storage::disk('s3')`. Never store files in the database or
  write uploads to another disk. The local-folder fallback (when `AWS_BUCKET` is empty) is only
  for local dev and tests; production never falls back (`config/filesystems.php`).
- Payment is manual for now: checkout creates a `pending_payment` order and sends a Telegram
  alert, then an admin confirms payment (ABA/Wing/QR) and marks the order `paid` in Filament,
  which unlocks the invitation. Online automatic payment comes later. Don't build it unless asked.
- Customer photo uploads (dashboard image + gallery fields, GIFs included) are **3 MB max** per
  file. Keep `InvitationManagePage::MAX_UPLOAD_KB` / `MAX_GIF_KB` (3072) and the UI/lang texts in
  step. `config/livewire.php` `temporary_file_upload.rules` (20 MB) is only a safety ceiling, so
  leave it. The browser shrinks photos to 1600px before sending
  (`resources/js/modules/photoUploader.js`), so the cap applies to the file that is sent.
- Guest-facing invitation pages are always Khmer; the rest of the site defaults to English.
- On demo pages `$invitation` is `null` — treat that as "every premium field unlocked".
- Every move to a new page must have a transition. Internal links use `wire:navigate` (or
  `$this->redirect(..., navigate: true)` from Livewire), which gets the fade-in from the
  `[data-page-transition]` wrapper in the layout (`page-enter` in `resources/css/app.css`, added in
  `resources/js/app.js`). Full page loads use the view transition in the layouts. A new layout must
  include both; respect `prefers-reduced-motion`.
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
- **Every invitation template always ends with the shared footer component** (no exceptions, never a
  hand-written footer): `<x-invitation-footer>` (`resources/views/components/invitation-footer.blade.php`,
  styles `public/css/invitations/shared/footer.css`). It has the back-to-top button, ornament divider,
  names, date, «រៀបចំឡើងដោយក្តីស្រឡាញ់ ♥», the ROUMDOUL social icons and `CRAFTED BY ROUMDOUL · {year}`.
  Pass `prefix` to reuse a template's own footer classes, or theme it with the `--inv-footer-*` variables.
  Social links live in `config/roumdoul.php` (`ROUMDOUL_*_URL` in `.env`).
