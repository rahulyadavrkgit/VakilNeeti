<?php

namespace App\Http\Controllers;

use App\Models\AdvocateProfile;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdvocateProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->account_type === 'company' && ! $user->is_admin) {
            return redirect()->route('firm-profile.index');
        }

        $profile = $user->advocateProfile()->firstOrNew();

        return view('profile.advocate', [
            'user' => $user,
            'profile' => $profile,
            'specializations' => AdvocateProfile::SPECIALIZATIONS,
            'stateGroups' => State::dropdownGroups(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->account_type === 'company' && ! $user->is_admin) {
            return redirect()->route('firm-profile.index');
        }

        $profile = $user->advocateProfile()->firstOrNew();
        $isStudent = $user->account_role === 'student_intern';
        $statesWithCodes = State::indianStatesAndUnionTerritories();
        $languages = $request->input('languages_spoken', '');

        $request->merge(AdvocateProfile::normaliseBarInput($request->all()));
        $request->merge([
            'languages_spoken' => is_string($languages)
                ? array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $languages) ?: [])))
                : $languages,
        ]);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'gender' => ['required', 'in:Male,Female,Other'],
            'photo' => [
                $profile->photo_path || $isStudent ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'years_experience' => ['required', 'integer', 'min:0', 'max:80'],
            'graduation_year' => ['required', 'integer', 'between:1950,2030'],
            'languages_spoken' => ['required', 'array', 'min:1', 'max:20'],
            'languages_spoken.*' => ['required', 'string', 'max:60'],
            'specializations' => ['required', 'array', 'min:1'],
            'specializations.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(AdvocateProfile::SPECIALIZATIONS),
            ],
            'practice_areas' => ['nullable', 'string', 'max:2000'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', Rule::in(array_keys($statesWithCodes))],
            'pincode' => ['required', 'regex:/^[0-9]{6}$/'],
            'court' => ['required', 'string', 'max:200'],
            ...AdvocateProfile::barRegistrationRules(! $isStudent),
        ], AdvocateProfile::barRegistrationMessages() + [
            'region.in' => 'Select a State or Union Territory from the list.',
        ]);

        $oldPhotoPath = $profile->photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')->store('advocate-photos', 'public');

            if (! $newPhotoPath) {
                throw new \RuntimeException('The advocate photo could not be saved.');
            }
        }

        $profile->fill([
            'gender' => $validated['gender'],
            'years_experience' => $validated['years_experience'],
            'graduation_year' => $validated['graduation_year'],
            'languages_spoken' => $validated['languages_spoken'],
            'specializations' => array_values(array_unique($validated['specializations'])),
            'practice_areas' => $validated['practice_areas'] ?? null,
            'city' => $validated['city'],
            'district' => $validated['district'],
            'region' => $validated['region'],
            'state_code' => $statesWithCodes[$validated['region']],
            'pincode' => $validated['pincode'],
            'court' => $validated['court'],
            'bar_state' => $validated['bar_state'] ?? null,
            'bar_state_code' => $validated['bar_state_code'] ?? null,
            'bar_number' => $validated['bar_number'] ?? null,
            'bar_registration_year' => $validated['bar_registration_year'] ?? null,
            'bar_council_registration_number' => AdvocateProfile::composeBarRegistration(
                $validated['bar_state_code'] ?? null,
                $validated['bar_number'] ?? null,
                isset($validated['bar_registration_year']) ? (int) $validated['bar_registration_year'] : null,
            ) ?? $profile->bar_council_registration_number,
        ]);

        if ($newPhotoPath) {
            $profile->photo_path = $newPhotoPath;
        }

        try {
            DB::transaction(function () use ($user, $profile, $validated): void {
                $user->name = $validated['full_name'];
                $user->email = Str::lower($validated['email']);
                $user->save();

                $profile->user_id = $user->id;
                $profile->save();
            });
        } catch (\Throwable $exception) {
            if ($newPhotoPath && ! Storage::disk('public')->delete($newPhotoPath)) {
                Log::warning('Failed to remove an unused advocate photo after profile update failed.', [
                    'user_id' => $user->id,
                    'path' => $newPhotoPath,
                ]);
            }

            throw $exception;
        }

        if (
            $newPhotoPath &&
            $oldPhotoPath &&
            $oldPhotoPath !== $newPhotoPath &&
            Storage::disk('public')->exists($oldPhotoPath) &&
            ! Storage::disk('public')->delete($oldPhotoPath)
        ) {
            Log::warning('Failed to remove the previous advocate photo.', [
                'user_id' => $user->id,
                'path' => $oldPhotoPath,
            ]);
        }

        return redirect()
            ->route('profile.advocate.index')
            ->with('success', 'Advocate profile saved.');
    }
}
