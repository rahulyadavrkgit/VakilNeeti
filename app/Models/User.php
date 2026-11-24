<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'mobile',
    'mobile_verified_at',
    'email_verified_at',
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
        'theme' => ['default' => 'system', 'options' => ['system' => 'Same as device', 'light' => 'Light', 'dark' => 'Dark']],
        'calendar_view' => ['default' => 'month', 'options' => ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'list' => 'List']],
        'week_starts_on' => ['default' => 'sunday', 'options' => ['sunday' => 'Sunday', 'monday' => 'Monday']],
        'home_display_boards' => ['default' => true],
        'remind_hearings' => ['default' => 'today_and_tomorrow', 'options' => ['today_and_tomorrow' => 'Today + tomorrow (evening)', 'today' => 'Only on the day', 'off' => 'Off']],
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

    /**
     * Team roles: a junior adds and edits cases, dates, tasks and clients; a clerk sees them and adds
     * next dates and tasks. Neither sees the owner's billing or settings.
     *
     * @var array<string, string>
     */
    public const TEAM_ROLES = ['junior' => 'Junior', 'clerk' => 'Clerk'];

    /**
     * Whose cases, clients and calendar this person works in: the advocate who invited them, or their own.
     */
    public function workspaceId(): int
    {
        return $this->team_owner_id ?: $this->id;
    }

    public function isTeamMember(): bool
    {
        return $this->team_owner_id !== null;
    }

    public function isClerk(): bool
    {
        return $this->isTeamMember() && $this->team_role === 'clerk';
    }

    /**
     * On sign-in: join the workspace of an advocate who invited this email or mobile number, if not in a team yet.
     */
    public function acceptTeamInvite(): void
    {
        if ($this->team_owner_id || (! $this->email && ! $this->mobile)) {
            return;
        }

        $lastTen = fn (?string $number): string => substr(preg_replace('/\D+/', '', (string) $number) ?? '', -10);
        $email = mb_strtolower((string) $this->email);
        $invite = TeamInvite::query()
            ->whereNull('accepted_at')
            ->where('owner_id', '!=', $this->id)
            ->oldest()
            ->get()
            ->first(fn (TeamInvite $invite): bool => ($invite->email && $email !== '' && mb_strtolower($invite->email) === $email)
                || ($invite->mobile && $this->mobile && $lastTen($invite->mobile) === $lastTen($this->mobile)));

        if (! $invite) {
            return;
        }

        $this->forceFill(['team_owner_id' => $invite->owner_id, 'team_role' => $invite->role])->save();
        $invite->update(['accepted_user_id' => $this->id, 'accepted_at' => now()]);
    }

    public function teamOwner(): BelongsTo
    {
        return $this->belongsTo(self::class, 'team_owner_id');
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(self::class, 'team_owner_id');
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
