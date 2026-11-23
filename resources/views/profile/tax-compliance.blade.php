@extends('layouts.app')

@section('title', 'Tax & compliance')
@section('page-heading', 'Tax & compliance')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    <x-page-header title="Tax & Compliance" subtitle="Financial Year {{ $financialYear }} · {{ $financialYearStart->format('d M Y') }} to {{ $financialYearEnd->format('d M Y') }}" />

    @if($firm)
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="font-semibold text-slate-800">{{ $firm->firm_name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                GSTIN: {{ $firm->gstin ?: 'Not configured' }}
            </p>
            <p class="text-sm text-slate-500">
                State: {{ $firm->state ?: 'Not configured' }}
            </p>
        </section>
    @else
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            No active firm profile found. Configure your firm profile first.
        </div>
    @endif

    <section>
        <h2 class="mb-3 text-lg font-semibold text-slate-800">TDS Summary - {{ $financialYear }}</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Total TDS Recorded</p>
                <p class="mt-2 text-2xl font-bold text-slate-800">
                    @money((float) $tdsTotal)
                </p>
                <a href="{{ route('tds.index') }}" class="mt-3 inline-block text-sm text-indigo-600">Open TDS Register</a>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Certificates Awaited</p>
                <p class="mt-2 text-2xl font-bold text-amber-700">
                    @money((float) $tdsAwaited)
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $tdsAwaitedCount }} records</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Certificates Received</p>
                <p class="mt-2 text-2xl font-bold text-blue-700">
                    @money((float) $tdsReceived)
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $tdsReceivedCount }} records</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Marked Verified</p>
                <p class="mt-2 text-2xl font-bold text-green-700">
                    @money((float) $tdsVerified)
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $tdsVerifiedCount }} records</p>
            </div>
        </div>

        <p class="mt-2 text-xs text-slate-500">Internal status only. Check Form 26AS / AIS for credit.</p>
    </section>

    <section>
        <h2 class="mb-3 text-lg font-semibold text-slate-800">Professional Tax Summary</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Paid</p>
                <p class="mt-2 text-2xl font-bold text-green-700">
                    @money((float) $professionalTaxPaid)
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Total Pending</p>
                <p class="mt-2 text-2xl font-bold text-slate-800">
                    @money((float) $professionalTaxPending)
                </p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Overdue</p>
                <p class="mt-2 text-2xl font-bold text-red-700">
                    @money((float) $professionalTaxOverdue)
                </p>
                <p class="mt-1 text-xs text-slate-500">Pending records past their recorded due date</p>
            </div>

            <div class="rounded-xl border bg-white p-5">
                <p class="text-sm text-slate-500">Upcoming / Undated Pending</p>
                <p class="mt-2 text-2xl font-bold text-blue-700">
                    @money((float) $professionalTaxUpcoming)
                </p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

        <section class="rounded-xl border bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">TDS Certificates Awaited</h2>
                <a href="{{ route('tds.index') }}" class="text-sm text-indigo-600">View All</a>
            </div>

            <div class="space-y-3">
                @forelse($tdsCertificates as $item)
                    <div class="flex items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <p class="text-sm font-medium text-slate-800">
                                {{ $item->client?->display_name ?? 'Client unavailable' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Invoice: {{ $item->invoice?->invoice_number ?? 'Unavailable' }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Deduction date: {{ $item->deduction_date?->format('d M Y') ?? 'Not recorded' }}
                            </p>
                        </div>
                        <p class="text-sm font-semibold text-slate-800">
                            @money((float) $item->amount)
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No awaited TDS certificates in this financial year.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-semibold text-slate-800">Recent Professional Tax Records</h2>
                <a href="{{ route('professional-tax.index') }}" class="text-sm text-indigo-600">View All</a>
            </div>

            <div class="space-y-3">
                @forelse($professionalTaxRecords as $record)
                    <div class="flex items-center justify-between gap-3 border-b pb-3">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $record->state }}</p>
                            <p class="text-xs text-slate-500">
                                {{ $record->tax_period }} · {{ $record->registration_type }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Due: {{ $record->due_date?->format('d M Y') ?? 'Not specified' }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold">
                                @money((float) $record->amount)
                            </p>
                            <p class="text-xs {{ $record->payment_status === 'paid' ? 'text-green-600' : 'text-amber-600' }}">
                                {{ ucfirst($record->payment_status) }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No Professional Tax records.</p>
                @endforelse
            </div>
        </section>
    </div>

    <section class="rounded-xl border bg-white p-5">
        <h2 class="mb-4 font-semibold text-slate-800">State-wise Professional Tax Summary</h2>

        @if($professionalTaxByState->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="vn-stack-table w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-slate-500">
                            <th class="px-3 py-3">State</th>
                            <th class="px-3 py-3">Total Amount</th>
                            <th class="px-3 py-3">Paid Records</th>
                            <th class="px-3 py-3">Pending Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($professionalTaxByState as $stateRecord)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-3 font-medium">{{ $stateRecord->state }}</td>
                                <td class="px-3 py-3">@money((float) $stateRecord->total_amount)</td>
                                <td class="px-3 py-3">{{ $stateRecord->paid_count }}</td>
                                <td class="px-3 py-3">{{ $stateRecord->pending_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-slate-500">No state-wise Professional Tax records available.</p>
        @endif
    </section>

    <section class="rounded-xl border bg-white p-5">
        <h2 class="mb-4 font-semibold text-slate-800">Tax Settings & Registers</h2>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <a href="{{ route('profile.invoice-settings.index') }}" class="rounded-lg border p-4 hover:bg-slate-50">
                <p class="font-medium">GST & Invoice Settings</p>
                <p class="mt-1 text-sm text-slate-500">GST registration, invoice tax and TDS defaults.</p>
            </a>

            <a href="{{ route('tds.create') }}" class="rounded-lg border p-4 hover:bg-slate-50">
                <p class="font-medium">Record TDS Deduction</p>
                <p class="mt-1 text-sm text-slate-500">Add a client deduction and track its certificate.</p>
            </a>

            <a href="{{ route('tds.index') }}" class="rounded-lg border p-4 hover:bg-slate-50">
                <p class="font-medium">TDS Deduction Register</p>
                <p class="mt-1 text-sm text-slate-500">Review deductions and certificate statuses.</p>
            </a>

            <a href="{{ route('professional-tax.create') }}" class="rounded-lg border p-4 hover:bg-slate-50">
                <p class="font-medium">Add Professional Tax Record</p>
                <p class="mt-1 text-sm text-slate-500">Record a state-wise payment or compliance obligation.</p>
            </a>

            <a href="{{ route('professional-tax.index') }}" class="rounded-lg border p-4 hover:bg-slate-50">
                <p class="font-medium">Professional Tax Register</p>
                <p class="mt-1 text-sm text-slate-500">Review payment and return records.</p>
            </a>
        </div>
    </section>

</div>
@endsection