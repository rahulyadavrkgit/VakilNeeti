<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvocateProfile extends Model
{
    protected $fillable = [
        'gender',
        'photo_path',
        'years_experience',
        'graduation_year',
        'district',
        'state_code',
        'bar_registration_year',
        'languages_spoken',
        'specializations',
        'practice_areas',
        'bar_council_registration_number',
        'city',
        'region',
        'pincode',
        'court',
    ];

    protected function casts(): array
    {
        return [
            'languages_spoken' => 'array',
            'specializations' => 'array',
            'bar_registration_year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
