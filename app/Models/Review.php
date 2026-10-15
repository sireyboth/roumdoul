<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'customer_name',
        'email',
        'rating',
        'source',
        'is_approved',
        'reported_at',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
        'reported_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
