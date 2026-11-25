@extends('layouts.app')

@section('title', 'Settings')
@section('page-heading', 'Settings')

@section('content')
    @php
        $passwordProof = session('password_setup_proof');
        $canSetPassword = !auth()->user()->is_admin && (! auth()->user()->password_set_at
            || (($passwordProof['id'] ?? null) === auth()->id() && ($passwordProof['until'] ?? 0) > now()->timestamp));
    @endphp
    <section id="password" class="mx-auto mb-4 max-w-5xl scroll-mt-24 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="font-bold">{{ auth()->user()->password_set_at ? 'Change password' : 'Set your email sign-in password' }}</h2>
        @if(session('password_saved'))<p role="status" class="my-2 text-sm text-teal-700">{{ session('password_saved') }}</p>@endif
        @foreach($errors->passwordSetup->all() as $error)<p role="alert" class="my-2 text-sm text-red-700">{{ $error }}</p>@endforeach
        <form method="POST" action="{{ route('profile.password.store') }}" class="mt-3 grid gap-3 sm:grid-cols-3">
            @csrf
            @unless($canSetPassword)
                <label class="text-sm font-medium">Current password<input name="current_password" type="password" autocomplete="current-password" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
            @endunless
            <label class="text-sm font-medium">New password<input name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
            <label class="text-sm font-medium">Confirm password<input name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
            <p class="text-xs text-slate-600 sm:col-span-3">Use at least 8 characters.</p>
            <button class="min-h-11 justify-self-start rounded-lg bg-teal-700 px-4 font-semibold text-white sm:col-span-3">Save password</button>
        </form>
    </section>
<div class="space-y-6">

    <x-page-header title="Settings" subtitle="Profile, practice and app settings.">
        <a href="{{ route('profile.setup.start') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">{{ __('Complete my profile') }}</a>
    </x-page-header>

    @if(auth()->user()->account_type !== 'company')
        <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-teal-200 bg-teal-50 p-5">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Public profile &amp; CV</h2>
                <p class="text-sm text-slate-600">A one-page profile from your details, to share or download.</p>
            </div>
            <a href="{{ route('profile.cv.edit') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white">Open</a>
        </section>
    @endif

    {{-- PROFILE INFORMATION --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('1. Profile Information') }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ __('Manage the account-specific profile and contact information used in VakilNiti.') }}</p>
        </div>

        @if(($isCompany || $showAllProfileTypes) && $firm)
            <div class="mb-5 rounded-lg bg-slate-50 p-4">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Current Firm / Law Office') }}</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Firm Name') }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->firm_name ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Proprietor') }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->proprietor_name ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('GSTIN') }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->gstin ?: 'Not configured' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Email') }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->email ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Phone') }}</p>
                        <p class="mt-1 font-medium text-slate-800">{{ $firm->phone ?: 'Not provided' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">{{ __('Address') }}</p>
                        <p class="mt-1 font-medium text-slate-800">
                            {{ collect([$firm->address, $firm->city, $firm->district, $firm->state, $firm->pincode])->filter()->implode(', ') ?: 'Not provided' }}
                        </p>
                    </div>
                </div>
            </div>
        @elseif($isCompany)
            <p class="mb-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                {{ __('Add your company or firm details to use them on invoices and internal documents.') }}
            </p>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @if($showAllProfileTypes)
                <a href="{{ route('profile.personal.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Personal Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage personal identity, address and individual information.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Personal Profile') }} →</p>
                </a>
                <a href="{{ route('profile.advocate.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Advocate Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage registration, practice areas and court information.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Advocate Profile') }} →</p>
                </a>
                <a href="{{ route('profile.professional.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Professional Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage credentials, qualifications and professional information.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Professional Profile') }} →</p>
                </a>
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Company / Firm Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage firm identity, business address and tax details.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Company Profile') }} →</p>
                </a>
            @elseif($isCompany)
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Company / Firm Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Manage company identity, proprietor, business address, GST and office details.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Company Profile') }} →</p>
                </a>
            @else
                <a href="{{ route('profile.advocate.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Advocate Profile') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Update your registration details, practice areas, profile photo and court information.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Advocate Profile') }} →</p>
                </a>
                <a href="{{ route('firm-profile.index') }}"
                   class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Office & billing details') }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ __('Your chamber or office address and GSTIN, printed on your invoices.') }}</p>
                    <p class="mt-4 text-sm font-semibold text-blue-600">{{ $firm ? 'Update office details' : 'Add office details' }} &rarr;</p>
                </a>
            @endif

            <a href="{{ route('profile.contacts.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Contact Information') }}</h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('Manage personal, professional and office contact channels.') }}
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Contact Information') }} →</p>
            </a>

        </div>
    </section>

    @unless($user->isTeamMember())
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Currency') }}</h2>
        @if(session('preferences_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('preferences_success') }}</p>
        @endif
        <form method="POST" action="{{ route('profile.preferences.update') }}" class="mt-3 flex flex-wrap items-end gap-3">
            @csrf
            <label class="block min-w-[14rem] text-sm font-medium text-slate-700">
                Symbol shown on amounts
                <select name="currency" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                    @foreach(\App\Enums\Currency::cases() as $currencyOption)
                        <option value="{{ $currencyOption->value }}" @selected(old('currency', $user->currency ?? 'INR') === $currencyOption->value)>{{ $currencyOption->label() }}</option>
                    @endforeach
                </select>
                @error('currency') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <button type="submit" class="min-h-11 rounded-lg bg-[#16776e] px-5 font-semibold text-white hover:bg-[#125f58]">{{ __('Save') }}</button>
        </form>
    </section>

    {{-- PRACTICE STATES --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('2. Practice States & Jurisdictions') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('Choose the States/UTs where you practise.') }}</p>

        <a href="{{ route('profile.practice-states.index') }}"
           class="mt-4 inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            {{ __('Manage Practice States & Jurisdictions') }}
        </a>
    </section>

    {{-- INVOICE AND TAX SETTINGS --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('3. Invoice & Tax Settings') }}</h2>
            <p class="mt-1 text-sm text-slate-600">
                {{ __('Manage invoicing, GST configuration and tax-compliance records together.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <a href="{{ route('profile.invoice-settings.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Invoice & GST Settings') }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ __('GST, numbering, bank details and terms.') }}</p>
                <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Manage Invoice & GST Settings') }} →</p>
            </a>

            <a href="{{ route('profile.tax-compliance') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="text-base font-semibold text-slate-900">{{ __('Tax & Compliance') }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ __('TDS, certificates and Professional Tax.') }}</p>
                <p class="mt-4 text-sm font-semibold text-blue-600">{{ __('Open Tax & Compliance') }} →</p>
            </a>

        </div>
    </section>

    @endunless

    {{-- SYSTEM PREFERENCES --}}
    @php
        $choice = 'flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 hover:border-[#16776e] has-[:checked]:border-[#16776e] has-[:checked]:bg-teal-50 has-[:checked]:text-[#16776e] has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-[#16776e]/40';
        $switchRow = 'flex min-h-12 cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm hover:border-[#16776e]';
        $switch = 'relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-slate-300 transition before:absolute before:left-0.5 before:top-0.5 before:h-5 before:w-5 before:rounded-full before:bg-white before:shadow before:transition checked:bg-[#16776e] checked:before:translate-x-5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#16776e]/40';
    @endphp
    <section id="system-preferences" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('4. System Preferences') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('How the calendar opens, what Home shows, and which reminders reach your bell.') }}</p>

        @if(session('system_preferences_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('system_preferences_success') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.system-preferences.update') }}" class="mt-4 space-y-6">
            @csrf

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Appearance') }}</legend>
                <div class="mt-2 grid grid-cols-3 gap-2 sm:max-w-md">
                    @foreach(\App\Models\User::PREFERENCES['theme']['options'] as $value => $label)
                        <label class="{{ $choice }}">
                            <input type="radio" name="theme" value="{{ $value }}" class="sr-only" @checked($user->preference('theme') === $value)>
                            {{ __($label) }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Calendar') }}</legend>
                <div class="mt-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">{{ __('Opens on') }}</p>
                        <div class="grid grid-cols-4 gap-2">
                            @foreach(\App\Models\User::PREFERENCES['calendar_view']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="calendar_view" value="{{ $value }}" class="sr-only" @checked($user->preference('calendar_view') === $value)>
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">{{ __('Week starts on') }}</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach(\App\Models\User::PREFERENCES['week_starts_on']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="week_starts_on" value="{{ $value }}" class="sr-only" @checked($user->preference('week_starts_on') === $value)>
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Home page') }}</legend>
                <label class="{{ $switchRow }} mt-2">
                    <span>
                        <span class="block font-semibold text-slate-800">{{ __('Live display boards') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('The court display board panel on Home.') }}</span>
                    </span>
                    <input type="checkbox" name="home_display_boards" value="1" class="{{ $switch }}" @checked($user->preference('home_display_boards'))>
                </label>
            </fieldset>

            <fieldset>
                <legend class="text-sm font-bold uppercase tracking-wide text-slate-500">{{ __('Reminders in the bell') }}</legend>
                <div class="mt-2 space-y-2">
                    <div>
                        <p class="mb-1.5 text-sm font-semibold text-slate-800">{{ __('Hearings') }}</p>
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                            @foreach(\App\Models\User::PREFERENCES['remind_hearings']['options'] as $value => $label)
                                <label class="{{ $choice }}">
                                    <input type="radio" name="remind_hearings" value="{{ $value }}" class="sr-only" @checked($user->preference('remind_hearings') === $value)>
                                    {{ __($label) }}
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
                                <span class="block font-semibold text-slate-800">{{ __($label) }}</span>
                                <span class="block text-xs text-slate-500">{{ $hint }}</span>
                            </span>
                            <input type="checkbox" name="{{ $key }}" value="1" class="{{ $switch }}" @checked($user->preference($key))>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex justify-end border-t border-slate-100 pt-4">
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-6 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Save system preferences') }}</button>
            </div>
        </form>
    </section>

    {{-- CALENDAR SYNC --}}
    <section id="calendar-sync" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Calendar sync') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('Hearings and appointments in Google Calendar or your iPhone calendar, kept up to date by themselves.') }}</p>
        @if(session('calendar_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('calendar_success') }}</p>
        @endif
        @if($user->calendar_token)
            @php
                $feedUrl = route('calendar.feed', $user->calendar_token);
                $webcalUrl = preg_replace('#^https?://#', 'webcal://', $feedUrl);
            @endphp
            <div class="mt-3 flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <span class="min-w-0 flex-1 truncate text-slate-700" title="{{ $feedUrl }}">{{ $feedUrl }}</span>
                <button type="button" data-copy="{{ $feedUrl }}" class="inline-flex min-h-9 items-center gap-1 rounded-full border border-slate-200 px-3 font-semibold text-slate-700"><x-icon name="copy" :size="14" /><span data-copy-label>{{ __('Copy link') }}</span></button>
            </div>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <a href="{{ $webcalUrl }}" class="flex min-h-11 items-center justify-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Add to iPhone / Mac calendar') }}</a>
                <a href="https://calendar.google.com/calendar/r?cid={{ rawurlencode($webcalUrl) }}" target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center justify-center rounded-xl border border-[#16776e] px-4 text-sm font-semibold text-[#16776e] hover:bg-teal-50">{{ __('Add to Google Calendar') }}</a>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ __('Anyone with this link can see your hearings. If it was shared by mistake, make a new one.') }}</p>
            <form method="POST" action="{{ route('profile.calendar-link') }}" data-confirm="Make a new calendar link? The current link will stop working everywhere it was added." class="mt-2">
                @csrf
                <button type="submit" class="text-sm font-semibold text-slate-600 hover:underline">{{ __('Make a new link') }}</button>
            </form>
        @else
            <form method="POST" action="{{ route('profile.calendar-link') }}" class="mt-3">
                @csrf
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Turn on calendar sync') }}</button>
            </form>
        @endif
    </section>

    {{-- TEAM: juniors and clerks --}}
    <section id="team" class="scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-semibold text-slate-900">{{ __('Team') }}</h2>
        @if(session('team_success'))
            <p role="status" class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('team_success') }}</p>
        @endif

        @if($user->isTeamMember())
            <p class="mt-1 text-sm text-slate-700">
                You are a <strong>{{ \App\Models\User::TEAM_ROLES[$user->team_role] ?? 'member' }}</strong> in the workspace of {{ $user->teamOwner?->name ?? 'an advocate' }}.
                {{ $user->isClerk() ? 'You can see cases and the calendar, and add next dates and tasks.' : 'You can add and edit cases, dates, tasks and clients.' }}
            </p>
            <form method="POST" action="{{ route('profile.team.leave') }}" data-confirm="Leave this team? You will see only your own workspace." class="mt-3">
                @csrf
                <button type="submit" class="min-h-10 rounded-xl border border-red-200 px-4 text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('Leave team') }}</button>
            </form>
        @else
            <p class="mt-1 text-sm text-slate-600">{{ __('Juniors add and edit cases, dates, tasks and clients. Clerks see them and add next dates and tasks. Neither sees your billing or settings.') }}</p>

            @php
                $members = $user->teamMembers()->orderBy('name')->get();
                $invites = \App\Models\TeamInvite::where('owner_id', $user->id)->whereNull('accepted_at')->latest()->get();
            @endphp
            @if($members->isNotEmpty() || $invites->isNotEmpty())
                <ul class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @foreach($members as $member)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                            <span class="min-w-0">
                                <span class="block font-semibold text-slate-900">{{ $member->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $member->mobile }}</span>
                            </span>
                            <span class="flex items-center gap-2">
                                <form method="POST" action="{{ route('profile.team.role', $member) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" data-autosubmit aria-label="Role of {{ $member->name }}" class="min-h-9 rounded-lg border border-slate-300 bg-transparent px-2 text-sm">
                                        @foreach(\App\Models\User::TEAM_ROLES as $value => $label)
                                            <option value="{{ $value }}" @selected($member->team_role === $value)>{{ __($label) }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('profile.team.remove', $member) }}" data-confirm="Remove {{ $member->name }} from your team?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm font-semibold text-red-700 hover:underline">{{ __('Remove') }}</button>
                                </form>
                            </span>
                        </li>
                    @endforeach
                    @foreach($invites as $invite)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                            <span>
                                <span class="block font-semibold text-slate-900">{{ $invite->email ?: $invite->mobile }}</span>
                                <span class="block text-xs text-amber-700">Invited as {{ \App\Models\User::TEAM_ROLES[$invite->role] ?? $invite->role }} · joins on first sign-in</span>
                            </span>
                            <form method="POST" action="{{ route('profile.team.cancel', $invite) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-semibold text-slate-600 hover:underline">{{ __('Cancel invite') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('profile.team.invite') }}" class="mt-3 flex flex-wrap items-end gap-2">
                @csrf
                <label class="block min-w-[12rem] flex-1 text-sm font-semibold text-slate-700">
                    {{ __('Email or mobile number') }}
                    <input type="text" name="contact" required autocomplete="off" placeholder="name@example.com" value="{{ old('contact') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5">
                </label>
                <label class="block text-sm font-semibold text-slate-700">
                    Role
                    <select name="role" class="mt-1 rounded-xl border border-slate-300 bg-transparent px-3 py-2.5">
                        @foreach(\App\Models\User::TEAM_ROLES as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Invite') }}</button>
            </form>
            @error('contact', 'team') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        @endif
    </section>

</div>


@endsection