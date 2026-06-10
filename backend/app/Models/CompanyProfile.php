<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'company_name',
        'company_address',
        'company_phone',
        'company_logo_initials',
        'is_training',
    ];

    protected $casts = [
        'is_training' => 'boolean',
    ];
}
