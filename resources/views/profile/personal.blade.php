@extends('layouts.app')

@section('title', 'Personal profile')
@section('page-heading', 'Personal profile')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Personal Profile</h1>
            <p class="mt-1 text-sm text-slate-500">
                Maintain your individual personal and correspondence information.
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

    <form method="POST" action="{{ route('profile.personal.update') }}"
          class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Personal Information</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Full Name *
                    </label>
                    <input type="text" name="full_name"
                           value="{{ old('full_name', $profile->full_name ?? '') }}"
                           required maxlength="150"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Professional Title
                    </label>
                    <input type="text" name="professional_title"
                           value="{{ old('professional_title', $profile->professional_title ?? '') }}"
                           placeholder="e.g. Advocate"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Personal Email
                    </label>
                    <input type="email" name="email"
                           value="{{ old('email', $profile->email ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Mobile Number
                    </label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $profile->phone ?? '') }}"
                           maxlength="20"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Alternate Mobile Number
                    </label>
                    <input type="text" name="alternate_phone"
                           value="{{ old('alternate_phone', $profile->alternate_phone ?? '') }}"
                           maxlength="20"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Residential / Correspondence Address</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Address
                    </label>
                    <textarea name="address" rows="3"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('address', $profile->address ?? '') }}</textarea>
                </div>

                <x-location-fields class="md:grid-cols-2" :with-country="true"
                                   :country="old('country', $profile->country ?? 'India')" :state="old('state', $profile->state ?? null)"
                                   :district="old('district', $profile->district ?? null)" :city="old('city', $profile->city ?? null)"
                                   field-class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">PIN Code</label>
                        <input type="text" name="pincode"
                               value="{{ old('pincode', $profile->pincode ?? '') }}"
                               maxlength="10" inputmode="numeric"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </x-location-fields>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Personal Profile
            </button>
        </div>
    </form>
</div>
@endsection