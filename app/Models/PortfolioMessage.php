<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioMessage extends Model
{
    protected $fillable = ['portfolio', 'locale', 'name', 'email', 'subject', 'message', 'ip_address', 'user_agent', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }
}
