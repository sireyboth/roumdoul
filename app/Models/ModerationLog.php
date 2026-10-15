<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModerationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['action', 'subject_type', 'subject_id', 'actor_id', 'reasons', 'content_hash'];

    protected $casts = ['reasons' => 'array'];

    /**
     * Records a moderation event. Never pass raw comment text — only a reason list and, if
     * useful, the hash from ContentModerator::fingerprint().
     */
    public static function record(string $action, ?string $subjectType = null, ?int $subjectId = null, array $reasons = [], ?string $hash = null, ?int $actorId = null): self
    {
        return static::create([
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'actor_id' => $actorId,
            'reasons' => $reasons ?: null,
            'content_hash' => $hash,
        ]);
    }
}
