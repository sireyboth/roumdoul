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
  Public URL: `/invite/{invitation-slug}/{token}`.

## The standard field catalog

Templates should compose from this catalog instead of inventing new field keys. Every field
lives in one flat list, `InvitationTemplate::FIELD_CATALOG` — there's no separate per-event-type
catalog. A template just ticks which of these it uses; unrelated templates are unaffected by
adding more entries, so the catalog is meant to keep growing as new event types show up.

For readability, `InvitationTemplate::FIELD_SECTIONS` additionally groups every catalog key into
`universal` (reusable by any template), `wedding`, or `birthday` — purely cosmetic, driving the
three separate checkbox lists on the admin "Fields this design uses" screen (Filament's
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
| `celebrant_name` | text | Birthday-specific name |
| `turning_age` | text | Birthday-specific "turning ___" |
| `headline` | text | Big title text |
| `message` | long text | The main personalized paragraph / wish |
| `cover_image` | image | Hero photo |
| `event_date` | datetime | The date being announced/proposed |
| `venue_name` | text | Location name (plain text, not a map link) |
| `music_url` | text | Background music — a YouTube/TikTok link, not an upload |
| `accent_color` | color | Theme color |

**Premium fields — the pricing upsell lever.** A template can still tick these into its schema,
but they only actually render for a customer whose plan has that exact key in
`service_plans.features`:

| Field | Type | Purpose |
|---|---|---|
| `venue_address` | text | Feeds a "Get directions" Google Maps link |
| `khmer_date` | text | Lunar/Khmer calendar date, written as free text |
| `rsvp_enabled` | boolean | Shows the RSVP yes/no buttons and persists responses |
| `countdown_enabled` | boolean | Shows a live countdown timer |
| `photo_gallery` | gallery | Multi-image gallery |
| `event_schedule` | schedule | Repeatable time + item rows (e.g. "5:00 PM — Ceremony") |
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

## Things that are NOT per-template concerns

- Pricing, recipient limits, retention, feature unlocks → all on `Service`/`ServicePlan`, not
  hardcoded per template.
- Media storage → always R2 (`Storage::disk('s3')`), never binary data in the database.
- Video → link-only for now (paste a YouTube/TikTok URL and embed it), no self-hosted upload.
- Expiry enforcement → handled generically by `Invitation::isExpired()` and the retention purge
  job, not something each template needs to check itself (though the public recipient route
  does check it before rendering any template).
