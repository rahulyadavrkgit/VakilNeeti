@extends('layouts.app')

@section('title', 'Settings')
@section('page-heading', 'Settings')

@section('content')
<div class="space-y-6">

    <x-page-header title="Settings" subtitle="Profile and practice settings for your {{ $isCompany ? 'company' : 'advocate' }} account.">
        <a href="{{ route('profile.setup.start') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">Complete my profile</a>
    </x-page-header>

    {{-- PROFILE INFORMATION --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800">1. Profile Information</h2>
            <p class="mt-1 text-sm text-slate-600">Manage the account-specific profile and contact information used in VakilNiti.</p>
        </div>

        @if(($isCompany || $showAllProfileTypes) && $firm)
            <div class="mb-5 rounded-lg bg-slate-50 p-4">
                <h3 class="font-semibold text-slate-800">Current Firm / Law Office</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs text-slate-500">Firm Name</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->firm_name ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Proprietor</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->proprietor_name ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">GSTIN</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->gstin ?: 'Not configured' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Email</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->email ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Phone</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->phone ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Address</p>
                        <p class="mt-1 font-medium text-slate-800">
                            {{ collect([$firm->address, $firm->city, $firm->district, $firm->state, $firm->pincode])->filter()->implode(', ') ?: 'Not provided' }}
                        </p>
                    </div>
                </div>
            </div>
        @elseif($isCompany)
            <p class="mb-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                Add your company or firm details to use them on invoices and internal documents.
            </p>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @if($showAllProfileTypes)
                <a href="{{ route('profile.personal.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Personal Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Manage personal identity, address and individual information.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Personal Profile &rarr;</p>
                </a>
                <a href="{{ route('profile.advocate.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Advocate Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Manage registration, practice areas and court information.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Advocate Profile &rarr;</p>
                </a>
                <a href="{{ route('profile.professional.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Professional Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Manage credentials, qualifications and professional information.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Professional Profile &rarr;</p>
                </a>
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Company / Firm Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Manage firm identity, business address and tax details.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Company Profile &rarr;</p>
                </a>
            @elseif($isCompany)
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Company / Firm Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Manage company identity, proprietor, business address, GST and office details.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Company Profile &rarr;</p>
                </a>
            @else
                <a href="{{ route('profile.advocate.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Advocate Profile</h3>
                    <p class="mt-2 text-sm text-slate-600">Update your registration details, practice areas, profile photo and court information.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">Manage Advocate Profile &rarr;</p>
                </a>
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="font-semibold text-slate-800">Office &amp; billing details</h3>
                    <p class="mt-2 text-sm text-slate-600">Your chamber or office address and GSTIN, printed on your invoices.</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ $firm ? 'Update office details' : 'Add office details' }} &rarr;</p>
                </a>
            @endif

            <a href="{{ route('profile.contacts.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Contact Information</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Manage personal, professional and office contact channels.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Contact Information &rarr;</p>
            </a>

        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-800">Workspace preferences</h2>
        <p class="mt-1 text-sm text-slate-600">Choose the tools and information you want to focus on.</p>

        @if(session('preferences_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('preferences_success') }}</p>
        @endif
        @error('onboarding_features')
            <p role="alert" class="mt-3 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @error('onboarding_features.*')
            <p role="alert" class="mt-3 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <form id="workspace-preferences-form" method="POST" action="{{ route('profile.preferences.update') }}" class="mt-4 space-y-4">
            @csrf
            <fieldset>
                <legend class="sr-only">Select workspace features</legend>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($onboardingFeatures as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 p-3 text-sm hover:border-amber-400 has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50">
                            <input type="checkbox" name="onboarding_features[]" value="{{ $value }}"
                                   @checked(in_array($value, (array) old('onboarding_features', $user->onboarding_features ?? []), true))
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="block text-sm font-medium text-slate-700" data-preferences-other
                   @if(!in_array('other', (array) old('onboarding_features', $user->onboarding_features ?? []), true)) hidden @endif>
                Describe the other feature
                <textarea name="onboarding_other" rows="2" maxlength="500" @required(in_array('other', (array) old('onboarding_features', $user->onboarding_features ?? []), true))
                          class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5">{{ old('onboarding_other', $user->onboarding_other) }}</textarea>
                @error('onboarding_other') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block max-w-xs text-sm font-medium text-slate-700">
                Currency
                <select name="currency" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                    @foreach(\App\Enums\Currency::cases() as $currencyOption)
                        <option value="{{ $currencyOption->value }}" @selected(old('currency', $user->currency ?? 'INR') === $currencyOption->value)>{{ $currencyOption->label() }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs font-normal text-slate-500">Changes the symbol shown on amounts. Amounts are not converted.</span>
                @error('currency') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <button type="submit" class="rounded-lg bg-[#238b80] px-5 py-2.5 font-semibold text-white hover:bg-[#16776e]">
                Save preferences
            </button>
        </form>
    </section>

    {{-- PRACTICE STATES --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-800">2. Practice States &amp; Jurisdictions</h2>
        <p class="mt-1 text-sm text-slate-600">Choose the States/UTs where you practise.</p>

        <a href="{{ route('profile.practice-states.index') }}"
           class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Manage Practice States &amp; Jurisdictions
        </a>
    </section>

    {{-- INVOICE AND TAX SETTINGS --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800">3. Invoice &amp; Tax Settings</h2>
            <p class="mt-1 text-sm text-slate-600">
                Manage invoicing, GST configuration and tax-compliance records together.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <a href="{{ route('profile.invoice-settings.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Invoice &amp; GST Settings</h3>
                <p class="mt-2 text-sm text-slate-600">GST, numbering, bank details and terms.</p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Invoice &amp; GST Settings &rarr;</p>
            </a>

            <a href="{{ route('profile.tax-compliance') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Tax &amp; Compliance</h3>
                <p class="mt-2 text-sm text-slate-600">TDS, certificates and Professional Tax.</p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open Tax &amp; Compliance &rarr;</p>
            </a>

        </div>
    </section>

    {{-- SYSTEM PREFERENCES --}}
    @php
        $choice = 'flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e] has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50 has-[:checked]:text-[#16776e] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#16776e]/40';
        $switchRow = 'flex min-h-12 cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:border-[#16776e]';
        $switch = 'relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-slate-300 transition before:absolute before:left-0.5 before:top-0.5 before:h-5 before:w-5 before:rounded-full before:bg-white before:shadow before:transition checked:bg-[#16776e] checked:before:translate-x-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#16776e]/40';
    @endphp
    <section id="system-preferences" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-800">4. System Preferences</h2>
        <p class="mt-1 text-sm text-slate-600">How the calendar opens, what Home shows, and which reminders reach your bell.</p>

        @if(session('system_preferences_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('system_preferences_success') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.system-preferences.update') }}" class="mt-4 space-y-6">
            @csrf

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">Calendar</legend>
                <div class="mt-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">Opens on</p>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach(\App\Models\User::PREFERENCES['calendar_view']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="calendar_view" value="{{ $value }}" class="sr-only" @checked($user->preference('calendar_view') === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">Week starts on</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(\App\Models\User::PREFERENCES['week_starts_on']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="week_starts_on" value="{{ $value }}" class="sr-only" @checked($user->preference('week_starts_on') === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">Home page</legend>
                <label class="{{ $switchRow }} mt-2">
                    <span>
                        <span class="block font-semibold text-slate-800">Live display boards</span>
                        <span class="block text-xs text-slate-500">The court display board panel on Home.</span>
                    </span>
                    <input type="checkbox" name="home_display_boards" value="1" class="{{ $switch }}" @checked($user->preference('home_display_boards'))>
                </label>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">Reminders in the bell</legend>
                <div class="mt-2 space-y-2">
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">Hearings</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach(\App\Models\User::PREFERENCES['remind_hearings']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="remind_hearings" value="{{ $value }}" class="sr-only" @checked($user->preference('remind_hearings') === $value)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @foreach([
                        'remind_appointments' => ['Appointments', 'Client meetings today and tomorrow.'],
                        'remind_tasks' => ['Tasks', 'Tasks due today, and overdue ones.'],
                        'remind_invoices' => ['Overdue invoices', 'Invoices past their due date with money still owed.'],
                    ] as $key => [$label, $hint])
                        <label class="{{ $switchRow }}">
                            <span>
                                <span class="block font-semibold text-slate-800">{{ $label }}</span>
                                <span class="block text-xs text-slate-500">{{ $hint }}</span>
                            </span>
                            <input type="checkbox" name="{{ $key }}" value="1" class="{{ $switch }}" @checked($user->preference($key))>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-6 text-sm font-semibold text-white hover:bg-[#125f58]">Save system preferences</button>
            </div>
        </form>
    </section>

</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        const preferencesForm = document.querySelector('#workspace-preferences-form');
        if (!preferencesForm) return;

        const otherField = preferencesForm.querySelector('[data-preferences-other]');
        const otherInput = otherField.querySelector('textarea');

        function toggleOtherField() {
            const selected = Boolean(preferencesForm.querySelector('input[name="onboarding_features[]"][value="other"]:checked'));
            otherField.hidden = !selected;
            otherInput.disabled = !selected;
            otherInput.required = selected;
        }

        preferencesForm.querySelectorAll('input[name="onboarding_features[]"]').forEach(function (input) {
            input.addEventListener('change', toggleOtherField);
        });
        toggleOtherField();
    });
</script>
@endsection