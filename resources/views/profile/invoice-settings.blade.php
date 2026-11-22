@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Invoice & GST Settings</h1>
            <p class="mt-1 text-sm text-slate-500">
                Configure invoice preferences, tax defaults and payment instructions.
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

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-slate-800">Firm Registration Information</h2>

        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <p class="text-sm text-slate-500">Firm Name</p>
                <p class="font-medium text-slate-800">{{ $firm->firm_name }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">GSTIN</p>
                <p class="font-medium text-slate-800">{{ $firm->gstin ?: 'Not provided in Firm Profile' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">State</p>
                <p class="font-medium text-slate-800">{{ $firm->state ?: 'Not provided' }}</p>
            </div>
            <div>
                <p class="text-sm text-slate-500">State Code</p>
                <p class="font-medium text-slate-800">{{ $firm->state_code ?: 'Not provided' }}</p>
            </div>
        </div>

        <a href="{{ route('firm-profile.index') }}"
           class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:underline">
            Edit Firm Registration Information
        </a>
    </div>

    <form method="POST" action="{{ route('profile.invoice-settings.update') }}"
          class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">GST Configuration</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        GST Registration Type
                    </label>
                    <select name="gst_registration_type" required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2">
                        <option value="regular" @selected(old('gst_registration_type', $settings->gst_registration_type ?? 'regular') === 'regular')>
                            Regular
                        </option>
                        <option value="composition" @selected(old('gst_registration_type', $settings->gst_registration_type ?? '') === 'composition')>
                            Composition
                        </option>
                        <option value="unregistered" @selected(old('gst_registration_type', $settings->gst_registration_type ?? '') === 'unregistered')>
                            Unregistered
                        </option>
                    </select>
                </div>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="gst_enabled" value="0">
                    <input type="checkbox" name="gst_enabled" value="1"
                           @checked(old('gst_enabled', $settings->gst_enabled ?? false))
                           class="h-4 w-4 rounded border-slate-300">
                    <span class="text-sm font-medium text-slate-700">
                        Enable GST calculation for new invoices
                    </span>
                </label>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Default Tax Rate (%)
                        </label>
                        <input type="number" name="default_tax_rate" step="0.01"
                               min="0" max="100" required
                               value="{{ old('default_tax_rate', $settings->default_tax_rate ?? '18.00') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Default SAC Code
                        </label>
                        <input type="text" name="default_sac_code" maxlength="30"
                               value="{{ old('default_sac_code', $settings->default_sac_code ?? '') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tax Calculation Mode
                        </label>
                        <select name="default_tax_mode" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="exclusive" @selected(old('default_tax_mode', $settings->default_tax_mode ?? 'exclusive') === 'exclusive')>
                                Tax Exclusive
                            </option>
                            <option value="inclusive" @selected(old('default_tax_mode', $settings->default_tax_mode ?? '') === 'inclusive')>
                                Tax Inclusive
                            </option>
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-3">
                    <input type="hidden" name="reverse_charge_applicable" value="0">
                    <input type="checkbox" name="reverse_charge_applicable" value="1"
                           @checked(old('reverse_charge_applicable', $settings->reverse_charge_applicable ?? false))
                           class="h-4 w-4 rounded border-slate-300">
                    <span class="text-sm font-medium text-slate-700">
                        Reverse charge applicable by default
                    </span>
                </label>

                <p class="text-xs text-slate-500">Defaults only. Confirm the GST rate and treatment before invoicing.</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Invoice Numbering & Payment Terms</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Invoice Prefix
                    </label>
                    <input type="text" name="invoice_prefix" required maxlength="30"
                           value="{{ old('invoice_prefix', $settings->invoice_prefix ?? 'INV') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Invoice Number Format
                    </label>
                    <input type="text" name="invoice_number_format" required maxlength="50"
                           value="{{ old('invoice_number_format', $settings->invoice_number_format ?? '{PREFIX}/{YEAR}/{NUMBER}') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Default Payment Due Days
                    </label>
                    <input type="number" name="default_payment_due_days" required min="0" max="365"
                           value="{{ old('default_payment_due_days', $settings->default_payment_due_days ?? 15) }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>

            <p class="mt-3 text-xs text-slate-500">Saved as a preference; not yet used for invoice numbers.</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Bank & Payment Instructions</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Bank Name</label>
                    <input type="text" name="bank_name"
                           value="{{ old('bank_name', $settings->bank_name ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Account Holder Name</label>
                    <input type="text" name="account_holder_name"
                           value="{{ old('account_holder_name', $settings->account_holder_name ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Account Number</label>
                    <input type="text" name="account_number"
                           value="{{ old('account_number', $settings->account_number ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">IFSC Code</label>
                    <input type="text" name="ifsc_code"
                           value="{{ old('ifsc_code', $settings->ifsc_code ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Branch Name</label>
                    <input type="text" name="branch_name"
                           value="{{ old('branch_name', $settings->branch_name ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">UPI ID</label>
                    <input type="text" name="upi_id"
                           value="{{ old('upi_id', $settings->upi_id ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Invoice Text & Conditions</h2>

            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Default Invoice Terms & Conditions
                    </label>
                    <textarea name="default_invoice_terms" rows="4"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('default_invoice_terms', $settings->default_invoice_terms ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Default Invoice Notes
                    </label>
                    <textarea name="default_invoice_notes" rows="3"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('default_invoice_notes', $settings->default_invoice_notes ?? '') }}</textarea>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Invoice Footer
                    </label>
                    <input type="text" name="invoice_footer" maxlength="500"
                           value="{{ old('invoice_footer', $settings->invoice_footer ?? '') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Invoice & GST Settings
            </button>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">TDS / Tax Deducted at Source</h2>

            <div class="space-y-4">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="tds_enabled" value="0">
                    <input type="checkbox" name="tds_enabled" value="1"
                           @checked(old('tds_enabled', $settings->tds_enabled ?? false))
                           class="h-4 w-4 rounded border-slate-300">
                    <span class="text-sm font-medium text-slate-700">
                        Enable TDS tracking for professional fee receipts
                    </span>
                </label>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            TDS Section
                        </label>
                        <input type="text" name="tds_section"
                               value="{{ old('tds_section', $settings->tds_section ?? '') }}"
                               placeholder="e.g. 194J, if applicable"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Default TDS Rate (%)
                        </label>
                        <input type="number" name="tds_default_rate" min="0" max="100" step="0.001"
                               value="{{ old('tds_default_rate', $settings->tds_default_rate ?? '') }}"
                               placeholder="Enter applicable rate"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            TAN
                        </label>
                        <input type="text" name="tan_number" maxlength="20"
                               value="{{ old('tan_number', $settings->tan_number ?? '') }}"
                               placeholder="TAN, where applicable"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>
                </div>

                <p class="text-xs text-slate-500">TDS is deducted by the client. Record actual deductions and reconcile with certificates.</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 font-semibold text-slate-800">Professional Tax Compliance</h2>

            <div class="space-y-4">
                <label class="flex items-center gap-3">
                    <input type="hidden" name="professional_tax_applicable" value="0">
                    <input type="checkbox" name="professional_tax_applicable" value="1"
                           @checked(old('professional_tax_applicable', $settings->professional_tax_applicable ?? false))
                           class="h-4 w-4 rounded border-slate-300">
                    <span class="text-sm font-medium text-slate-700">
                        Professional Tax registration / compliance applicable
                    </span>
                </label>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            State / UT
                        </label>
                        <input type="text" name="professional_tax_state"
                               value="{{ old('professional_tax_state', $settings->professional_tax_state ?? '') }}"
                               placeholder="State where registration applies"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Professional Tax Registration Number
                        </label>
                        <input type="text" name="professional_tax_registration_number"
                               value="{{ old('professional_tax_registration_number', $settings->professional_tax_registration_number ?? '') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Employer Enrolment Number
                        </label>
                        <input type="text" name="professional_tax_enrolment_number"
                               value="{{ old('professional_tax_enrolment_number', $settings->professional_tax_enrolment_number ?? '') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Compliance Frequency
                        </label>
                        <select name="professional_tax_frequency" required
                                class="w-full rounded-lg border border-slate-300 px-3 py-2">
                            <option value="monthly" @selected(old('professional_tax_frequency', $settings->professional_tax_frequency ?? 'monthly') === 'monthly')>Monthly</option>
                            <option value="quarterly" @selected(old('professional_tax_frequency', $settings->professional_tax_frequency ?? '') === 'quarterly')>Quarterly</option>
                            <option value="annual" @selected(old('professional_tax_frequency', $settings->professional_tax_frequency ?? '') === 'annual')>Annual</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Professional Tax Notes
                    </label>
                    <textarea name="professional_tax_notes" rows="3"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2">{{ old('professional_tax_notes', $settings->professional_tax_notes ?? '') }}</textarea>
                </div>

                <p class="text-xs text-slate-500">A State-level tax. It is not added to invoices.</p>
            </div>
        </div>
    </form>
</div>
@endsection