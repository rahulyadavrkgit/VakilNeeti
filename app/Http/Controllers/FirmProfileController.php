<?php

namespace App\Http\Controllers;

use App\Models\FirmProfile;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FirmProfileController extends Controller
{
    /**
     * Office and billing details printed on invoices. Company accounts call it their firm profile;
     * individual advocates use the same page for their chamber or office address.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdvocate = $user->account_type !== 'company' && ! $user->is_admin;
        $firm = FirmProfile::first();

        return view('firm-profile.index', [
            'firm' => $firm,
            'isAdvocate' => $isAdvocate,
            'defaults' => $firm ? [] : [
                'firm_name' => $isAdvocate ? 'Adv. '.$user->name : null,
                'proprietor_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->mobile,
            ],
            'next' => $request->query('next') === 'invoice-settings' ? 'invoice-settings' : null,
            'stateGroups' => State::dropdownGroups(),
        ]);
    }

    public function update(Request $request)
    {
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

        // Guided profile setup: carry on to the next unfinished step.
        if ($request->boolean('setup')) {
            return redirect()->route('profile.setup.next', ['after' => 'office'])->with('success', 'Office details saved.');
        }

        // Coming from "Add bank or UPI details": carry on to that step.
        if ($request->input('next') === 'invoice-settings') {
            return redirect()
                ->route('profile.invoice-settings.index')
                ->with('success', 'Office details saved. Now add your bank or UPI details.');
        }

        return redirect()
            ->route('firm-profile.index')
            ->with('success', $request->user()->account_type === 'company' ? 'Firm profile saved successfully.' : 'Office details saved.');
    }
}
