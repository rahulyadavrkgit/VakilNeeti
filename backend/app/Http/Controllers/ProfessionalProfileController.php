<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfessionalProfileController extends Controller
{
    public function index()
    {
        $firm = DB::table('firm_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->first();

        if (!$firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $profile = DB::table('professional_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('firm_profile_id', $firm->id)
            ->first();

        return view('profile.professional', compact('firm', 'profile'));
    }

    public function update(Request $request)
    {
        $firm = DB::table('firm_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('is_active', true)
            ->first();

        if (!$firm) {
            return redirect()
                ->route('firm-profile.index')
                ->with('error', 'Please create an active Firm Profile first.');
        }

        $validated = $request->validate([
            'bar_council_name' => ['nullable', 'string', 'max:200'],
            'enrolment_number' => ['nullable', 'string', 'max:100'],
            'enrolment_date' => ['nullable', 'date'],
            'bar_council_status' => ['nullable', 'string', 'max:100'],
            'supreme_court_aor_number' => ['nullable', 'string', 'max:100'],
            'aor_enrolment_date' => ['nullable', 'date'],
            'qualifications' => ['nullable', 'string', 'max:10000'],
            'professional_memberships' => ['nullable', 'string', 'max:10000'],
            'practice_areas' => ['nullable', 'string', 'max:10000'],
            'courts_of_practice' => ['nullable', 'string', 'max:10000'],
            'practice_start_date' => ['nullable', 'date'],
            'professional_bio' => ['nullable', 'string', 'max:10000'],
        ]);

        $existing = DB::table('professional_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
            ->where('firm_profile_id', $firm->id)
            ->first();

        $data = array_merge($validated, [
            'updated_at' => now(),
        ]);

        if ($existing) {
            DB::table('professional_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))
                ->where('id', $existing->id)
                ->update($data);
        } else {
            $data['firm_profile_id'] = $firm->id;
            $data['user_id'] = auth()->id();
            $data['created_at'] = now();

            DB::table('professional_profiles')
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()))->insert($data);
        }

        return redirect()
            ->route('profile.professional.index')
            ->with('success', 'Professional Profile saved successfully.');
    }
}