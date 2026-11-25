@extends('layouts.app')

@section('title', $isAdvocate ? 'Office & billing details' : 'Firm Profile')
@section('page-heading', $isAdvocate ? 'Office & billing details' : 'Firm Profile')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    <x-page-header :title="$isAdvocate ? 'Office & billing details' : 'Firm Profile'"
                   :subtitle="$isAdvocate ? 'Your chamber or office address and GST details, printed on your invoices.' : 'The business details printed on your invoices.'">
        <a href="{{ route('profile.invoice-settings.index') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Bank &amp; UPI details &rarr;</a>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-lg bg-green-100 border border-green-200 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div role="alert" class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-amber-900">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-100 border border-red-200 px-4 py-3 text-red-800">
            <p class="font-semibold">Please correct the following:</p>
            <ul class="list-disc pl-5 mt-2 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('profile.partials.setup-progress', ['step' => 'office'])

    <form method="POST" action="{{ route('firm-profile.update') }}"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-8">

        @csrf
        @if($next)
            <input type="hidden" name="next" value="{{ $next }}">
        @endif
        @if(request()->boolean('setup'))
            <input type="hidden" name="setup" value="1">
        @endif

        <section>
            <h3 class="mb-5 border-b border-slate-100 pb-3 text-lg font-semibold text-slate-900">
                {{ $isAdvocate ? 'Name on invoices' : 'Firm Identity' }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium mb-2">{{ $isAdvocate ? 'Office or chamber name *' : 'Firm Name *' }}</label>
                    <input type="text" name="firm_name"
                           value="{{ old('firm_name', $firm->firm_name ?? $defaults['firm_name'] ?? '') }}"
                           required
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">{{ $isAdvocate ? 'Advocate name' : 'Proprietor / Owner Name' }}</label>
                    <input type="text" name="proprietor_name"
                           value="{{ old('proprietor_name', $firm->proprietor_name ?? $defaults['proprietor_name'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

            </div>
        </section>

        <section>
            <h3 class="mb-5 border-b border-slate-100 pb-3 text-lg font-semibold text-slate-900">
                Business Address
            </h3>

            <div class="space-y-5">

                <div>
                    <label class="block text-sm font-medium mb-2">Address</label>
                    <textarea name="address" rows="3"
                              class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('address', $firm->address ?? '') }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5" data-location-picker data-districts-url="{{ url('/api/court-directory/states') }}" data-cities-url="{{ url('/api/locations/districts') }}">

                    @php
                        $firmState = old('state', $firm->state ?? '');
                        $firmStateIsUnlisted = filled($firm->state ?? null) && ! array_key_exists($firm->state, \App\Models\State::GST_STATE_CODES);
                    @endphp

                    <div>
                        <label for="firm-state" class="block text-sm font-medium mb-2">State / Union Territory</label>
                        <select id="firm-state" name="state" data-location-state
                                class="w-full rounded-lg border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500">
                            <x-state-options :groups="$stateGroups" :selected="$firmState" />
                        </select>
                        @if($firmStateIsUnlisted)
                            <p class="mt-1 text-xs text-amber-700">Saved value "{{ $firm->state }}" is not in the official list. Select it again.</p>
                        @endif
                        @error('state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">GST State Code</label>
                        <input type="text" value="{{ \App\Models\State::GST_STATE_CODES[$firmState] ?? '' }}" readonly data-location-gst-code
                               class="w-full rounded-lg border-slate-200 bg-slate-50 text-slate-600">
                        <p class="mt-1 text-xs text-slate-500">Filled in automatically from the State / UT.</p>
                    </div>

                    <div>
                        <label for="firm-district" class="block text-sm font-medium mb-2">District</label>
                        <select data-location-district-select aria-label="District" hidden
                                class="w-full rounded-lg border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500"></select>
                        <input type="text" id="firm-district" name="district" data-location-district-input placeholder="Type your district"
                               value="{{ old('district', $firm->district ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                        <p data-location-district-hint class="mt-1 text-xs text-slate-500" hidden></p>
                    </div>

                    <div>
                        <label for="firm-city" class="block text-sm font-medium mb-2">City / Town</label>
                        <select data-location-city-select aria-label="City or town" hidden
                                class="w-full rounded-lg border-slate-300 bg-white focus:border-blue-500 focus:ring-blue-500"></select>
                        <input type="text" id="firm-city" name="city" autocomplete="address-level2" data-location-city-input placeholder="Type the city or town"
                               value="{{ old('city', $firm->city ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">PIN Code</label>
                        <input type="text" name="pincode" maxlength="10"
                               value="{{ old('pincode', $firm->pincode ?? '') }}"
                               class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                </div>
            </div>
        </section>

        <section>
            <h3 class="mb-5 border-b border-slate-100 pb-3 text-lg font-semibold text-slate-900">
                GST and Contact Details
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <div>
                    <label class="block text-sm font-medium mb-2">GSTIN</label>
                    <input type="text" name="gstin" maxlength="15"
                           value="{{ old('gstin', $firm->gstin ?? '') }}"
                           placeholder="15-character GSTIN"
                           class="w-full rounded-lg border-slate-300 uppercase focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email"
                           value="{{ old('email', $firm->email ?? $defaults['email'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Phone</label>
                    <input type="text" name="phone"
                           value="{{ old('phone', $firm->phone ?? $defaults['phone'] ?? '') }}"
                           class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

            </div>

            <p class="mt-4 text-xs text-slate-500">Enter details exactly as on your GST certificate.</p>
        </section>

        <div class="flex justify-end border-t pt-5">
            <button type="submit"
                    class="rounded-lg bg-[#16776e] px-6 py-3 text-white font-medium hover:bg-[#125f58]">
                {{ $next ? 'Save and add bank / UPI details' : ($isAdvocate ? 'Save office details' : 'Save Firm Profile') }}
            </button>
        </div>

    </form>

</div>

@endsection