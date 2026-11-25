{{-- Pop-up: a quick client, a person (name and phone) or a company (name, phone, email). Saves through clients.store. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
    $isCompany ??= false;
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ $isCompany ? __('New company') : __('New client') }}</h2>

{{-- Person or company: switch without leaving the pop-up. --}}
<nav aria-label="{{ __('Client type') }}" class="mt-3 inline-flex overflow-hidden rounded-xl border border-slate-200 text-sm font-semibold">
    <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" @unless($isCompany) aria-current="page" @endunless
       class="inline-flex min-h-10 items-center px-4 {{ $isCompany ? 'text-slate-600 hover:bg-slate-50' : 'bg-[#16776e] text-white' }}">{{ __('Person') }}</a>
    <a href="{{ route('clients.create', ['type' => 'company']) }}" data-sheet="{{ route('sheets.client', ['type' => 'company']) }}" @if($isCompany) aria-current="page" @endif
       class="inline-flex min-h-10 items-center border-l border-slate-200 px-4 {{ $isCompany ? 'bg-[#16776e] text-white' : 'text-slate-600 hover:bg-slate-50' }}">{{ __('Company') }}</a>
</nav>

<form method="POST" action="{{ route('clients.store') }}" data-sheet-form data-sheet-success="{{ $isCompany ? __('Company added') : __('Client added') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="client_type" value="{{ $isCompany ? 'company' : 'individual' }}">
    @if($isCompany)
        <label class="{{ $label }}">
            {{ __('Company name') }}
            <input type="text" name="organisation_name" required maxlength="255" autofocus class="{{ $field }}">
        </label>
    @else
        <div class="grid grid-cols-2 gap-3">
            <label class="{{ $label }}">
                {{ __('First name') }}
                <input type="text" name="first_name" required maxlength="255" autofocus class="{{ $field }}">
            </label>
            <label class="{{ $label }}">
                {{ __('Last name') }}
                <input type="text" name="last_name" maxlength="255" class="{{ $field }}">
            </label>
        </div>
    @endif
    <label class="{{ $label }}">
        {{ $isCompany ? __('Phone') : __('Mobile') }}
        <input type="tel" name="mobile" maxlength="20" inputmode="tel" placeholder="{{ __('98xxxxxxxx') }}" class="{{ $field }}">
    </label>
    <label class="{{ $label }}">
        {{ __('Email') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span>
        <input type="email" name="email" maxlength="255" class="{{ $field }}">
    </label>
    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('clients.create', $isCompany ? ['type' => 'company'] : []) }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('More details') }}</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ $isCompany ? __('Add company') : __('Add client') }}</button>
    </div>
</form>
