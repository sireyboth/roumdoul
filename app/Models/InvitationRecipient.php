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
        'open_count',
        'last_viewed_at',
        'revoked_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'last_viewed_at' => 'datetime',
        'open_count' => 'integer',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $recipient) {
            $recipient->token ??= static::newToken();
        });
    }

    /** Length of a guest link code: /i/{code}. 62^12 ≈ 3·10^21 codes, so links can't be guessed. */
    public const TOKEN_LENGTH = 12;

    /** A fresh random code for a guest link (letters + digits, unique). */
    public static function newToken(): string
    {
        do {
            $token = Str::random(self::TOKEN_LENGTH);
        } while (static::where('token', $token)->exists());

        return $token;
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
