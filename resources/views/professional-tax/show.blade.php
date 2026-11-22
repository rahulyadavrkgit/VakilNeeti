@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-800">Professional Tax Record</h1>
        <div class="flex gap-3">
            <a href="{{ route('professional-tax.edit', $record) }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white">Edit Record</a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="rounded-xl border bg-white p-6">
        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">State</dt><dd class="font-medium">{{ $record->state }}</dd></div>
            <div><dt class="text-sm text-slate-500">Registration Type</dt><dd>{{ $record->registration_type }}</dd></div>
            <div><dt class="text-sm text-slate-500">Registration Number</dt><dd>{{ $record->registration_number ?: '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Enrolment Number</dt><dd>{{ $record->enrolment_number ?: '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Tax Period</dt><dd>{{ $record->tax_period }}</dd></div>
            <div><dt class="text-sm text-slate-500">Period Type</dt><dd>{{ ucfirst(str_replace('_', ' ', $record->period_type)) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Due Date</dt><dd>{{ $record->due_date?->format('d-m-Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Amount</dt><dd>@money((float) $record->amount)</dd></div>
            <div><dt class="text-sm text-slate-500">Payment Status</dt><dd>{{ ucfirst($record->payment_status) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Payment Date</dt><dd>{{ $record->payment_date?->format('d-m-Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Challan Number</dt><dd>{{ $record->challan_number ?: '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Payment Reference</dt><dd>{{ $record->payment_reference ?: '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Payment Method</dt><dd>{{ $record->payment_method ? ucfirst($record->payment_method) : '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Return Status</dt><dd>{{ ucfirst(str_replace('_', ' ', $record->return_status)) }}</dd></div>
            <div><dt class="text-sm text-slate-500">Return Filing Date</dt><dd>{{ $record->return_filing_date?->format('d-m-Y') ?? '—' }}</dd></div>
            <div><dt class="text-sm text-slate-500">Acknowledgement Number</dt><dd>{{ $record->return_acknowledgement_number ?: '—' }}</dd></div>
        </dl>

        @if($record->notes)
            <div class="mt-5 border-t pt-4">
                <p class="text-sm text-slate-500">Notes</p>
                <p>{{ $record->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endsection