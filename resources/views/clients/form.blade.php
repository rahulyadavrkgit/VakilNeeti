@extends('layouts.app')

@php
    $isEditing = $client->exists;
    $type = old('client_type', $client->client_type ?: 'individual');
    $isCompany = in_array($type, \App\Models\Client::COMPANY_TYPES, true);
    $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5';
@endphp

@section('title', $isEditing ? 'Edit '.$client->displayName() : 'Add client')
@section('page-heading', $isEditing ? 'Edit client' : 'Add client')

@section('content')
<form method="POST" action="{{ $isEditing ? route('clients.update', $client) : route('clients.store') }}" class="mx-auto max-w-4xl space-y-6" data-client-form>
    @csrf
    @if($isEditing) @method('PUT') @endif

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <fieldset>
            <legend class="text-lg font-bold text-slate-900">This client is a</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach(['individual' => 'Person', 'company' => 'Company', 'organisation' => 'Organisation'] as $value => $label)
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold has-[:checked]:border-[#238b80] has-[:checked]:bg-[#e8f4f2] has-[:checked]:text-[#16776e]">
                        <input type="radio" name="client_type" value="{{ $value }}" data-client-type @checked($type === $value) class="text-[#238b80]">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="mt-5 grid gap-4 sm:grid-cols-2" data-for-type="person" @if($isCompany) hidden @endif>
            <label class="text-sm font-medium text-slate-700">First name <span class="text-red-600">*</span>
                <input name="first_name" value="{{ old('first_name', $isCompany ? '' : $client->first_name) }}" maxlength="255" @required(! $isCompany) data-required-for="person" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Last name
                <input name="last_name" value="{{ old('last_name', $client->last_name) }}" maxlength="255" class="{{ $field }}">
            </label>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2" data-for-type="company" @unless($isCompany) hidden @endunless>
            <label class="text-sm font-medium text-slate-700">Company name <span class="text-red-600">*</span>
                <input name="organisation_name" value="{{ old('organisation_name', $client->organisation_name) }}" maxlength="255" @required($isCompany) data-required-for="company" class="{{ $field }}">
            </label>
        </div>

    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">Contact</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-slate-700">Mobile
                <input name="mobile" value="{{ old('mobile', $client->mobile) }}" maxlength="20" inputmode="tel" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Email
                <input type="email" name="email" value="{{ old('email', $client->email) }}" maxlength="255" class="{{ $field }}">
            </label>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">Address</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <label class="text-sm font-medium text-slate-700 sm:col-span-2">Street address
                <textarea name="address" rows="2" class="{{ $field }}">{{ old('address', $client->address) }}</textarea>
            </label>
            <x-location-fields class="sm:col-span-2" :with-country="true"
                               :country="old('country', $client->country ?: 'India')" :state="old('state', $client->state)"
                               :district="old('district', $client->district)" :city="old('city', $client->city)"
                               field-class="{{ $field }}">
                <label class="text-sm font-medium text-slate-700">PIN code
                    <input name="pin_code" value="{{ old('pin_code', $client->pin_code) }}" maxlength="10" inputmode="numeric" class="{{ $field }}">
                </label>
            </x-location-fields>
        </div>
    </section>


    {{-- Rarely needed details stay folded away; they open when one of them already has a value or an error. --}}
    @php
        $moreFields = ['middle_name', 'company_id', 'occupation', 'website', 'display_name', 'alternate_mobile', 'phone', 'locality', 'referred_by', 'notes'];
        $moreOpen = collect($moreFields)->contains(fn ($name) => filled(old($name, $client->{$name})) || $errors->has($name));
    @endphp
    <details class="group rounded-xl border border-slate-200 bg-white shadow-sm" @if($moreOpen) open @endif>
        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-5 sm:px-6">
            <span class="font-semibold text-slate-900">More details <span class="font-normal text-slate-500">(other numbers, referral, notes)</span></span>
            <x-icon name="chevron" class="text-slate-400 transition group-open:rotate-180" />
        </summary>
        <div class="grid gap-4 px-5 pb-5 sm:grid-cols-2 sm:px-6 sm:pb-6">
            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2" data-for-type="person" @if($isCompany) hidden @endif>
            <label class="text-sm font-medium text-slate-700">Middle name
                <input name="middle_name" value="{{ old('middle_name', $client->middle_name) }}" maxlength="255" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Works at
                <select name="company_id" class="{{ $field }}">
                    <option value="">No company</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" @selected((string) old('company_id', $client->company_id) === (string) $company->id)>{{ $company->displayName() }}</option>
                    @endforeach
                </select>
                <a href="{{ route('clients.create', ['type' => 'company']) }}" class="mt-1 inline-block text-xs font-semibold text-[#16776e] hover:underline">+ Add a company</a>
            </label>
            <label class="text-sm font-medium text-slate-700">Occupation
                <input name="occupation" value="{{ old('occupation', $client->occupation) }}" maxlength="255" class="{{ $field }}">
            </label>
            </div>
            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2" data-for-type="company" @unless($isCompany) hidden @endunless>
            <label class="text-sm font-medium text-slate-700">Website
                <input type="url" name="website" value="{{ old('website', $client->website) }}" maxlength="255" placeholder="https://" class="{{ $field }}">
            </label>
            </div>
        <label class="text-sm font-medium text-slate-700 sm:col-span-2">Display name <span class="font-normal text-slate-400">(optional, how the name appears in lists)</span>
            <input name="display_name" value="{{ old('display_name', $client->display_name) }}" maxlength="255" class="{{ $field }}">
        </label>
            <label class="text-sm font-medium text-slate-700">Alternate mobile
                <input name="alternate_mobile" value="{{ old('alternate_mobile', $client->alternate_mobile) }}" maxlength="20" inputmode="tel" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Phone (landline)
                <input name="phone" value="{{ old('phone', $client->phone) }}" maxlength="20" inputmode="tel" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700 sm:col-span-2">Locality / area
                <input name="locality" value="{{ old('locality', $client->locality) }}" maxlength="255" placeholder="e.g. Gomti Nagar" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Referred by
                <input name="referred_by" value="{{ old('referred_by', $client->referred_by) }}" maxlength="255" class="{{ $field }}">
            </label>
            @if($isEditing)
                <label class="text-sm font-medium text-slate-700">Status
                    <select name="status" class="{{ $field }}">
                        <option value="active" @selected(old('status', $client->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $client->status) === 'inactive')>Inactive</option>
                    </select>
                </label>
            @endif
            <label class="text-sm font-medium text-slate-700 sm:col-span-2">Notes
                <textarea name="notes" rows="3" class="{{ $field }}">{{ old('notes', $client->notes) }}</textarea>
            </label>
        </div>
    </details>
    <div class="flex justify-end gap-3">
        <a href="{{ $isEditing ? route('clients.show', $client) : route('clients.index') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-xl bg-[#238b80] px-6 py-2.5 font-semibold text-white hover:bg-[#16776e]">{{ $isEditing ? 'Save changes' : 'Add client' }}</button>
    </div>
</form>
@endsection
