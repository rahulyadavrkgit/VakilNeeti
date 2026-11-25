@extends('layouts.app')

@section('title', 'Professional profile')
@section('page-heading', 'Professional profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Professional Profile</h1>
            <p class="mt-1 text-sm text-slate-500">
                Maintain your legal qualifications, enrolment and practice information.
            </p>
        </div>

    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('profile.professional.update') }}"
          class="space-y-6">
        @csrf

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Bar Council Enrolment</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Bar Council</label>
                    <input type="text" name="bar_council_name"
                           value="{{ old('bar_council_name', $profile->bar_council_name ?? '') }}"
                           placeholder="Name of State Bar Council"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Enrolment Number</label>
                    <input type="text" name="enrolment_number"
                           value="{{ old('enrolment_number', $profile->enrolment_number ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Enrolment Date</label>
                    <input type="date" name="enrolment_date"
                           value="{{ old('enrolment_date', isset($profile->enrolment_date) ? substr($profile->enrolment_date, 0, 10) : '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Enrolment Status</label>
                    <input type="text" name="bar_council_status"
                           value="{{ old('bar_council_status', $profile->bar_council_status ?? '') }}"
                           placeholder="e.g. Active"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Supreme Court AOR Details</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Advocate-on-Record Number
                    </label>
                    <input type="text" name="supreme_court_aor_number"
                           value="{{ old('supreme_court_aor_number', $profile->supreme_court_aor_number ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        AOR Enrolment Date
                    </label>
                    <input type="date" name="aor_enrolment_date"
                           value="{{ old('aor_enrolment_date', isset($profile->aor_enrolment_date) ? substr($profile->aor_enrolment_date, 0, 10) : '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
            <p class="mt-3 text-xs text-slate-500">
                Enter AOR details only if applicable. These fields do not verify AOR status.
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Qualifications & Memberships</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Educational / Professional Qualifications
                    </label>
                    <textarea name="qualifications" rows="3"
                              placeholder="Enter degrees, diplomas and other qualifications."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('qualifications', $profile->qualifications ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Professional Memberships
                    </label>
                    <textarea name="professional_memberships" rows="3"
                              placeholder="Enter bar associations or other professional memberships."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('professional_memberships', $profile->professional_memberships ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h2 class="mb-4 text-lg font-semibold text-slate-900">Practice Information</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Areas of Practice
                    </label>
                    <textarea name="practice_areas" rows="3"
                              placeholder="e.g. Civil, Criminal, Constitutional, Commercial, Consumer."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('practice_areas', $profile->practice_areas ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Courts / Forums of Practice
                    </label>
                    <textarea name="courts_of_practice" rows="3"
                              placeholder="Enter courts and tribunals where you practise."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('courts_of_practice', $profile->courts_of_practice ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Date of Commencement of Practice
                    </label>
                    <input type="date" name="practice_start_date"
                           value="{{ old('practice_start_date', isset($profile->practice_start_date) ? substr($profile->practice_start_date, 0, 10) : '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Professional Profile / Biography
                    </label>
                    <textarea name="professional_bio" rows="5"
                              placeholder="Write a concise professional biography for internal CV and profile use."
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('professional_bio', $profile->professional_bio ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Professional Profile
            </button>
        </div>
    </form>
</div>
@endsection