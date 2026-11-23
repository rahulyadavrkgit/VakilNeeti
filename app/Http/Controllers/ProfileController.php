<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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

    /**
     * Save System Preferences: calendar view and week start, Home display boards, and which reminders to get.
     */
    public function updateSystemPreferences(Request $request)
    {
        $rules = [];

        foreach (User::PREFERENCES as $key => $definition) {
            $rules[$key] = is_bool($definition['default'])
                ? ['nullable', 'boolean']
                : ['sometimes', 'required', Rule::in(array_keys($definition['options']))];
        }

        $validated = $request->validate($rules);
        $user = $request->user();
        $preferences = [];

        // A choice the form does not send keeps its current value; an unticked switch is off.
        foreach (User::PREFERENCES as $key => $definition) {
            $preferences[$key] = is_bool($definition['default'])
                ? $request->boolean($key)
                : ($validated[$key] ?? $user->preference($key));
        }

        $user->preferences = $preferences;
        $user->save();

        return redirect()
            ->to(route('profile.index').'#system-preferences')
            ->with('system_preferences_success', 'System preferences saved.');
    }

    /**
     * Appearance from the account menu (same as device, light or dark), saved without leaving the page.
     */
    public function updateTheme(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', Rule::in(array_keys(User::PREFERENCES['theme']['options']))],
        ]);

        $user = $request->user();
        $user->preferences = ['theme' => $validated['theme']] + ($user->preferences ?? []);
        $user->save();

        return response()->json(['theme' => $validated['theme']]);
    }

    public function updatePreferences(Request $request)
    {
        // Settings now sends only the currency; the sign-up answers are kept unless they are sent.
        $rules = [
            'onboarding_features' => ['sometimes', 'required', 'array', 'min:1', 'max:'.count(self::ONBOARDING_FEATURES)],
            'onboarding_features.*' => ['required', 'distinct', 'in:'.implode(',', array_keys(self::ONBOARDING_FEATURES))],
            'onboarding_other' => ['nullable', 'string', 'max:500'],
            'currency' => ['sometimes', Rule::enum(Currency::class)],
        ];

        if (in_array('other', (array) $request->input('onboarding_features', []), true)) {
            $rules['onboarding_other'] = ['required', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);
        $user = $request->user();
        if (array_key_exists('onboarding_features', $validated)) {
            $user->onboarding_features = array_values(array_unique($validated['onboarding_features']));
            $user->onboarding_other = $validated['onboarding_other'] ?? null;
        }

        $user->currency = $validated['currency'] ?? $user->currency;
        $user->save();

        return redirect()
            ->route('profile.index')
            ->with('preferences_success', 'Workspace preferences saved.');
    }
}
