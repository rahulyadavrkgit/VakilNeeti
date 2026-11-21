@extends('layouts.app')

@section('title', 'Advocate Profile')
@section('page-heading', 'Advocate Profile')

@section('content')
@php
    $languageInput = old('languages_spoken', $profile->languages_spoken ?? []);
    $languageValue = is_array($languageInput) ? implode(', ', $languageInput) : $languageInput;
    $selectedSpecializations = (array) old('specializations', $profile->specializations ?? []);
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <header class="rounded-2xl bg-slate-900 p-6 text-white shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-widest text-amber-300">Profile Settings</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Advocate Profile</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
            Review and update the professional information you provided during registration.
        </p>
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

    <form method="POST" action="{{ route('profile.advocate.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-slate-900">Basic information</h2>
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
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                               @required(!$profile->photo_path && auth()->user()->account_role !== 'student_intern')
                               class="mt-1 block w-full cursor-pointer rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-950 file:px-4 file:py-2 file:font-semibold file:text-white">
                    </label>
                    <p class="mt-1 text-xs text-slate-500">JPG, PNG or WebP; maximum size 10 MB.</p>
                    @error('photo') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-slate-900">Professional information</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">
                    Years of experience <span class="text-red-600">*</span>
                    <input type="number" name="years_experience" value="{{ old('years_experience', $profile->years_experience) }}" required min="0" max="80"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('years_experience') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Graduation year <span class="text-red-600">*</span>
                    <input type="number" name="graduation_year" value="{{ old('graduation_year', $profile->graduation_year) }}" required min="1950" max="{{ now()->year }}"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
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
                                       class="rounded border-slate-300 text-blue-800 focus:ring-blue-700">
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
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-slate-900">Practice location</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-medium text-slate-700">
                    City <span class="text-red-600">*</span>
                    <input name="city" value="{{ old('city', $profile->city) }}" required maxlength="100"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('city') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    District <span class="text-red-600">*</span>
                    <input name="district" value="{{ old('district', $profile->district) }}" required maxlength="100"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('district') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Region / State <span class="text-red-600">*</span>
                    <input name="region" value="{{ old('region', $profile->region) }}" required maxlength="120"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('region') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    State code
                    <input name="state_code" value="{{ old('state_code', $profile->state_code) }}" maxlength="15"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('state_code') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    PIN code <span class="text-red-600">*</span>
                    <input name="pincode" value="{{ old('pincode', $profile->pincode) }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('pincode') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="text-sm font-medium text-slate-700">
                    Court <span class="text-red-600">*</span>
                    <input name="court" value="{{ old('court', $profile->court) }}" required maxlength="200" placeholder="Primary court or forum"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">
                    @error('court') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-amber-400 px-7 py-3 font-semibold text-slate-950 hover:bg-amber-300">
                Save Advocate Profile
            </button>
        </div>
    </form>
</div>
@endsection