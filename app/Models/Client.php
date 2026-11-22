<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use BelongsToUser;
    use SoftDeletes;

    /**
     * Client types that are companies rather than people.
     *
     * @var list<string>
     */
    public const COMPANY_TYPES = ['company', 'organisation'];

    protected $fillable = [
        'client_code', 'client_type', 'company_id', 'first_name', 'middle_name', 'last_name',
        'display_name', 'mobile', 'alternate_mobile', 'phone', 'email', 'website', 'address', 'locality', 'city',
        'district', 'state', 'country', 'pin_code', 'occupation',
        'organisation_name', 'referred_by', 'notes', 'status',
    ];

    public function isCompany(): bool
    {
        return in_array($this->client_type, self::COMPANY_TYPES, true);
    }

    /**
     * The company this person works for.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(self::class, 'company_id');
    }

    /**
     * The people who work for this company.
     */
    public function people(): HasMany
    {
        return $this->hasMany(self::class, 'company_id');
    }

    /**
     * The next client code for the signed-in user (codes run 1, 2, 3… per user).
     */
    public static function nextCode(): int
    {
        return (int) (static::withTrashed()->max('client_code') ?? 0) + 1;
    }

    /**
     * The client's name for display.
     */
    public function displayName(): string
    {
        if ($this->display_name) {
            return $this->display_name;
        }

        if ($this->isCompany() && $this->organisation_name) {
            return $this->organisation_name;
        }

        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Mobile number in the international form WhatsApp links need ("919876543210"), or null.
     * Ten-digit numbers are treated as Indian.
     */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->mobile) ?? '';

        return match (true) {
            strlen($digits) === 10 => '91'.$digits,
            strlen($digits) === 11 && str_starts_with($digits, '0') => '91'.substr($digits, 1),
            strlen($digits) >= 11 && strlen($digits) <= 15 => $digits,
            default => null,
        };
    }

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
