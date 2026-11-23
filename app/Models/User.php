<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'mobile',
    'mobile_verified_at',
    'password',
    'account_type',
    'account_role',
    'onboarding_features',
    'onboarding_other',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarding_features' => 'array',
            'preferences' => 'array',
        ];
    }

    /**
     * System preferences and their defaults. Values a person has not chosen fall back to these.
     *
     * @var array<string, array{default: string|bool, options?: array<string, string>}>
     */
    public const PREFERENCES = [
        'calendar_view' => ['default' => 'month', 'options' => ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'list' => 'List']],
        'week_starts_on' => ['default' => 'sunday', 'options' => ['sunday' => 'Sunday', 'monday' => 'Monday']],
        'home_display_boards' => ['default' => true],
        'remind_hearings' => ['default' => 'today_and_tomorrow', 'options' => ['today_and_tomorrow' => 'Today and tomorrow', 'today' => 'Only on the day', 'off' => 'Off']],
        'remind_appointments' => ['default' => true],
        'remind_tasks' => ['default' => true],
        'remind_invoices' => ['default' => true],
    ];

    /**
     * One system preference, or its default.
     */
    public function preference(string $key): string|bool|null
    {
        $definition = self::PREFERENCES[$key] ?? null;

        if (! $definition) {
            return null;
        }

        $value = ($this->preferences ?? [])[$key] ?? null;

        if (is_bool($definition['default'])) {
            return $value === null ? $definition['default'] : (bool) $value;
        }

        return is_string($value) && array_key_exists($value, $definition['options'] ?? []) ? $value : $definition['default'];
    }

    public function advocateProfile(): HasOne
    {
        return $this->hasOne(AdvocateProfile::class);
    }

    public function firmProfile(): HasOne
    {
        return $this->hasOne(FirmProfile::class);
    }
}
