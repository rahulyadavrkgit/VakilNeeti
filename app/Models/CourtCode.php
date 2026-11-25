<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * What a CNR court prefix stands for, e.g. "UPJL" = Jalaun district courts, Uttar Pradesh. Learned from
 * imported cases whose court complex names the district, so the same prefix is labelled on every case.
 */
class CourtCode extends Model
{
    protected $fillable = ['code', 'state_code', 'court_level', 'district'];
}
