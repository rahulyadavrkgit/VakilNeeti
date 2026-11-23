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
     * @return array{percent: int, missing: list<array{label: string, url: string}>}
     */
    public function forUser(User $user): array
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
            ];

            $checks[] = [
                'Add your Bar Council registration',
                filled(data_get($advocate, 'bar_number'))
                    || filled(data_get($advocate, 'bar_council_registration_number')),
                route('profile.advocate.index').'#bar-registration',
            ];

            $checks[] = [
                'Choose your practice areas',
                filled(data_get($advocate, 'practice_areas'))
                    || filled(data_get($advocate, 'specializations')),
                route('profile.advocate.index'),
            ];

            $checks[] = [
                'Add the court you practise in',
                filled(data_get($advocate, 'court')),
                route('profile.advocate.index'),
            ];
        }

        $checks[] = [
            'Choose your practice States',
            DB::table('firm_practice_states')->where('user_id', $user->id)->exists(),
            route('profile.practice-states.index'),
        ];

        $checks[] = [
            'Add your office address for invoices',
            filled(data_get($firm, 'firm_name')) && filled(data_get($firm, 'address')),
            route('firm-profile.index'),
        ];

        $checks[] = [
            'Add bank or UPI details for payments',
            filled(data_get($invoiceSettings, 'upi_id'))
                || filled(data_get($invoiceSettings, 'account_number')),
            route('profile.invoice-settings.index'),
        ];

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
}