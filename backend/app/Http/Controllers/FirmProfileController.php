<?php

namespace App\Http\Controllers;

use App\Models\FirmProfile;
use Illuminate\Http\Request;

class FirmProfileController extends Controller
{
    public function index()
    {
        $firm = FirmProfile::first();

        return view('firm-profile.index', compact('firm'));
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
            'state' => ['nullable', 'string', 'max:255'],
            'state_code' => ['nullable', 'string', 'size:2'],
            'pincode' => ['nullable', 'string', 'max:10'],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/',
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

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
