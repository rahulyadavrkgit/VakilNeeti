<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FirmProfile extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    protected $fillable = [
        'firm_name',
        'proprietor_name',
        'address',
        'city',
        'district',
        'state',
        'state_code',
        'pincode',
        'gstin',
        'email',
        'phone',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}