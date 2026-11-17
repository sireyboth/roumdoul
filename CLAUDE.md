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
  While an order is `pending_payment`, the customer gets the KHQR payment modal (`<x-shop.payment-modal>`, 3D glass: QR,
  account name + ID, exact amount, order number as the transfer note; no screenshot asked) on the order
  confirmation page (opens by itself) and the dashboard order page («Pay now»). QR image: `public/images/payment/khqr.png`.
  Account details: `config('roumdoul.payment')`. An order only becomes `paid` through the admin's
  «Mark as paid» (`Order::markPaid()`: amount received ≥ total + a bank transaction ID never used on
  another order); that provisions invitations, applies add-ons and emails the customer.
- Add-ons (more guests / photos, unlock a feature, upgrade, extend) are add-on orders for one invitation
  — see «Add-ons» in `docs/invitation-templates.md`. Each invitation keeps its own copy of its plan's
  limits, so editing a plan never changes invitations already sold. Production needs the scheduler
  (`php artisan schedule:run` every minute) for the daily expiry reminders.
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
  names, date, «Made with love ♥», then «Created with love by» and the ROUMDOUL block (the site footer
  without Product categories / Company: logo, description, social icons, quick help + Telegram button, policy links, ©).
  Its text is Khmer; an English template passes `lang="en"`.
  Pass `prefix` to reuse a template's own footer classes, or theme it with the `--inv-footer-*` variables.
  Social links live in `config/roumdoul.php` (`ROUMDOUL_*_URL` in `.env`).
- **Always use sound effects (sfx)** in invitation templates and their interactive parts: every tap,
  open, reveal, scratch, flip, pin, etc. gets a short sound. Use the shared files in
  `public/images/invitations/assets/sfx/` (`pop`, `chime`, `reveal`, `card`, `envelope`, `scratch`/`scratch-loop`,
  `papercrackle1`, `cassette`, `birds-loop`, …) and add a new file there when nothing fits. Shared components
  (`<x-scratch-card>`, `<x-memory-map>`, …) play sfx too. Sounds start only after the guest's first tap,
  stay soft under the song, and follow the template's mute button.
- **Fonts must support both English and Khmer.** Prefer a font that has both scripts (e.g. `Kantumruy Pro`,
  `Noto Sans Khmer` / `Noto Serif Khmer`, `Battambang`). When a stylish Latin-only font is needed (script,
  display, serif…), always pair it with a Khmer font right after it in the same `font-family` stack, e.g.
  `"Pinyon Script", "Moul", cursive` or `"Instrument Serif", "Kantumruy Pro", serif`, so Khmer text never falls
  back to a system font. Load both from Google Fonts. Applies everywhere: CSS variables, inline styles, canvas
  `ctx.font`, and shared components (their `--inv-*-font` defaults too).
