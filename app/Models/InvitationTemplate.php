<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvitationTemplate extends Model
{
    use HasFactory;

    /**
     * The standard field catalog: every key a template can declare in its `fields` schema,
     * with the input type the dashboard form should render for it. Adding a genuinely new
     * field means adding one entry here, not touching the dashboard or any template file.
     */
    public const FIELD_CATALOG = [
        'sender_name' => ['label' => 'Your name (sender)', 'type' => 'text'],
        'headline' => ['label' => 'Headline', 'type' => 'text'],
        'message' => ['label' => 'Message', 'type' => 'textarea'],
        'cover_image' => ['label' => 'Cover image', 'type' => 'image'],
        'event_date' => ['label' => 'Event date', 'type' => 'datetime'],
        'venue_name' => ['label' => 'Venue name', 'type' => 'text'],
        'venue_address' => ['label' => 'Venue address', 'type' => 'text'],
        'rsvp_enabled' => ['label' => 'Enable RSVP buttons', 'type' => 'boolean'],
        'countdown_enabled' => ['label' => 'Show countdown', 'type' => 'boolean'],
        'music_url' => ['label' => 'Background music (YouTube/TikTok link)', 'type' => 'text'],
        'cta_label' => ['label' => 'Button label', 'type' => 'text'],
        'cta_url' => ['label' => 'Button link', 'type' => 'text'],
        'accent_color' => ['label' => 'Accent color', 'type' => 'color'],
    ];

    protected $fillable = [
        'service_id',
        'name',
        'slug',
        'category',
        'is_premium',
        'is_active',
        'fields',
        'view',
        'thumbnail',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'fields' => 'array',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /**
     * The standard field catalog keys this template uses (e.g. 'sender_name', 'event_date', 'venue_address').
     */
    public function usesField(string $key): bool
    {
        return in_array($key, $this->fields ?? [], true);
    }
}
