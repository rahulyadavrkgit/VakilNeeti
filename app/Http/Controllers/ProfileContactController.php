<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileContactController extends Controller
{
    private function activeFirm()
    {
        return DB::table('firm_profiles')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();
    }

    public function index()
    {
        $firm = $this->activeFirm();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $contacts = DB::table('profile_contacts')
            ->where('user_id', auth()->id())
            ->where('firm_profile_id', $firm->id)
            ->where('is_active', true)
            ->orderBy('contact_type')
            ->orderBy('label')
            ->get();

        $personal = DB::table('personal_profiles')
            ->where('user_id', auth()->id())
            ->where('firm_profile_id', $firm->id)
            ->first();

        return view('profile.contacts', compact('firm', 'contacts', 'personal'));
    }

    public function store(Request $request)
    {
        $firm = $this->activeFirm();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $validated = $request->validate([
            'contact_type' => [
                'required',
                Rule::in(['email', 'phone', 'whatsapp', 'website', 'other']),
            ],
            'label' => ['nullable', 'string', 'max:100'],
            'contact_value' => ['required', 'string', 'max:255'],
        ]);

        DB::table('profile_contacts')
            ->where('user_id', auth()->id())->insert([
                'user_id' => auth()->id(),
                'firm_profile_id' => $firm->id,
                'contact_type' => $validated['contact_type'],
                'label' => $validated['label'] ?? null,
                'contact_value' => $validated['contact_value'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('profile.contacts.index')
            ->with('success', 'Contact information added successfully.');
    }

    public function destroy($id)
    {
        $firm = $this->activeFirm();

        if (! $firm) {
            return redirect()->route('firm-profile.index');
        }

        DB::table('profile_contacts')
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->where('firm_profile_id', $firm->id)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('profile.contacts.index')
            ->with('success', 'Contact information removed from the active list.');
    }
}
