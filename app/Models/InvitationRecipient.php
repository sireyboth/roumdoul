<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InvitationRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'recipient_name',
        'token',
        'rsvp_status',
        'rsvp_note',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $recipient) {
            $recipient->token ??= Str::random(32);
        });
    }

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }
}
