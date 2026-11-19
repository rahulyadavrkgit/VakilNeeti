@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Professional Tax Register</h1>
            <p class="text-sm text-slate-500">Firm-level state tax compliance records.</p>
        </div>
        <a href="{{ route('professional-tax.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
            Add Tax Record
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Pending Amount</p>
            <p class="mt-2 text-2xl font-bold">₹{{ number_format((float) $pendingAmount, 2) }}</p>
        </div>
        <div class="rounded-xl border bg-white p-5">
            <p class="text-sm text-slate-500">Paid Amount</p>
            <p class="mt-2 text-2xl font-bold">₹{{ number_format((float) $paidAmount, 2) }}</p>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="p-3">State</th>
                    <th class="p-3">Registration</th>
                    <th class="p-3">Period</th>
                    <th class="p-3">Due Date</th>
                    <th class="p-3">Amount</th>
                    <th class="p-3">Payment</th>
                    <th class="p-3">Return</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($records as $record)
                    <tr>
                        <td class="p-3">{{ $record->state }}</td>
                        <td class="p-3">{{ $record->registration_type }}</td>
                        <td class="p-3">{{ $record->tax_period }}</td>
                        <td class="p-3">{{ $record->due_date?->format('d-m-Y') ?? '—' }}</td>
                        <td class="p-3">₹{{ number_format((float) $record->amount, 2) }}</td>
                        <td class="p-3">{{ ucfirst($record->payment_status) }}</td>
                        <td class="p-3">{{ ucfirst(str_replace('_', ' ', $record->return_status)) }}</td>
                        <td class="p-3">
                            <a href="{{ route('professional-tax.show', $record) }}" class="text-indigo-600">View</a>
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('professional-tax.edit', $record) }}" class="text-indigo-600">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center text-slate-500">No Professional Tax records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $records->links() }}
</div>
@endsection