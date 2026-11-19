# Digital Invitations — how to add a new template

This is the reference for adding a new invitation design (wedding, break-up message,
date-asking, birthday, etc.) to the system. Read this before building a new one.

## How the pieces fit together

- A **template** (`InvitationTemplate`) is a design + a schema of which standard fields it uses.
- A **service/plan** (existing `Service`/`ServicePlan`) is how it's priced and sold — recipient
  limit, retention period, and unlocked features all live on the `ServicePlan` the customer buys.
- An **invitation** (`Invitation`) is one customer's filled-in project: which template, their
  field values, how many recipients they're allowed, and when it expires.
- A **recipient** (`InvitationRecipient`) is one shareable link: a name + an unguessable token.
  Public URL: `/i/{token}` (12 random letters/digits per guest, throttled; old `/invite/{slug}/{token}` links 301-redirect there).

## The standard field catalog

Templates should compose from this catalog instead of inventing new field keys. Every field
lives in one flat list, `InvitationTemplate::FIELD_CATALOG` — there's no separate per-event-type
catalog. A template just ticks which of these it uses; unrelated templates are unaffected by
adding more entries, so the catalog is meant to keep growing as new event types show up.

For readability, `InvitationTemplate::FIELD_SECTIONS` additionally groups every catalog key into
`universal` (reusable by any template), `wedding`, `birthday`, or `ceremony` (Buddhist merit-making
ceremonies such as Kathina) — purely cosmetic, driving the separate checkbox lists on the admin "Fields this design uses" screen (Filament's
`CheckboxList` has no native option-group support, so it's three components, not one). This has
zero effect on gating or rendering — only `FREE_FIELDS` (below) and a template's own `fields`
selection actually matter at runtime. Add a new section here when a new event type brings fields
that genuinely don't belong anywhere else; most new fields turn out to be `universal`.

**Field types the dashboard form renderer understands:** `text`, `textarea`, `image` (single
upload), `gallery` (multiple images, add/remove), `schedule` (repeatable time + label rows,
add/remove), `boolean`, `datetime`, `color`.

**Free fields — always available on every plan, once a template ticks them:**

| Field | Type | Purpose |
|---|---|---|
| `sender_name` | text | Generic "who it's from" (date-asking, breakup, etc.) |
| `groom_name` / `bride_name` | text | Wedding-specific names |
| `couple_initials` | text | The couple's initials, e.g. «ប&ម» (Khmer Traditional Wedding II: inside the gold frame on the cover and on the closing seal); empty = first letter of each name |
| `groom_father` / `groom_mother` / `bride_father` / `bride_mother` | text | Wedding-specific parents' names (top of the card, above the title; "លោក"/"លោកស្រី" added automatically) |
| `celebrant_name` | text | Birthday-specific name |
| `turning_age` | text | Birthday-specific "turning ___" |
| `opening_line` | text | Buddhist ceremonies: the line under the title; empty = "នមោ តស្ស ភគវតោ អរហតោ សម្មាសម្ពុទ្ធស្ស" |
| `host_names` | long text | Ceremony hosts / organizing committee, one name per line (Kathina: "ម្ចាស់ពិធី") |
| `contact_phone` | text | One phone number guests can call for details (any event) |
| `headline` | text | Big title text |
| `message` | long text | The main personalized paragraph / wish |
| `reception_message` | long text | Wording of the reception invitation card (replaces the default text under the guest name) |
| `thanks_message` / `apology_message` | long text | Thank-you statement and apology letter (own sections before the closing seal). Kathina uses `thanks_message` as the closing blessing text; empty = the template's own blessing |
| `title_image` | image | Optional picture of the big title (e.g. a transparent PNG of decorated lettering). Kathina: shown instead of the title; without it the typed `headline` becomes the title text, and with neither the template's own title artwork is used |
| `cover_image` | image | Hero photo (Kathina: replaces the trai in the middle of the first screen and above the blessing; Rose Bloom: the poster photo — falls back to the first gallery photo) |
| `background_image` | image | Optional full-screen background (Kathina: replaces the temple picture; portrait works best) |
| `gift_message` | long text | Optional wording above the gift/donation QR; empty = the template's own text |
| `rsvp_message` | text | Optional question above the RSVP buttons; empty = the template's own text |
| `event_date` | datetime | The date being announced/proposed (Rose Bloom: «together since» — the poster's date column and a days-together count) |
| `venue_name` | text | Location name (plain text, not a map link). Rose Bloom: a short «where it began» line on the poster |
| `music_url` | text | Background music — a YouTube/TikTok link |
| `music_file` | audio | Background music uploaded as a file (MP3/M4A/AAC, at most `InvitationManagePage::MAX_AUDIO_KB` = 10 MB ≈ 10 min). Checked in the browser before anything is sent and again on the server (size + real content type); stored under a random name in the invitation's own folder, never re-encoded (no ffmpeg). Templates play it instead of `music_url` when both are set, with `preload="none"` so guests download nothing until it plays |
| `accent_color` | color | Theme color |
| `love_reasons` | long text | Anniversary / love letter: reasons you love them, one per line (Rose Bloom: the tap-through cards deck; River Love: «Paper boats», one folded boat per line, max 9, a tap unfolds it into a note) |
| `photo_captions` | long text | One caption per line, in photo-gallery order (Rose Bloom: lines 1–4 = scratch stamps, 5–9 = photo strip as «Title \| small line»). Shown in the dashboard's media tab |
| `song_title` | text | Song name on the music card / record label (Rose Bloom: the “Now playing” card; River Love: the cover «Play …» button and its «Now playing» record card, both driving the same song); the song itself is `music_file` / `music_url` |
| `closing_line` | text | The last big line at the very end (Rose Bloom finale; River Love: shows after the guest sends a paper lantern up; `thanks_message` is the smaller line under it) |

**Premium fields — the pricing upsell lever.** A template can still tick these into its schema,
but they only actually render for a customer whose plan has that exact key in
`service_plans.features`:

| Field | Type | Purpose |
|---|---|---|
| `venue_address` | text | Feeds a "Get directions" Google Maps link |
| `khmer_date` | text | Lunar/Khmer calendar date, written as free text |
| `rsvp_enabled` | boolean | Shows the RSVP yes/no buttons and persists responses |
| `countdown_enabled` | boolean | Shows a live countdown timer |
| `photo_gallery` | gallery | Multi-image gallery. How many photos it holds is set per plan in admin (**Max gallery photos**, `service_plans.max_gallery_photos`); blank = 20 (`InvitationManagePage::MAX_GALLERY_PHOTOS`). Read via `$invitation->maxGalleryPhotos()` |
| `event_schedule` | schedule | Repeatable time + item rows (e.g. "5:00 PM — Ceremony") |
| `schedule_image` | image | Optional ready-made schedule picture (e.g. a kbach card). When set, templates show it **instead of** the typed `event_schedule` rows. Has no plan unlock of its own — it follows `event_schedule` via `InvitationTemplate::UNLOCKED_WITH`, and is edited in the dashboard's Program tab via the catalog entry's `section` override |
| `qr_code` | image | A QR code image upload |
| `cta_label` + `cta_url` | text | Custom call-to-action button |

If a genuinely new field is needed, add it to `InvitationTemplate::FIELD_CATALOG` (one time,
plus `FREE_FIELDS` if it should never be gated) — not just to one template — so the next
template that needs something similar can reuse it too. Adding a new field TYPE (beyond the
ones above) additionally needs a new `@case()` branch in
`invitation-manage-page.blade.php`'s dynamic form renderer.

## Feature gating by plan

Premium fields (see table above) are gated by which `ServicePlan` the customer bought, via
`service_plans.features` — a JSON array of **field keys straight from the catalog**
(e.g. `['venue_address', 'countdown_enabled']`), not a separate feature-naming scheme. This is
set in admin exactly like price (the "Unlocked fields" checkbox list on a plan, generated
automatically from the catalog) — it's how a template earns you an upsell ("Premium unlocks
the map") without any code change per feature.

In a template's Blade file, gate optional content with `$invitation->fieldUnlocked($key)`
(handles the free/premium split for you — free fields always pass, premium ones check the
plan):

```blade
@if ($invitation->fieldUnlocked('venue_address'))
  {{-- render the map link here --}}
@endif
```

Free fields are never gated — they're always available regardless of plan. On the unpurchased
`/templates/{slug}/demo` preview page `$invitation` is `null`, so templates should treat a null
invitation as "everything unlocked" (e.g. `$invitation ? $invitation->fieldUnlocked($key) : true`)
so the demo actually shows off premium content. Shared components like a reusable
map/countdown/RSVP block (`<x-invitations.map>` etc.) don't exist yet — the one real template so
far (date-asking) inlines this logic itself. Build a shared component once a second template
needs the same behavior, not preemptively.

**Each invitation keeps its own limits.** When an order is paid, the plan's `max_recipients`,
`max_gallery_photos` and `features` are **copied onto the invitation** (`invitations.*`). Templates
and the dashboard read the invitation's copy (`$invitation->maxGalleryPhotos()`,
`fieldUnlocked()`), so editing a plan in admin only affects new buyers. Old invitations made before
the copy existed fall back to their plan.

## Add-ons (more guests, photos, features, upgrade, extend)

A customer can buy extras for an invitation they already own — `App\Services\InvitationAddons`,
the dashboard's «Add more» section (`partials/addons.blade.php`). Prices are set **per plan** in
admin (`service_plans.extra_guest_price`, `extra_photo_price`, `feature_prices` {key: price},
`extend_price` + `extend_months`); a blank price = that add-on isn't sold for the plan. Upgrading
to a pricier plan of the same product costs the difference and keeps everything already bought.

Buying makes a `pending_payment` order with `type = 'addon'` and `invitation_id` (order items carry
`addon_type` / `addon_value`), sends the Telegram alert and opens the KHQR payment modal. When the
admin marks it paid (Filament «Mark as paid» → `Order::markPaid()`: amount received ≥ total, unique
bank transaction ID), `InvitationAddons::apply()` raises the invitation's limits **once**
(`orders.addons_applied_at`). The customer gets an «order paid» email (`CustomerNotifier`), and
`invitations:remind-expiring` (daily) emails them 14 days before expiry, pointing at «Extend time».

## Steps to add a new template

Step 1 is code (needs a developer). Step 2 is fully self-serve in Filament admin
(**Catalog → Invitation Templates**), no code or deploy, and no separate trip to "Services"
needed — that screen is only for real subscription products (Gemini, ChatGPT, etc.); it never
shows invitation products at all.

1. **Build the Blade file first** — `resources/views/invitations/templates/{name}.blade.php`.
   It always receives:
   - `$recipientName` — the personalized name for this specific link
   - `$fields` — an array keyed by whatever this template's schema declares
   - `$invitation` — for feature-gate checks (`$invitation->fieldUnlocked('venue_address')`, etc.)

   Beyond that contract, the file is fully custom — any layout, colors, animation, fonts. The
   admin "Design (Blade view)" dropdown (step 2) only lists files that already exist here, so
   this has to happen first — you can't pick a file that isn't on disk yet.

   **The one fixed part: every template ends with the shared footer**, never a hand-written one:

   ```blade
   <x-invitation-footer :names="$coupleTitle" :date="$dateNumeric"
     style="--inv-footer-bg: #2a0606; --inv-footer-text: #f3ead6; --inv-footer-accent: #c9a24d;">
     <x-slot:ornament><svg aria-hidden="true"><use href="#my-ornament"/></svg></x-slot:ornament>
   </x-invitation-footer>
   ```

   It renders the back-to-top button, an ornament divider (default: a heart), the names and date
   (left out when empty), «រៀបចំឡើងដោយក្តីស្រឡាញ់ ♥» (`love="…"` to change, e.g. English-only
   templates), «Created with love by» + the ROUMDOUL block (logo, description, social icons, quick help with a Telegram button, policy links, ©; `lang="en"` for an English template). Anything inside the
   tag (besides the ornament) is shown above the brand line. Theme it with the `--inv-footer-*`
   variables (bg, text, muted, accent, ring, names-font, caps-font, kh-font), or pass
   `prefix="xx"` to also give it the template's own classes (`xx-footer`, `xx-top` + id `xx-totop`,
   `xx-divider`, `xx-footer-names`, `xx-footer-date`, `xx-footer-love`, `xx-brand`) and style those
   in the template's CSS. Social links come from `config/roumdoul.php` (`ROUMDOUL_FACEBOOK_URL`,
   `ROUMDOUL_INSTAGRAM_URL`, `ROUMDOUL_TIKTOK_URL`, `ROUMDOUL_TELEGRAM_URL` in `.env`); an empty
   link isn't shown.
2. **In admin**: Catalog → Invitation Templates → Create — one screen, everything on it:
   product name, category label, the design (from step 1), which catalog fields it uses (tick
   the checkboxes), and its pricing plans (label, price, **Max recipients**, **Retention
   (months)**, **Unlocked fields (premium extras)** per plan — this is the actual "Premium
   unlocks the map" decision). Saving this auto-creates the underlying `Service` + `ServicePlan`
   rows for you (in an internal "Digital Invitations" category) — you never touch Services directly.
3. Done — it's immediately live: buying it now creates a real `Invitation`, its demo page works
   at `/templates/{slug}/demo`, and the dashboard's field-filling form is generated
   automatically from whichever fields you ticked.

**Changing prices/plans later**: the Invitation Templates *edit* screen doesn't show the plans
repeater (editing plans on an already-selling product risks orphaning existing customers'
invitations if not handled carefully) — instead it has a **"Manage pricing & plans"** button
that takes you straight to that product's normal Service edit screen, where pricing plans work
exactly like they do for every other product in the shop.

## Shared scratch-off card (`<x-scratch-card>`)

`resources/views/components/scratch-card.blade.php` (JS `public/js/invitations/shared/scratch-card.js`, CSS
`public/css/invitations/shared/scratch-card.css`, both loaded once by the component). A foil the guest rubs away
to reveal a photo or any content; past `threshold` (default .45) the rest melts away. Used by River Love's
«If I could live a moment twice…» section and Front Page's scratch-to-reveal date (Rose Bloom still has its own built-in stamps).

```blade
<x-scratch-card :src="$photo" caption="that night we laughed" />
<x-scratch-card stamp :src="$photo" :caption="$caption" />          {{-- perforated postage-stamp frame --}}
<x-scratch-card cover="open me" :heart="false"><p>Save the date!</p></x-scratch-card>
```

Props: `src`, `alt`, `caption`, `hint` (shown until revealed), `cover` (script text on the foil), `heart`,
`ratio` (default `4/5`), `threshold`, `sound` / `revealSound` (`true` = shared sfx, a URL, or `false`), `stamp`,
`label` (aria). Theme with `--inv-scratch-foil` (comma list of colours), `--inv-scratch-ink`, `--inv-scratch-accent`,
`--inv-scratch-font`, `--inv-scratch-radius`, `--inv-scratch-frame`, `--inv-scratch-cap-font`/`-cap-color`/`-hint-color`.
Buttons/links in the slot (e.g. a lightbox button) can't be focused or tapped until revealed. A revealed card fires
a bubbling `inv-scratch:reveal` event; `window.InvScratch.setMuted(bool)` hooks it to a template's mute button.

## Shared memory map (`<x-memory-map>`)

`resources/views/components/memory-map.blade.php` (JS `public/js/invitations/shared/memory-map.js`, CSS
`public/css/invitations/shared/memory-map.css`, both loaded once by the component). A hand-drawn paper map with
heart pins joined by a dotted route; tapping a pin opens a polaroid with that place's photo, name, date and note.
Used by River Love's «Our places» section (Rose Bloom still has its own built-in map). Renders nothing when
`places` is empty, so a template can drop it in unconditionally.

```blade
{{-- usually built from story_chapters: title «Place | when» → name + date, text → note --}}
<x-memory-map :places="[['name' => 'Where we met', 'date' => 'Spring 2022', 'note' => '…', 'photo' => $url], …]" />
<x-memory-map :places="$places" river :label="$from.' & '.$name" photo-class="rl-ph-btn" />
```

Props: `places` (max 6; optional `x`/`y` 0–100 to place a pin yourself), `river` (draws a winding river),
`label` (script line on the paper), `seed` (changes the hills), `sound` (pin pop: `true` = shared `pop.mp3`, a URL, or `false`), `photoClass` (extra class on the polaroid's photo
button, which carries `data-full`/`data-cap`, so the template's own photo preview opens it), `closeLabel`.
Theme with `--inv-map-paper`, `--inv-map-ink`, `--inv-map-land`, `--inv-map-water`, `--inv-map-pin`,
`--inv-map-card`, `--inv-map-card-ink`, `--inv-map-font`, `--inv-map-note-font`, `--inv-map-radius`, `--inv-map-ratio`.
A pin tap fires a bubbling `inv-map:open` event (`detail: { index, place }`); `window.InvMap.setMuted(bool)` hooks it to a template's mute button. Without JS, the memories are listed
under the map.

## Shared falling flowers (`<x-flower-drop>`)

`resources/views/components/flower-drop.blade.php` (JS `public/js/invitations/shared/flower-drop.js`, CSS
`public/css/invitations/shared/flower-drop.css`, both loaded once by the component). Petals (and a few leaves) fall
in 3D over the whole page forever: each one spins on three axes and catches the light, near ones are bigger and
soft-focus, scrolling gives them wind, a finger brushes them aside. Taken from River Love (which still has its own
copy). Off with `prefers-reduced-motion`. Used by Vellum Garden and Front Page.

```blade
<x-flower-drop :color="$accent" />
<x-flower-drop :color="$accent" shape="rose" :leaves="false" density=".5" />
```

Props: `color` (petal colour, hex, three tones are made from it), `shape` (`blossom` | `rose`), `leaves` (mix in green
leaves, default true) + `leafColor`, `density` (1 = normal), `tap` (a tap drops a handful where the finger was,
default true), `bursts` (a revealed `<x-scratch-card>` bursts into petals and an opened `<x-memory-map>` pin lets a few
fall, default true). Layer with `--inv-flowers-z` (default 30, keep it under the template's dialogs); hide it until
something happens with your own CSS (`.tpl:not(.is-open) .inv-flowers{ opacity:0 }` fades it in).
`window.InvFlowers.drop(x, y, scale)`, `.pause()`, `.resume()`. No sound of its own: the taps, scratches and pins
that set it off already play theirs.

## Wedding templates (`templates/wedding/`)

- **Vellum Garden** — `wedding/vellum-garden.blade.php` (CSS/JS `public/{css,js}/invitations/vellum-garden.*`,
  demo `/demo/wedding/vellum-garden`, `?open=1` skips the envelope). Fine-art garden look: ivory linen, botanical line
  art that draws itself, arched photo frames, vellum cards, a wax-seal envelope the guest taps open. Uses
  `groom_name`/`bride_name`, `couple_initials` (the seal), the four parents, `event_date` (hero + save-the-date
  calendar), `khmer_date`, `countdown_enabled`, `message` (a note under the card), `story_chapters`, `event_schedule` /
  `schedule_image`, `reception_message`, `photo_gallery` + `photo_captions` (one per photo, shown under it and in the
  preview), `cover_image` (main arch; empty = first gallery photo), `venue_name`/`venue_address`/`map_url`, `qr_code` +
  `gift_account` + `gift_message`, `rsvp_enabled` + `rsvp_message`, `groom_phone`/`bride_phone`/`contact_phone`,
  `thanks_message`/`apology_message`, `music_file`/`music_url`, `accent_color` (the wax colour and the falling rose
  petals, default terracotta). Shared parts: `<x-flower-drop>` (rose petals + leaves, shown once the envelope opens),
  «Our places» `<x-memory-map>` (one pin per story chapter; a title «Place | when» gives the pin's name + date, the
  chapter text stays in the story cards) and «A little surprise»: three `<x-scratch-card stamp>` stamps hiding the last
  three gallery photos (their captions under them). The gallery runs in rows of two arches → one big photo → three
  squares (repeating; the last row takes what's left). `dress_code` shows as a card: colours named in it (Khmer like
  «ក្រែម», «បៃតងខ្ចី», «ផ្កាឈូកខ្ចី» or English like «sage», «dusty pink», or `#hex` codes) become fabric swatches, in the
  order written (max 6), with the text under them. Line art draws itself everywhere: sprigs beside section headings,
  vine dividers whose bloom opens, the story line drawing down as you scroll (each chapter's flower blooms when
  reached), leaves round the arches and the big photo, and a wreath round the closing seal.
- **Front Page** — `wedding/front-page.blade.php` (CSS/JS `public/{css,js}/invitations/front-page.*`, demo
  `/demo/wedding/front-page`). Modern editorial "wedding issue": cream paper + ink + one accent, a magazine-style cover
  (names as the masthead, cover lines, a barcode drawn from the date), a marquee band, a running header with page
  numbers, giant date numerals under an `<x-scratch-card>` foil, photo-booth strips that "print" into view, a
  «contents page» program. Uses the same fields as Vellum Garden plus `dress_code` (under the venue); `message` is the
  pull quote, `photo_gallery` fills strips of 4 (up to 16 photos), `cover_image` the cover (empty = first gallery photo),
  `accent_color` the accent and the falling rose petals (default vermilion `#c2412d`). Shared parts: `<x-flower-drop>`
  (rose petals, no leaves), «Exclusive · unseen»: three `<x-scratch-card>`s hiding the last three gallery photos, and
  «Our places» `<x-memory-map>` (one pin per story chapter, «Place | when» like Vellum Garden).

## Things that are NOT per-template concerns

- Pricing, recipient limits, retention, feature unlocks → all on `Service`/`ServicePlan`, not
  hardcoded per template.
- Media storage → always R2 (`Storage::disk('s3')`), never binary data in the database.
- Video → link-only for now (paste a YouTube/TikTok URL and embed it), no self-hosted upload.
- Expiry enforcement → handled generically by `Invitation::isExpired()` and the retention purge
  job, not something each template needs to check itself (though the public recipient route
  does check it before rendering any template).

## Photo gallery layouts (`frame` | `feed`)

`invitations/partials/photo-feed.blade.php` is a reusable vertical photo feed (tall rounded photos,
next one peeking in, fade-up entrance, tap-to-open lightbox with swipe, optional auto-scroll).
Include it with `@include('invitations.partials.photo-feed', ['photos' => $urls, 'alt' => '…'])`.

- Choose per invitation with the `gallery_feed` boolean field (free field; tick it on the template),
  or set `$fields['gallery_layout']` to `frame`/`feed`. In the Khmer Traditional Wedding template, unset =
  `feed` (its design) and the dashboard checkbox starts ticked; unticking it gives the framed grid.
  Append `?layout=feed|frame` to the URL to preview.
- Demo: `/templates/khmer-traditional-wedding/demo?photos=15` previews N photos (1–30).
- `?autoscroll=1` starts the slow auto-scroll (for screen recordings); touch/wheel/key stops it.
- `?snap=proximity|mandatory` turns on page scroll-snap for testing; the partial defaults to none.
