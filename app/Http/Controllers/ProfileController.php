<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const ONBOARDING_FEATURES = [
        'personalized_hearings' => 'Personalized court hearing lists',
        'daily_orders' => 'Daily orders and case details',
        'invoices_expenses_documents' => 'Invoices, expenses and document management',
        'judgment_research' => 'Judgment research',
        'mis_reports' => 'MIS reports',
        'other' => 'Other',
    ];

    public function index()
    {
        $user = Auth::user();
        $isCompany = $user->account_type === 'company';
        $showAllProfileTypes = $user->is_admin;
        // Every account can keep office details for invoices (advocates call it office & billing).
        $firm = DB::table('firm_profiles')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return view('profile.index', [
            'firm' => $firm,
            'isCompany' => $isCompany,
            'showAllProfileTypes' => $showAllProfileTypes,
            'onboardingFeatures' => self::ONBOARDING_FEATURES,
            'user' => $user,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $rules = [
            'onboarding_features' => ['required', 'array', 'min:1', 'max:'.count(self::ONBOARDING_FEATURES)],
            'onboarding_features.*' => ['required', 'distinct', 'in:'.implode(',', array_keys(self::ONBOARDING_FEATURES))],
            'onboarding_other' => ['nullable', 'string', 'max:500'],
            'currency' => ['sometimes', Rule::enum(Currency::class)],
        ];

        if (in_array('other', (array) $request->input('onboarding_features', []), true)) {
            $rules['onboarding_other'] = ['required', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);
        $user = $request->user();
        $user->onboarding_features = array_values(array_unique($validated['onboarding_features']));
        $user->onboarding_other = $validated['onboarding_other'] ?? null;
        $user->currency = $validated['currency'] ?? $user->currency;
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('preferences_success', 'Workspace preferences saved.');
    }
}
