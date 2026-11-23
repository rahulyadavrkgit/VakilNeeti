<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Works out how complete a user's profile is, for the home page banner.
 */
class ProfileCompletion
{
    /**
     * Profile setup steps in order: one page each, with the route that opens it.
     *
     * @var array<string, array{label: string, route: string}>
     */
    public const STEPS = [
        'advocate' => ['label' => 'Advocate profile', 'route' => 'profile.advocate.index'],
        'practice-states' => ['label' => 'Practice States', 'route' => 'profile.practice-states.index'],
        'office' => ['label' => 'Office address', 'route' => 'firm-profile.index'],
        'billing' => ['label' => 'Bank & UPI', 'route' => 'profile.invoice-settings.index'],
    ];

    /**
     * The setup steps that apply to this user, each with whether everything on it is done.
     *
     * @return list<array{key: string, label: string, url: string, done: bool}>
     */
    public function steps(User $user): array
    {
        $checks = collect($this->checks($user));

        return collect(self::STEPS)
            ->filter(fn (array $step, string $key): bool => $checks->contains(fn (array $check): bool => $check[3] === $key))
            ->map(fn (array $step, string $key): array => [
                'key' => $key,
                'label' => $step['label'],
                'url' => route($step['route'], ['setup' => 1]),
                'done' => $checks->where(3, $key)->every(fn (array $check): bool => $check[1]),
            ])
            ->values()
            ->all();
    }

    /**
     * The first unfinished step, leaving out the given ones (the step just saved, steps skipped).
     *
     * @param  list<string>  $except
     * @return array{key: string, label: string, url: string, done: bool}|null
     */
    public function nextStep(User $user, array $except = []): ?array
    {
        $steps = collect($this->steps($user));
        // Bank & UPI details hang off the office record: with the office step skipped and unfinished, skip them too.
        $officeMissing = $steps->contains(fn (array $step): bool => $step['key'] === 'office' && ! $step['done']);

        if ($officeMissing && in_array('office', $except, true)) {
            $except[] = 'billing';
        }

        return $steps->first(fn (array $step): bool => ! $step['done'] && ! in_array($step['key'], $except, true));
    }

    /**
     * @return array{percent: int, missing: list<array{label: string, url: string}>}
     */
    public function forUser(User $user): array
    {
        $checks = $this->checks($user);
        $done = collect($checks)->filter(fn (array $check): bool => $check[1])->count();

        return [
            'percent' => (int) round($done / count($checks) * 100),
            'missing' => collect($checks)
                ->reject(fn (array $check): bool => $check[1])
                ->map(fn (array $check): array => [
                    'label' => $check[0],
                    'url' => $check[2],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Each check: label, done, the page that fixes it, and its setup step.
     *
     * @return list<array{0: string, 1: bool, 2: string, 3: string}>
     */
    private function checks(User $user): array
    {
        $advocate = DB::table('advocate_profiles')->where('user_id', $user->id)->first();
        $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
        $invoiceSettings = DB::table('invoice_settings')->where('user_id', $user->id)->first();

        $checks = [];

        if ($user->account_type !== 'company') {
            $checks[] = [
                'Add your photo',
                filled(data_get($advocate, 'photo_path')),
                route('profile.advocate.index'),
                'advocate',
            ];

            $checks[] = [
                'Add your Bar Council registration',
                filled(data_get($advocate, 'bar_number'))
                    || filled(data_get($advocate, 'bar_council_registration_number')),
                route('profile.advocate.index').'#bar-registration',
                'advocate',
            ];

            $checks[] = [
                'Choose your practice areas',
                filled(data_get($advocate, 'practice_areas'))
                    || filled(data_get($advocate, 'specializations')),
                route('profile.advocate.index'),
                'advocate',
            ];

            $checks[] = [
                'Add the court you practise in',
                filled(data_get($advocate, 'court')),
                route('profile.advocate.index'),
                'advocate',
            ];
        }

        $checks[] = [
            'Choose your practice States',
            DB::table('firm_practice_states')->where('user_id', $user->id)->exists(),
            route('profile.practice-states.index'),
            'practice-states',
        ];

        $checks[] = [
            'Add your office address for invoices',
            filled(data_get($firm, 'firm_name')) && filled(data_get($firm, 'address')),
            route('firm-profile.index'),
            'office',
        ];

        $checks[] = [
            'Add bank or UPI details for payments',
            filled(data_get($invoiceSettings, 'upi_id'))
                || filled(data_get($invoiceSettings, 'account_number')),
            route('profile.invoice-settings.index'),
            'billing',
        ];

        return $checks;
    }
}
