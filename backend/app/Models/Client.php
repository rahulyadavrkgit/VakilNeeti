<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use \App\Models\Concerns\BelongsToUser;
    use SoftDeletes;

    protected $fillable = [
        'client_code','client_type','first_name','middle_name','last_name',
        'display_name','mobile','alternate_mobile','email','address','city',
        'district','state','country','pin_code','occupation',
        'organisation_name','referred_by','notes','status'
    ];

    public function caseParties(): HasMany
    {
        return $this->hasMany(CaseParty::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClientActivity::class);
    }

    public function getTotalInvoicedAttribute(): float
    {
        return (float) $this->invoices()->sum('total_amount');
    }

    public function getTotalReceivedAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getOutstandingAttribute(): float
    {
        return $this->total_invoiced - $this->total_received;
    }
}