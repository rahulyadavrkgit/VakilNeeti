@extends('layouts.app')

@section('title', 'Advocate Profile')
@section('page-heading', 'Advocate Profile')

@section('content')
@php
    $languageInput = old('languages_spoken', $profile->languages_spoken ?? []);
    $languageValue = is_array($languageInput) ? implode(', ', $languageInput) : $languageInput;
    $selectedSpecializations = (array) old('specializations', $profile->specializations ?? []);
    $allStates = \App\Models\State::indianStatesAndUnionTerritories();
    $selectedRegion = old('region', $profile->region);
    $selectedStateCode = $allStates[$selectedRegion] ?? '';
    $selectedGstCode = \App\Models\State::GST_STATE_CODES[$selectedRegion] ?? '';
    $hasUnlistedRegion = filled($profile->region) && ! array_key_exists($profile->region, $allStates);
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-[#142235] p-6 text-white shadow-lg sm:p-8">
        <div class="pointer-events-none absolute -right-12 -top-24 h-64 w-64 rounded-full border border-white/10"></div>
        <p class="relative text-xs font-bold uppercase tracking-[.18em] text-[#91c9c1]">Profile Settings</p>
        <h1 class="relative mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Advocate Profile</h1>
    </header>

    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @include('profile.partials.setup-progress', ['step' => 'advocate'])

    <form method="POST" action="{{ route('profile.advocate.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if(request()->boolean('setup'))
            <input type="hidden" name="setup" value="1">
        @endif

        <x-panel class="p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Basic information</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">
                    Full name <span class="text-red-600">*</span>
                    <input name="full_name" value="{{ old('full_name', $user->name) }}" required maxlength="150" autocomplete="name"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('full_name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Phone number
                    <input value="{{ $user->mobile }}" readonly class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Email ID <span class="text-red-600">*</span>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('email') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Gender <span class="text-red-600">*</span>
                    <select name="gender" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                        <option value="">Select gender</option>
                        @foreach(['Male', 'Female', 'Other'] as $gender)
                            <option value="{{ $gender }}" @selected(old('gender', $profile->gender) === $gender)>{{ $gender }}</option>
                        @endforeach
                    </select>
                    @error('gender') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <div class="sm:col-span-2">
                    @if($profile->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($profile->photo_path))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($profile->photo_path) }}"
                             alt="Current advocate profile photo" class="mb-3 h-24 w-24 rounded-full object-cover">
                    @endif
                    <label class="text-sm font-medium text-slate-700">
                        {{ $profile->photo_path ? 'Change photo (optional)' : 'Upload your photo' }}
                        @if(!$profile->photo_path)<span class="text-red-600">*</span>@endif
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-resize-image="1200" data-image-preview="advocate-photo-preview"
                               @required(!$profile->photo_path && auth()->user()->account_role !== 'student_intern')
                               class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-[#16776e] file:px-4 file:py-2 file:font-semibold file:text-white">
                    </label>
                    <img id="advocate-photo-preview" alt="New photo preview" hidden class="mt-3 h-24 w-24 rounded-full object-cover">
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. Large phone photos are made smaller automatically before upload.</p>
                    @error('photo') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-panel>

        <x-panel class="p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Professional information</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">
                    Years of experience <span class="text-red-600">*</span>
                    <input type="number" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" required min="0" max="80"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('years_experience') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Graduation year <span class="text-red-600">*</span>
                    <select name="graduation_year" required class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                        <option value="">Select year</option>
                        @foreach(range(2030, 1950) as $year)
                            <option value="{{ $year }}" @selected((int) old('graduation_year', $profile->graduation_year) === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    @error('graduation_year') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Languages spoken <span class="text-red-600">*</span>
                    <input name="languages_spoken" value="{{ $languageValue }}" required
                           placeholder="For example: Hindi, English" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    <span class="mt-1 block text-xs font-normal text-slate-500">Separate languages with commas.</span>
                    @error('languages_spoken') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <fieldset class="sm:col-span-2">
                    <legend class="mb-2 text-sm font-medium text-slate-700">Specialization <span class="text-red-600">*</span></legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($specializations as $specialization)
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                <input type="checkbox" name="specializations[]" value="{{ $specialization }}"
                                       @checked(in_array($specialization, $selectedSpecializations, true))
                                       class="rounded border-slate-300 text-teal-800 focus:ring-[#16776e]">
                                {{ $specialization }}
                            </label>
                        @endforeach
                    </div>
                    @error('specializations') <span class="mt-2 block text-xs text-red-600">{{ $message }}</span> @enderror
                    @error('specializations.*') <span class="mt-2 block text-xs text-red-600">{{ $message }}</span> @enderror
                </fieldset>

                <label class="text-sm font-medium text-slate-700 sm:col-span-2">
                    Practice areas <span class="font-normal text-slate-400">(optional)</span>
                    <textarea name="practice_areas" rows="3" maxlength="2000"
                              placeholder="Add a short description of your practice areas."
                              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('practice_areas', $profile->practice_areas) }}</textarea>
                    @error('practice_areas') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

            </div>
        </x-panel>

        <x-panel id="bar-registration" class="scroll-mt-6 p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Bar Council enrolment</h2>
            @if($profile->bar_council_registration_number && ! $profile->bar_number)
                <p class="mt-1 text-sm text-slate-500">Saved as: <span class="font-semibold text-slate-700">{{ $profile->bar_council_registration_number }}</span>. Enter it in the three parts below.</p>
            @endif
            <div class="mt-4">
                <x-bar-registration-fields :profile="$profile" :state-groups="$stateGroups" :required="$user->account_role !== 'student_intern'" />
            </div>
        </x-panel>

        <x-panel class="p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Practice location</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2" data-location-picker data-districts-url="{{ url('/api/court-directory/states') }}" data-cities-url="{{ url('/api/locations/districts') }}">
                <div class="text-sm font-medium text-slate-700">
                    <label for="advocate-region">State / Union Territory <span class="text-red-600">*</span></label>
                    <select id="advocate-region" name="region" required data-location-state
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                        <x-state-options :groups="$stateGroups" :selected="$selectedRegion" />
                    </select>
                    @if($hasUnlistedRegion)
                        <span class="mt-1 block text-xs font-normal text-amber-700">
                            Your saved value "{{ $profile->region }}" is not in the official list. Select your State / Union Territory again.
                        </span>
                    @endif
                    @error('region') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="text-sm font-medium text-slate-700">
                    <label for="advocate-district">District <span class="text-red-600">*</span></label>
                    <select data-location-district-select aria-label="District" hidden
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select>
                    <input id="advocate-district" name="district" value="{{ old('district', $profile->district) }}" required maxlength="100"
                           data-location-district-input placeholder="Type your district"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    <span data-location-district-hint class="mt-1 block text-xs font-normal text-slate-500" hidden></span>
                    @error('district') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="text-sm font-medium text-slate-700">
                    <label for="advocate-city">City / Town <span class="text-red-600">*</span></label>
                    <select data-location-city-select aria-label="City or town" hidden
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select>
                    <input id="advocate-city" name="city" value="{{ old('city', $profile->city) }}" required maxlength="100" autocomplete="address-level2"
                           data-location-city-input placeholder="Type the city or town"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('city') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <label class="text-sm font-medium text-slate-700">
                        State code
                        <input value="{{ $selectedStateCode }}" readonly data-location-state-code
                               class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                    </label>
                    <label class="text-sm font-medium text-slate-700">
                        GST state code
                        <input value="{{ $selectedGstCode }}" readonly data-location-gst-code
                               class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                    </label>
                </div>

                <label class="text-sm font-medium text-slate-700">
                    PIN code <span class="text-red-600">*</span>
                    <input name="pincode" value="{{ old('pincode', $profile->pincode) }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('pincode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <div class="text-sm font-medium text-slate-700">
                    <label for="advocate-court">Primary court <span class="text-red-600">*</span></label>
                    <select data-location-court-select aria-label="Primary court" hidden
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></select>
                    <input id="advocate-court" name="court" value="{{ old('court', $profile->court) }}" required maxlength="200"
                           data-location-court-input placeholder="Type the court or tribunal"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('court') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-panel>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl bg-[#238b80] px-7 py-3 font-semibold text-white shadow-sm transition hover:bg-[#16776e]">
                Save Advocate Profile
            </button>
        </div>
    </form>
</div>
@endsection
