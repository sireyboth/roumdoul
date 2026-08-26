<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteAssessmentRequest extends Model
{
    protected $fillable = ['full_name', 'phone', 'service_needed', 'location', 'message'];
}
