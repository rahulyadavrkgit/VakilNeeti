<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PersonalProfileController extends Controller
{
    public function index()
    {
        $firm = DB::table('firm_profiles')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->first();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $profile = DB::table('personal_profiles')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('firm_profile_id', $firm->id)
            ->first();

        return view('profile.personal', compact('firm', 'profile'));
    }

    public function update(Request $request)
    {
        $firm = DB::table('firm_profiles')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->first();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'professional_title' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'alternate_phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:120', Rule::when(in_array($request->input('country'), [null, '', 'India'], true), [State::nameRule()])],
            'pincode' => ['nullable', 'string', 'max:10'],
            'country' => ['required', 'string', 'max:100'],
        ]);

        $existing = DB::table('personal_profiles')
            ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('firm_profile_id', $firm->id)
            ->first();

        $data = array_merge($validated, [
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table('personal_profiles')
                ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['firm_profile_id'] = $firm->id;
            $data['user_id'] = auth()->id();
            $data['created_at'] = now();

            DB::table('personal_profiles')
                ->when(! auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->insert($data);
        }

        return redirect()
            ->route('profile.personal.index')
            ->with('success', 'Personal Profile saved successfully.');
    }
}
