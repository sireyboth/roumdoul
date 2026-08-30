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
  <x-invitations.map :address="$fields['venue_address']" />
@endif
```

Tier 1 fields are never gated — they're always available regardless of plan.

## Steps to add a new template

1. **In admin:** create a `Service` (name, price) with its `ServicePlan`s (recipient limits via
   `max_recipients`-equivalent plan tiers, `retention_months`, `features`) — same as any product
   today.
2. **Create the `InvitationTemplate` row:** name, category, slug, `is_premium`, `fields` (the
   catalog keys this design uses), `view` (the Blade view path).
3. **Build one Blade file** for the design at `resources/views/invitations/templates/{slug}.blade.php`.
   It always receives:
   - `$recipientName` — the personalized name for this specific link
   - `$fields` — an array keyed by whatever this template's schema declared
   - `$invitation` — for feature-gate checks (`$invitation->hasFeature(...)`)

   Beyond that contract, the file is fully custom — any layout, colors, animation, fonts.
4. Reuse shared components where they fit: `<x-invitations.map>`, `<x-invitations.countdown>`,
   `<x-invitations.rsvp-buttons>` (build these once, share across every template that needs them).
5. Done — it shows up in the dashboard's template picker and at `/templates/{slug}/demo`
   automatically, with placeholder data for preview. No routing or dashboard code changes needed.

## Things that are NOT per-template concerns

- Pricing, recipient limits, retention, feature unlocks → all on `Service`/`ServicePlan`, not
  hardcoded per template.
- Media storage → always R2 (`Storage::disk('s3')`), never binary data in the database.
- Video → link-only for now (paste a YouTube/TikTok URL and embed it), no self-hosted upload.
- Expiry enforcement → handled generically by `Invitation::isExpired()` and the retention purge
  job, not something each template needs to check itself (though the public recipient route
  does check it before rendering any template).
