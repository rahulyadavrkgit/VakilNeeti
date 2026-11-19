@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <div class="rounded-2xl bg-slate-900 p-6 text-white">
        <h1 class="text-2xl font-bold">Profile Settings</h1>
        <p class="mt-2 text-sm text-slate-300">
            Manage your personal identity, professional credentials, law office,
            practice jurisdictions, invoices, GST and tax compliance.
        </p>
    </div>

    {{-- PROFILE INFORMATION --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4">
            <h2 class="text-lg font-bold text-slate-800">1. Profile Information</h2>
            <p class="mt-1 text-sm text-slate-600">
                Manage personal, professional, firm and contact information from one section.
            </p>
        </div>

        @if($firm)
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
        @else
            <p class="mb-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-800">
                Firm details have not been configured. Add your firm information to use it in invoices and internal documents.
            </p>
        @endif

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

            <a href="{{ route('profile.personal.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Personal Profile</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Personal identity, address and individual information.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Personal Profile &rarr;</p>
            </a>

            <a href="{{ route('profile.professional.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Professional Profile</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Bar Council enrolment, AOR details, qualifications and professional information.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Professional Profile &rarr;</p>
            </a>

            <a href="{{ route('firm-profile.index') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Firm / Law Office / Company Profile</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Firm identity, proprietor, business address, GST registration and office details.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Firm Profile &rarr;</p>
            </a>

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

    {{-- PRACTICE STATES --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="text-lg font-bold text-slate-800">2. Practice States &amp; Jurisdictions</h2>
        <p class="mt-1 text-sm text-slate-600">
            Select multiple States and Union Territories to customise your High Court
            and District Court navigation.
        </p>

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
                <p class="mt-2 text-sm text-slate-600">
                    GST configuration, invoice numbering, tax settings, payment instructions,
                    bank details and invoice terms.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Manage Invoice &amp; GST Settings &rarr;</p>
            </a>

            <a href="{{ route('profile.tax-compliance') }}"
               class="rounded-xl border border-slate-200 p-5 transition hover:border-blue-400 hover:shadow-sm">
                <h3 class="font-semibold text-slate-800">Tax &amp; Compliance</h3>
                <p class="mt-2 text-sm text-slate-600">
                    TDS deductions, certificate follow-ups, tax-credit reconciliation
                    and Professional Tax records.
                </p>
                <p class="mt-4 text-sm font-semibold text-blue-600">Open Tax &amp; Compliance &rarr;</p>
            </a>

        </div>
    </section>

    {{-- SYSTEM PREFERENCES --}}
    <section class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-800">4. System Preferences</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Application display, calendar preferences, notifications and other user settings.
                </p>
            </div>
            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                Planned
            </span>
        </div>
    </section>

</div>
@endsection