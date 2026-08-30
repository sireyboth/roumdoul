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

Templates should compose from this catalog instead of inventing new field keys. Most new
templates need zero new fields — they just declare which of these they use.

**Tier 1 — every template uses these:**

| Field | Type | Purpose |
|---|---|---|
| `sender_name` | text | Who it's from |
| `headline` | text | Big title text |
| `message` | long text | The main personalized paragraph |
| `cover_image` | image | Background/hero photo |

**Tier 2 — event-based templates opt into these:**

| Field | Type | Purpose |
|---|---|---|
| `event_date` | datetime | The date being announced/proposed |
| `venue_name` | text | Location name |
| `venue_address` | location | Feeds the `<x-invitations.map>` component |
| `rsvp_enabled` | boolean | Shows/hides the RSVP yes/no buttons |
| `countdown_enabled` | boolean | Shows/hides a countdown timer |

**Tier 3 — optional extras:**

`gallery_images`, `music_url` (external link, not an upload — see note below),
`cta_label` + `cta_url`, `accent_color`.

If a genuinely new field is needed, add it to this catalog (one time), not just to one
template — so the next template that needs something similar can reuse it too.

## Feature gating by plan

Optional Tier 2/3 features (map, countdown, RSVP, ...) are gated by which `ServicePlan` the
customer bought, via `service_plans.features` (JSON array, e.g. `['map', 'countdown']`). This
is set in admin exactly like price — it's how a template earns you an upsell ("Plan 2 unlocks
the map") without any code change per feature.

In a template's Blade file, gate optional components with:

```blade
@if ($invitation->hasFeature('map'))
  {{-- render the map here --}}
@endif
```

Tier 1 fields are never gated — they're always available regardless of plan. Shared components
like a reusable map/countdown/RSVP block (`<x-invitations.map>` etc.) don't exist yet — the one
real template so far (date-asking) hasn't needed them. Build the first one when a template
actually needs it, not preemptively.

## Steps to add a new template

Step 1 is code (needs a developer). Step 2 is fully self-serve in Filament admin
(**Catalog → Invitation Templates**), no code or deploy, and no separate trip to "Services"
needed — that screen is only for real subscription products (Gemini, ChatGPT, etc.); it never
shows invitation products at all.

1. **Build the Blade file first** — `resources/views/invitations/templates/{name}.blade.php`.
   It always receives:
   - `$recipientName` — the personalized name for this specific link
   - `$fields` — an array keyed by whatever this template's schema declares
   - `$invitation` — for feature-gate checks (`$invitation->hasFeature('map')`, etc.)

   Beyond that contract, the file is fully custom — any layout, colors, animation, fonts. The
   admin "Design (Blade view)" dropdown (step 2) only lists files that already exist here, so
   this has to happen first — you can't pick a file that isn't on disk yet.
2. **In admin**: Catalog → Invitation Templates → Create — one screen, everything on it:
   product name, category label, the design (from step 1), which catalog fields it uses (tick
   the checkboxes), and its pricing plans (label, price, **Max recipients**, **Retention
   (months)**, **Unlocked features** per plan — this is the actual "Plan 2 unlocks the map"
   decision). Saving this auto-creates the underlying `Service` + `ServicePlan` rows for you
   (in an internal "Digital Invitations" category) — you never touch Services directly.
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
