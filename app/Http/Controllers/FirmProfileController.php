<?php

namespace App\Http\Controllers;

use App\Models\FirmProfile;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FirmProfileController extends Controller
{
    public function index()
    {
        if (auth()->user()->account_type !== 'company' && ! auth()->user()->is_admin) {
            return redirect()->route('profile.advocate.index');
        }

        $firm = FirmProfile::first();

        return view('firm-profile.index', [
            'firm' => $firm,
            'stateGroups' => State::dropdownGroups(),
        ]);
    }

    public function update(Request $request)
    {
        if ($request->user()->account_type !== 'company' && ! $request->user()->is_admin) {
            return redirect()->route('profile.advocate.index');
        }

        $request->merge([
            'gstin' => trim((string) $request->input('gstin')) === '' ? null : strtoupper(trim((string) $request->input('gstin'))),
        ]);

        $validated = $request->validate([
            'firm_name' => ['required', 'string', 'max:255'],
            'proprietor_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', Rule::in(array_keys(State::GST_STATE_CODES))],
            'pincode' => ['nullable', 'string', 'max:10'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'state.in' => 'Select a State or Union Territory from the list.',
        ]);

        $validated['state_code'] = isset($validated['state'])
            ? State::GST_STATE_CODES[$validated['state']]
            : null;

        $firm = FirmProfile::first();

        if ($firm) {
            $firm->update($validated);
        } else {
            FirmProfile::create($validated);
        }

        return redirect()
            ->route('firm-profile.index')
            ->with('success', 'Firm profile saved successfully.');
    }
}
