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

For readability, `InvitationTemplate::FIELD_SECTIONS` groups every catalog key for the admin «Fields» tab:
`essentials` first (free fields almost every template uses — message, date, venue name, cover, gallery, music…), then
`premium` (exactly the fields a plan must unlock, so a field is never both «most used» and premium — a test checks
it), then free fields by purpose (`wedding`, `gifts`, `wording`, `photos`) and the event-specific groups last
(`anniversary`, `birthday`, `ceremony`). Each group is a card with its own checkbox list (heading/icon/hint in
`InvitationTemplateForm::GROUPS`); the ⓘ next to each field shows its `ADMIN_HINTS` line on hover.
This is purely layout — zero effect on gating or rendering; only a template's own `fields` and `premium_fields`
(see «Feature gating by plan») matter at runtime. A new catalog key must go into exactly one group (a test checks it); a new group also
needs an entry in `InvitationTemplateForm::GROUPS`.

**Field types the dashboard form renderer understands:** `text`, `textarea`, `image` (single
upload), `gallery` (multiple images, add/remove), `schedule` (repeatable time + label rows,
add/remove), `boolean`, `datetime`, `color`.

**Free fields — always available on every plan, once a template ticks them:**

| Field | Type | Purpose |
|---|---|---|
| `sender_name` | text | Generic "who it's from" (date-asking, breakup, etc.). River Love / Rose Bloom: the sender everywhere «Me» would show («Chetra ♡ Anna», «Forever yours, Chetra»); their editor calls it «Your name», max 40 characters (`FIELD_OVERRIDES`) |
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
| `event_date` | datetime | The date being announced/proposed (Rose Bloom: «together since» — the poster's date column and a days-together count). Stored as typed, with no zone; always read it with `App\Support\Invitations\EventDate::parse()` (Cambodia time, `config('roumdoul.event_timezone')`) — never `Carbon::parse()`, which takes it as UTC and makes countdowns 7 hours late. Countdowns get `->toIso8601String()` («…T19:00:00+07:00») for `new Date()` |
| `venue_name` | text | Location name (plain text, not a map link). Rose Bloom: a short «where it began» line on the poster |
| `music_url` | text | Background music — a YouTube/TikTok link |
| `music_file` | audio | Background music uploaded as a file (MP3/M4A/AAC, at most `InvitationManagePage::MAX_AUDIO_KB` = 10 MB ≈ 10 min). Checked in the browser before anything is sent and again on the server (size + real content type); stored under a random name in the invitation's own folder, never re-encoded (no ffmpeg). Templates play it instead of `music_url` when both are set, with `preload="none"` so guests download nothing until it plays |
| `accent_color` | color | Theme color |
| `together_since` | date | Anniversary / love letter: the day the relationship began, read as Cambodia time (`EventDate::parse()`). River Love: «Together since …» with a live days/hours/minutes/seconds count-up (River Love has no countdown, so its editor hides `event_date` and `countdown_enabled`); Rose Bloom: the poster's «together since» + days count. Both fall back to a past `event_date` (invitations made before this field). Can't be a future date |
| `love_reasons` | long text | Anniversary / love letter: reasons you love them, one per line (Rose Bloom: the tap-through cards deck; River Love: «Paper boats», one folded boat per line, max 9, a tap unfolds it into a note) |
| `photo_captions` | long text | One caption per line, in photo-gallery order (caption N belongs to gallery photo N). Rose Bloom and River Love: lines 1–4 = scratch cards (photos 1–4), 5–9 = photo strip as «Title \| small line» — the small line replaces the strip's year/tag (River Love's strip starts with photos 5+). Shown in the dashboard's media tab |
| `song_title` | text | Song name on the music card / record label (Rose Bloom: the “Now playing” card; River Love: the cover «Play …» button and its «Now playing» record card, both driving the same song); the song itself is `music_file` / `music_url` |
| `closing_line` | text | The last big line at the very end (Rose Bloom finale; River Love: shows after the guest sends a paper lantern up; `thanks_message` is the smaller line under it) |
| `photo_gallery` | gallery | Multi-image gallery — **included in every plan** (most designs have one), so a template only needs it ticked in Fields. Plans differ by how many photos it holds: **Max gallery photos** (`service_plans.max_gallery_photos`, blank = 20 = `InvitationManagePage::MAX_GALLERY_PHOTOS`) and the «more photos» add-on. Read via `$invitation->maxGalleryPhotos()` |

**Premium fields — the pricing upsell lever.** A template can still tick these into its schema,
but they only actually render for a customer whose plan has that exact key in
`service_plans.features`:

| Field | Type | Purpose |
|---|---|---|
| `venue_address` | text | Feeds a "Get directions" Google Maps link |
| `khmer_date` | text | Lunar/Khmer calendar date, written as free text |
| `rsvp_enabled` | boolean | Shows the RSVP yes/no buttons and persists responses |
| `countdown_enabled` | boolean | Shows a live countdown timer |
| `event_schedule` | schedule | Repeatable time + item rows (e.g. "5:00 PM — Ceremony") |
| `schedule_image` | image | Optional ready-made schedule picture (e.g. a kbach card). When set, templates show it **instead of** the typed `event_schedule` rows. Has no plan unlock of its own — it follows `event_schedule` via `InvitationTemplate::UNLOCKED_WITH`, and is edited in the dashboard's Program tab via the catalog entry's `section` override |
| `qr_code` | image | A QR code image upload |
| `cta_label` + `cta_url` | text | Custom call-to-action button |

If a genuinely new field is needed, add it to `InvitationTemplate::FIELD_CATALOG` (one time,
plus `FREE_FIELDS` if it should start out free (not ★) when a template ticks it, one group in `FIELD_SECTIONS`, and a one-line admin explanation
in `ADMIN_HINTS` — shown under its checkbox in admin; tests fail if either is missing) — not just to one template — so the next
template that needs something similar can reuse it too. Adding a new field TYPE (beyond the
ones above) additionally needs a new `@case()` branch in
`invitation-manage-page.blade.php`'s dynamic form renderer.

When a shared field needs different wording on one template (another label, hint or example, a
shorter `max`, a different card on the form, or `hidden` for a field the design stopped using), add it to `InvitationTemplate::FIELD_OVERRIDES`
under the template's `view` instead of changing the catalog entry every other template shares
(e.g. River Love's «Your name» and its own Headline example). New English strings go in both
`lang/en.json` and `lang/km.json`.

## Feature gating by plan

**Which fields are ★ (premium) is the admin's choice, per template** — no code change needed:

1. **Template › Fields › «★ Needs a plan (premium)»** lists every field the template ticks. Ticked = ★: only plans
   that include it get it (or the customer buys the unlock). Unticked = every plan gets it. Stored in
   `invitation_templates.premium_fields`; read with `$template->premiumFields()` / `isPremiumField($key)`. When the
   admin ticks a field that the catalog suggests as premium (the table above = `FIELD_CATALOG` minus
   `FREE_FIELDS`, `InvitationTemplate::defaultPremiumFields()`), it starts out ★; any field can be made ★ or free.
   A field the template doesn't tick keeps the catalog's suggestion. `UNLOCKED_WITH` followers (`schedule_image`)
   can't be marked themselves — they follow their field.
2. **Template › Plans › each plan › «★ Fields this plan includes»** ticks which of those ★ fields the plan gets,
   stored as catalog keys in `service_plans.features` (e.g. Basic `[]`, Standard `['venue_address', 'rsvp_enabled']`,
   VIP all). The list follows the Fields tab live. `ServicePlan::unlockedFeatures()` is what a new invitation copies.
   Any plan can also sell single ★ fields («Unlock one extra on its own»).
3. The shop's plan cards list what each plan includes automatically (guest links, gallery photos, how long it stays
   online, the ★ fields it includes ✓ and the ones it doesn't crossed out, by their short
   `InvitationTemplate::SHOP_EXTRA_NAMES`), plus the plan's optional `badge` (e.g. «Best value»).

In a template's Blade file, gate optional content with `$invitation->fieldUnlocked($key)`
(handles the free/★ split for you — free fields always pass, ★ ones check what the invitation bought):

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
`max_gallery_photos` and `features` are **copied onto the invitation** (`invitations.*`), and so is the template's
list of free fields at that moment (`invitations.free_fields`). Templates and the dashboard read the invitation's
copy (`$invitation->maxGalleryPhotos()`, `fieldUnlocked()`), so editing a plan **or marking a field ★ later** only
affects new buyers — a sold invitation never loses a field. The other way round is generous: a ★ field made free
reaches invitations already sold. Old invitations made before the copy existed fall back to their plan.

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

## Admin «Invitation defaults» (starting values for a new template)

Admin › Catalog › **Invitation defaults** (`App\Filament\Pages\InvitationDefaults`, values in
`App\Support\InvitationDefaults`, stored in the `settings` table) sets what the «New template» form starts with:
the starting plan(s) (default Basic · 100 guests · 6 photos · 6 months), add-on prices (guest $0.10, photo $0.25,
extend $6; unlock QR $0.25, map $0.25, program $0.50), the dropdown suggestions under the plan inputs, the shop
texts, sort order (2), «How to use», FAQ, and the fields ticked per category (typing «wedding» ticks most used +
premium extras + couple & family + dress code). Only the invitation template form uses them — a plain Service
plan stays blank. Unlock prices for ★ fields the design doesn't use are dropped on save. A blank shop cover image
uses a copy of the template thumbnail.

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
   (months)**, **★ Fields this plan includes** per plan — this is the actual "Premium
   unlocks the map" decision; which fields are ★ is set in the Fields tab). Saving this auto-creates the underlying `Service` + `ServicePlan`
   rows for you (in an internal "Digital Invitations" category) — you never touch Services directly.
3. Done — it's immediately live: buying it now creates a real `Invitation`, its demo page works
   at `/templates/{slug}/demo`, and the dashboard's field-filling form is generated
   automatically from whichever fields you ticked.

**Changing it later**: Catalog → Invitation Templates → **Edit** opens the same wide modal, tabs
**Basics · Fields · Plans · Shop page**. **Plans** edits the linked Service's pricing plans in place (click
a plan to open it) plus the product's **Discount** (percentage or fixed, applied to every plan's price in the
cart, shop card and product page); a plan change only affects new buyers, each sold invitation keeps its own limits
(edit those on the template's «Invitations» tab). **Shop page** edits the linked Service's product page:
**Category** (the filter it shows under on `/shop`; default «Digital Invitations»), **Sort order**
(display order, lower first, featured first), Featured, In stock, short + full description, live demo URL,
**Cover image** + **Gallery**, «How to use» and FAQ. Both tabs are also on Create (blank shop texts = the
product name). Invitation products are hidden from Catalog → Services, so these tabs are the only place to
edit them; the fields come from `ServiceForm` helpers, so a change there shows in both forms. A template
with no linked Service shows «Set up pricing» instead.

## Dress code = colour swatches (`App\Support\Invitations\DressCode`)

Every template shows `dress_code` as **colour swatches**, never as plain text only. `DressCode::colours($text)` finds the
colours named in it — Khmer words («ក្រែម», «បៃតងខ្ចី», «ក្រហមដិត», a lone «ស»), English («sage», «dusty pink») or `#hex`
codes — in the order written, each once, max 6, and returns `[['hex' => '#efe4cc', 'name' => 'ក្រែម'], …]`. Render one
swatch per colour with its name, and keep the original text under them (it may say «formal», «Khmer traditional»…). A
missing colour word goes into `DressCode::PALETTE`, not into one template. Used by Vellum Garden, Front Page and Golden Hour.

## Start a new template from the shared kit (cheapest way)

New templates should NOT copy the 200 lines of data prep + 300 lines of JS from an older template. Start from
`resources/views/invitations/starters/wedding.blade.php` (preview: `/demo/starter`): copy it to
`templates/wedding/{name}.blade.php`, rename the `st` prefix and design the look. It is built on three shared pieces:

- **`App\Support\Invitations\WeddingData`** — `$w = WeddingData::from($fields, $invitation, $recipient ?? null,
  ['accent' => '#…', 'closing_line' => '…'])` prepares every wedding field once, already gated and with media turned into
  URLs: names (`groom`, `bride`, `coupleTitle`, `initials`, `groomParents`…), dates (`dateKhmer`, `timeKhmer` incl.
  `end_time`, `khmerDate`, `isoDate` for countdowns, `calendar`, `showCountdown`), venue (`venueName`, `venueAddress`,
  `mapUrl`, `mapEmbed`, `showVenue`), words (`message`, `receptionText`, `closingLine`, `letters`), photos (`cover`,
  `background`, `groomPhoto`, `bridePhoto`, `gallery`, `captions`), `story` («Place | when» split), `schedule` (Khmer times)
  / `scheduleImage`, guest notes (`dressCode`, `dressColours`, `whatToBring`, `rsvpBy`), RSVP (`showRsvp`, `rsvpUrl`,
  `rsvpQuestion`), gift (`giftQr`, `giftAccount`, `giftNumber`, `giftMessage`), `contacts`, `ctaLabel`/`ctaUrl`, music.
  Empty / locked = empty value, so `@if ($w->giftQr)` is the whole check. Helpers: `khDigits()`, `khDate()`, `khTime()`,
  `scheduleTime()`, `isKhmer()`, `pretty()`. Tested in `tests/Feature/WeddingDataTest.php`.
- **`<x-invite-kit :w="$w" wait />`** (`resources/views/components/invite-kit.blade.php`) — music `<audio>`, one sound
  button (mutes music + sfx + shared components), «now playing» pill, the sfx list, and loads
  `public/js/invitations/shared/invite-kit.js` + `public/css/invitations/shared/invite-kit.css`.
- **`InviteKit({ root, cover })`** (JS) — `sfx()`, `startSong()`, `lock()`, `words()`, `reveal()` (scroll check, not
  IntersectionObserver; ready-made `class="ik-r" data-ik="up|zoom|left|right|pop|blur|mask|fold|words"`),
  `onScroll()`, `lightbox()` (every `[data-ik-photo]` button: FLIP zoom, prev/next, swipe, keys, counter, caption),
  `countdown()`, `rsvp()`, `copy()`, `toast()`, `burst()`, `fly()` (cover photo → hero, shared element) and `settle()`
  (start a motion only after heavy layout/paint is done — what fixed Eternal Vows' stutter). Full API at the top of the
  JS file. Theme with `--ik-accent`, `--ik-ink`, `--ik-paper`, `--ik-lb-bg`, `--ik-cap-font`, `--ik-font`.

The six wedding templates made before the kit (Golden Hour, Paper Bloom, Eternal Vows, Love Bubble, Kbach Meas, Kbach
Chhouk) still carry their own copies; move one onto the kit only when it is being changed anyway.

Smooth animation rule (learned on Eternal Vows): animate only `transform` / `opacity` (on their own layers); never
animate `clip-path` shapes or `filter` on large painted areas during a transition, and do heavy DOM work (showing the
page) while nothing moves, then `kit.settle()` before the next motion.

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

- **Memory Hall** — `wedding/memory-hall.blade.php` (prefix `mh`, CSS `public/css/invitations/memory-hall.css`, 3D scene
  `resources/js/invitations/memory-hall.js` — a **Vite entry** (in `vite.config.js`) because it bundles three.js; demo
  `/demo/wedding/memory-hall`, `?open=1` skips the cover, `?open=1&stop=N` starts at stop N, `?flat=1` = no 3D). A real-time
  3D wedding hall walked in first person. Cover: night courtyard, stone facade, lanterns, a grand walnut double door with the
  `couple_initials` monogram; «បើកទ្វារ» swings the doors open in a flood of light and the camera walks in. Inside: marble
  floor, red aisle carpet, ivory + walnut panelled walls, coffered barrel vault, chandeliers (4 point lights follow the
  guest), falling petals and gold dust. The hall is split into wings by doors that open as the guest walks up, each with
  its name on the lintel (Welcome → Our day → Memories → For our guests → Forever). Stops, in order, each only when its
  fields are filled: welcome (`cover_image` framed + a welcome easel with names, date, guest name; panel: parents,
  `reception_message`, date, `khmer_date`, time + `end_time`), couple (`groom_photo`/`bride_photo` + brass name plates),
  `message` (framed letter + monogram), date (big date + calendar with a heart, countdown in the panel), `story_chapters`
  (one framed chapter each), programme (`schedule_image` framed or a programme easel from `event_schedule`), gallery
  (`photo_gallery` along both walls, `photo_captions` on brass plates), venue easel (`venue_name`/`venue_address`/`map_url`),
  guest notes (`dress_code` swatches painted in a frame + in the panel, `what_to_bring`, `rsvp_deadline`), RSVP at a
  guestbook lectern (the guest's name is written into the book after replying), gift box on a pedestal (`qr_code`,
  `gift_account`), contacts easel (+ `cta_label`/`cta_url`), `thanks_message`/`apology_message` as framed letters, and the
  finale: a floral arch, candles, `background_image` (empty = cover) on the end wall with `closing_line` (default «Forever
  starts here»), the «send love» button and the shared footer (its «back to top» walks back to the entrance). Walking: swipe
  up/down, wheel, W/S/↑/↓, ‹ › buttons, stop chips, tap the floor, or «ដើរទស្សនា» (auto tour); look: drag sideways or
  A/D/←/→ — the gaze also turns by itself to the photo being passed. Tap a photo on a wall (or its thumbnail in the panel):
  the camera walks up to it, then the shared FLIP preview opens out of the frame. Each stop's details show in a glass panel
  (bottom sheet on phones, side card from 900px). No WebGL, `prefers-reduced-motion`, `?flat=1` or the page button →
  every stop as a normal scrolling page (same markup; the cube button goes back to 3D). Signs are painted on canvases after
  their fonts load (Cormorant Garamond → Noto Serif Khmer, Great Vibes → Moulpali; body Kantumruy Pro). Sounds: the kit's
  sfx plus `footstep.wav` (steps) and `whoosh.wav` (doors) in `assets/sfx/`. **Photos on the walls are WebGL textures, so
  the S3/R2 bucket must send CORS headers (GET from the site's domain)**; without them the frames show a painted monogram
  instead (the panel thumbnails and the preview still show the real photos). `accent_color` is the gold (default `#c9a24d`).

- **Blossom Swing** — `wedding/blossom-swing.blade.php` (prefix `bs`, CSS/JS `public/{css,js}/invitations/blossom-swing.*`,
  demo `/demo/wedding/blossom-swing`, `?open=1` skips the cover). Soft pink watercolour, built on `WeddingData` + the kit.
  The head-flower frame (`assets/head-flowers/Cover-Frame.png`, cut into `garland-top.webp` + `garland-bottom.webp`) sways
  in an endless breeze. `$swing()` builds each garland as a branch of nested bones (one per flower cluster, the middle one
  the root, each outer one turning round the joint it shares with its neighbour), so pieces never tear apart however far
  they swing and the motion adds up towards the ends. Angles (relative to the parent): three sines with unrelated periods
  (never visibly repeats) reaching outer bones later, a slow rise and fall of the wind, a soft damped spring that gusts kick
  (tap, finger brushing through, fast scroll, opening, RSVP, «send love»), a lagging skew so the tips bend. Each garland
  **blooms** when it first comes into view: a folded bud that opens outward from the middle, ring by ring, then breathes.
  Seams: the outer piece fades in over the seam, the inner one stays solid under it and fades out softly (no hard cut even
  mid-bloom); garlands reach past the frame edges so a tilt never opens a gap. Only garlands on screen move; none with
  reduced motion. Garlands frame the cover; after opening, one head garland stays pinned to the top of the screen and one
  bouquet to the bottom (`.bs-sticky`, page-column wide, z-index 25: under the petals, sound button and photo preview)
  while the page scrolls between them; the RSVP card keeps its own hanging crown.
  Under the head garland: the Khmer title artwork `assets/text/kh-wedding.webp` (from `kh-wedding.png`) with a light
  sweeping across its letters, then `headline` as a small line (hidden when it only repeats the title).
  The cover shows no photo: the names (or `title_image`) and the date sit inside the floral gold frame
  `assets/img-frame/3.webp` (trimmed from `3.png`), which floats gently. Opening «blossom burst» (`blossomBurst()` in the
  JS): gold rings from the button, the words float away, the garlands part, a pink-white light blooms out of the button to
  fill the screen with a spiral of petals and leaves flying toward the camera; under the light the cover is swapped for the
  page and the frame's art floats up above it (`floatFrame()`). The light stays up: the second act «flower wall»
  (`flowerWall()`, started 0.7 s into the burst, while the frame is still gliding, so the blooms take it in; the screen is
  all flowers about 2 s after the tap) opens dozens of watercolour blooms
  (`assets/head-flowers/bloom-1…5.webp`, cut from the frame art) from the middle outward until they cover the screen; the
  light is removed underneath, and after a hold the flowers part from the centre under a petal shower — the guest sees
  the page for the first time here. The first section (`.bs-first`) is NOT a second cover: it is the invitation itself
  (the Khmer title artwork, gold wedding rings `assets/img-frame/ring.webp` floating with a glint, parents, «You are invited», guest name, `reception_message`, the couple's names, date + `khmer_date` + time) inside the
  garland panel; its garlands bloom and its lines rise in one by one as the flowers part (`.bs-landed`), then scrolling
  unlocks and hearts burst at the guest's name. Transform/opacity only; reduced motion = a plain fade. Buttons: pink watercolour body inside
  a double gold hairline (the frame's lines), a light sweep, gold rings rippling from the open button;
  `cover_image` is not used (the finale photo is `background_image`).
  Background: `assets/background/image/bg-pink-smoke-{tall,wide}.webp` (from `bg-pink-smoke.png`; tall = rotated, for
  phones), two copies drifting against each other. Sound: `rustle.wav` (in `assets/sfx/`) on every gust and bloom.
  `accent_color` is the rose (default `#d0668c`), falling petals via `<x-flower-drop>`. Fonts: Great Vibes → Moulpali,
  Cormorant Garamond → Noto Serif Khmer, body Kantumruy Pro.

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

- **Golden Hour** — `wedding/golden-hour.blade.php` (CSS/JS `public/{css,js}/invitations/golden-hour.*`, demo
  `/demo/wedding/golden-hour`, `?open=1` skips the cover). Cinematic and photo-first, with animation everywhere: the
  cover is the couple's photo (`cover_image`, empty = first gallery photo) full screen with a slow Ken Burns zoom, a warm
  light leak and gold dust; `headline` (default «We are getting married»), the names rise letter by letter (a Khmer
  name rises in one piece; `title_image` replaces the typed names), then the date, the guest name and a glowing «បើកសំបុត្រ ·
  Open invitation» button. A tap splits the photo into two doors that swing open on a seam of light and a flash. The
  page: hero (the same photo zooming back), a names marquee, the invitation card (parents, guest, `reception_message`,
  date + `end_time`, `khmer_date`), «The bride & groom» (`groom_photo`/`bride_photo`), `message` revealed word by word,
  a calendar where a heart draws itself round the day + a flip-clock countdown, a story timeline that draws as you
  scroll, the program (`event_schedule` / `schedule_image`), a gallery (mask wipes, 3D tilt, lightbox, `photo_captions`)
  + an endless photo reel, venue + map, guest notes (`dress_code` as colour swatches, `what_to_bring`, `rsvp_deadline`), RSVP (hearts
  burst out), gift QR with a scan line + «copy account number» (`gift_account`), contacts + `cta_label`/`cta_url`,
  thanks/apology, and a finale on `background_image` (empty = last gallery photo) with `closing_line` (default «Forever
  starts today») and a «send love» heart button. `song_title` slides out next to the sound button while the song plays.
  `accent_color` is the gold (default `#d4a76a`). Reveals are checked against the viewport on scroll, not with
  IntersectionObserver: an element that starts fully clipped (the mask wipe) never counts as visible to the observer.
  Fonts: Cormorant Garamond → Noto Serif Khmer, Great Vibes → Moulpali, body Kantumruy Pro. Khmer lines never get wide
  letter-spacing (only `.gh-eyebrow.en`).

- **Paper Bloom** — `wedding/paper-bloom.blade.php` (CSS/JS `public/{css,js}/invitations/paper-bloom.*`, demo
  `/demo/wedding/paper-bloom`, `?open=1` skips the cover). Handmade layered paper-cut, bright (blush / sage / butter /
  cream; `accent_color` is the front sheet, the ribbon-free accents and the confetti, default `#e58f8f`). Cover: the
  couple's photo (`cover_image`, empty = first gallery photo) seen through four paper arch windows with scalloped edges
  and paper flowers, drawn in PHP (`$archWindow`: the window outline walked in equal steps, each step bulging inward);
  the sheets shift in depth with the pointer / phone tilt (iOS asks permission on the tap) and drift by themselves
  when nothing moves. A satin ribbon with a bow is tied across the arch: tapping the bow unties it, the ribbon falls,
  the sheets peel past the camera one after another (each tilting its own way and blurring as it gets close), the
  cover photo flies out of the cover and lands as the hero photo (a fixed clone animated with the Web Animations API —
  the hero shows the cover photo for that reason), the page opens through a circle growing from the bow, then the
  hero's paper rings wrap round the photo and its words rise. Page: every section is a coloured paper sheet with a
  scalloped top edge; headings get paper flower bunches that pop up like a pop-up book. Hero arch (another gallery
  photo inside two paper rings), stitched invitation card with the guest's name on a kraft tag that swings with scroll
  speed, `groom_photo`/`bride_photo` in scalloped frames, `message` on a sticky note, a ring-bound tear-off calendar
  (the day circled by hand) + flip-card countdown, `story_chapters` as polaroids pegged on a clothesline (scroll
  sideways, they sway; tap flips one to its text), the program as paper tickets (or `schedule_image`), the gallery as a
  swipe deck (drag / arrows; tap = preview; `photo_captions` written under each) + a polaroid grid of every photo; the
  preview zooms out of the tapped photo (FLIP), has prev/next, swipe/drag, ←/→/Esc, a counter, and flies back on close, a folded paper map that unfolds on
  tap (the real Google map fades in after), dress code swatches pinned on a card + `what_to_bring` / `rsvp_deadline`
  as tags, RSVP that folds into a paper plane and flies off to a thank-you card, a gift box that opens to the QR
  (+ copy account number), contacts + `cta_label`/`cta_url`, thanks/apology on lined letter paper, finale with
  `background_image` as a polaroid (hidden when empty), `closing_line` (default «Happily ever after») and a «throw
  petals» button. Paper confetti (hearts + petals flipping in 3D) on taps and bursts, a few petals drift down all
  the time. Backgrounds are never blank: the cover sheet has hearts pressed into the paper (`#pb-press`), a garland and
  flower clusters (more out on the sides for wide screens); each page sheet colour has its own printed pattern (dots,
  hearts, sprigs, gingham) and every section gets `$decor()`: flower clusters peeking in from its edges (drifting with
  scroll) and small floating paper bits. Shared SVG pieces are `<g>` in `<defs>` (not `<symbol>`: a `<use>` of a symbol with no size scales it
  to the whole drawing). Fonts: Fraunces → Noto Serif Khmer, Allura → Moulpali, body Kantumruy Pro; Khmer captions
  never use the script face.

- **Kbach Chhouk** — `wedding/kbach-chhouk.blade.php` (prefix `kc`, CSS/JS `public/{css,js}/invitations/kbach-chhouk.*`,
  demo `/demo/wedding/kbach-chhouk`, `?open=1` skips the cover). Modern, light Khmer wedding: ivory silk with a hol (ikat)
  pattern, jade (`accent_color`, default `#2f7a5a`), lotus pink and fine gold kbach line art (phni curls, kanok vines, pka
  chan, lotus buds and blooms) drawn procedurally in PHP, lines drawing themselves. Cover: a lotus pond at dawn, the couple
  photo (`cover_image`) in a lotus-petal frame rising from the water; «បើកសំបុត្រ» sends out ripples, a giant 3D lotus
  closes and blooms open petal by petal while the photo flies into the hero frame. Sections: invitation + parents in a
  kbach frame, bride & groom petal frames, note between vines, Khmer date + lunar date + calendar + lotus-medallion
  countdown, programme as a lotus stem whose buds bloom on scroll (or `schedule_image`), love story along a winding river
  with lotus lanterns, gallery mosaic (lotus arches, hol frames, circles) with a full zoom preview, venue with a map that
  wipes open, dress code as silk swatches, RSVP that floats away as a lantern, gift QR under folding petals, contacts +
  custom button, thanks/apology letters, a twilight finale with floating lotus lanterns, and the shared footer.

- **Eternal Vows** — `wedding/eternal-vows.blade.php` (prefix `ev`, CSS/JS `public/{css,js}/invitations/eternal-vows.*`,
  demo `/demo/wedding/eternal-vows`, `?open=1` skips the cover). Classic Western black-tie: ivory paper, deep ink and
  champagne-gold foil (`accent_color` is the gold, default `#b08d57`) whose highlight moves with scroll, pointer and tilt.
  Cover: an ivory damask stage between emerald velvet curtains tied back with gold rope, a laurel monogram crest
  (`couple_initials`), `headline`, the couple photo (`cover_image`) in a gilded arch frame drawn in PHP, names (or
  `title_image`), the date in Roman numerals, the guest's name on a brass plaque and an «Open» button. Opening: the text
  lifts away, the curtains sweep closed behind the frame, a light sweep crosses them, then they part while the frame flies
  into the hero (FLIP), gold dust bursting. Page: hero with string lights swaying with scroll speed; foil-stamped
  invitation card; `groom_photo`/`bride_photo` in oval cameos; `message` as a quote; a Roman clock whose hands spin to the
  wedding hour + countdown tiles; `story_chapters` as a gold timeline drawing on scroll; programme (or `schedule_image`) as
  an «Order of service» card with clinking champagne coupes; gallery as a salon wall of gilded frames that hang and sway,
  opening a FLIP preview; venue with an engraved map (real map revealed on tap); `dress_code` as fabric chips on a brass
  rail + `what_to_bring` / `rsvp_deadline` tent cards; RSVP reply card that gets a foil stamp and turns over; gift QR in a
  velvet ring box (+ copy account); calling cards + custom button; letters; finale with `background_image`, `closing_line`
  (default «Forever & always») and a «raise a toast» button.

- **Love Bubble** — `wedding/love-bubble.blade.php` (classes `lb2-`, CSS/JS `public/{css,js}/invitations/love-bubble.*`,
  demo `/demo/wedding/love-bubble`, `?open=1` skips the cover). Cute Gen Z: candy pastels, chunky ink outlines, clay
  shadows, stickers and Y2K sparkles; `accent_color` is the pop colour (default `#ff7eb6`). Cover: an Instagram-style story
  of `cover_image` with progress bars, a profile bubble (`couple_initials`), stickers popping on (`headline`, date), «You've
  got an invite, {guest}!» and a squishy «Open» button; opening jelly-bounces the story, the stickers fly off, a wobbly blob
  hole grows through it and the photo flies into the hero's blob frame at the same crop. Page: hero with draggable
  stickers + a scroll-speed marquee, the invitation as a chat thread typing itself in (parents, `reception_message`,
  names, date + `khmer_date` + `end_time`, venue, `message`), «It's a match!» `groom_photo`/`bride_photo` cards, a
  boarding-pass ticket (tear the stub), «loading wedding… %» + slot countdown, a «now playing» card (`song_title`),
  `story_chapters` as 3D swipe cards, the programme as a self-ticking to-do list (or `schedule_image`), a sticker-bomb
  gallery with a FLIP preview, a cartoon map + real map on tap, `dress_code` as a colour picker (a shirt + gown take the
  tapped colour), `what_to_bring`/`rsvp_deadline` as notification cards, emoji RSVP (🥳/🥲, emoji rain), the gift QR as a
  «scan me» sticker, chat-app contact cards + custom button, a «Decorate us!» sticker sheet, Notes-app letters, and a
  heart-shaped finale photo (`background_image`) with `closing_line` and a «send love» counter. Fonts: Fredoka / Gochi
  Hand → Kantumruy Pro, Koulen for big Khmer names.

- **Kbach Meas** — `wedding/kbach-meas.blade.php` (prefix `km`, CSS/JS `public/{css,js}/invitations/kbach-meas.*`, demo
  `/demo/wedding/kbach-meas`, `?open=1` skips the cover). Royal traditional Khmer wedding: gold kbach on deep crimson,
  alternating with ivory silk sections; `accent_color` is the gold (default `#c9a14a`). All ornament is drawn in PHP: the
  temple-gate ogee arch (`$archSegs`, walked in equal steps by `$sample` so the kbach phni flames stand along its normal;
  finial, nāga curls, beads + pka chan, carved pilasters), kanok vines, dividers, corners, mandalas (`#km-mandala`) and an
  Angkor border band; lines draw themselves and the gold shimmers via an animated SVG gradient (`#km-gold`). Cover: the
  couple photo (`cover_image`) inside the gold gate, names, date, the guest's name on a gold plaque; «បើកសំបុត្រ» opens a
  seam of light, the facade splits into two carved doors that swing open in 3D, gold lotus petals float out, the page
  opens through a growing circle and the photo flies (FLIP) into the hero arch. Page: hero with a mandala turning with
  scroll; invitation card (guest plaque swinging with scroll speed); `groom_photo`/`bride_photo` in small gates; `message`
  in a gold cartouche; the auspicious day (lunar date in Moul, countdown in kbach medallions); `story_chapters` as
  palm-leaf (sleuk rith) strips with a red cord drawing down on scroll; programme as a gold line with a lotus at each rite
  (or `schedule_image`); gallery in carved gold frames and arches with a FLIP preview; venue + map; `dress_code` as hanging
  silk sampot swatches; RSVP sealed into a crimson envelope that flies off; gift envelope that opens to the QR; contacts +
  custom button; letters; finale with a gold lotus blooming with scroll, `closing_line` (default «សូមអរគុណ») and «throw
  gold petals». Fonts: Cinzel → Moul, Cormorant Garamond → Noto Serif Khmer, Pinyon Script → Moulpali, body Kantumruy Pro.

## Birthday templates (`templates/birthday/`)

Demo sample data: `InvitationTemplateDemoController::birthdayDemoFields()` (any `/demo/birthday/...` path). Birthday
templates use `WeddingData` for the shared prep (dates, venue, gallery, story, schedule, notes, RSVP, gift, music) and add
`celebrant_name`, `turning_age` and `sender_name` (the host) themselves via `$w->field()`.

- **Chrome Bow** — `birthday/chrome-bow.blade.php` (prefix `cb`, CSS/JS `public/{css,js}/invitations/chrome-bow.*`, demo
  `/demo/birthday/chrome-bow`, `?open=1` skips the cover). Modern Pinterest party look: pearl + bubblegum (`accent_color`,
  default `#ff4fa0`), puffy chrome balloon numerals (`turning_age`, an SVG specular-light filter `#cb-puff`), coquette
  satin bows, a faint checkerboard, drifting iridescent blobs and glossy balloon clusters in the desktop side space.
  Cover: a party scene to play — «PARTY TIME!» bunting with a spinning disco ball hanging from it, turning disco light
  spots, a confetti drizzle, a comic «Psst… {guest}! 👀» bubble, and a bouquet of 6 glossy balloons round the
  `cover_image` (a clear bubble balloon) + the chrome age. Each tap pops a balloon (sound, slivers, comic «POP!», its
  string drops) and adds a sticker word to «មក ជប់លៀង ជាមួយ គ្នា ណា! 🎉»; the last pop (or «pop them all», which
  chain-pops the rest) sets the disco off, bursts the photo balloon and flies the photo into the hero. On the page,
  a balloon floats up every few seconds; tap it to pop it. Page: hero (`headline`, `celebrant_name` or `title_image`,
  «turning N»), marquee bands, a holographic «ADMIT ONE» ticket that tilts with the finger/scroll (`reception_message`,
  date, `khmer_date`, venue, host), `message` as a pull quote, **Make a wish**: a Lambeth-piped cake with number candles
  from `turning_age` (3 striped candles when empty) — tap each to blow it out, all out = confetti + «Wish granted»,
  save-the-date calendar + chrome countdown, `story_chapters` «Place | when» as a «Through the years» timeline, the
  programme as a party playlist (or `schedule_image`), the gallery as a Pinterest mood board (masonry, captions, FLIP
  preview), venue + map, `dress_code` as glossy balloons you can tap, `what_to_bring` / `rsvp_deadline` stickers, emoji
  RSVP (confetti cannons), a gift box that opens to the QR (+ copy account), `contact_phone` + `cta_label`/`cta_url`,
  thanks/apology notes on lined paper, finale (`background_image`, `closing_line` default «Let's party!», confetti
  button). Fonts: Bricolage Grotesque → Kantumruy Pro, Instrument Serif → Noto Serif Khmer / Koulen (Khmer names).

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
