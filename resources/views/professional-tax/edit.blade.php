@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Update Professional Tax Record</h1>
        <p class="text-sm text-slate-500">{{ $record->state }} — {{ $record->tax_period }}</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('professional-tax.update', $record) }}"
          class="space-y-5 rounded-xl border bg-white p-6">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label class="mb-1 block text-sm font-medium">State / UT</label>
        <select name="state" required class="w-full rounded-lg border bg-white p-2">
            <x-state-options :groups="\App\Models\State::dropdownGroups()" :selected="old('state', $record->state ?? null)" />
        </select>
        @error('state') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Registration Type</label>
        <select name="registration_type" required class="w-full rounded-lg border p-2">
            @foreach(['PTEC', 'PTRC', 'Both', 'Other'] as $type)
                <option value="{{ $type }}" @selected(old('registration_type', $record->registration_type ?? 'PTEC') === $type)>
                    {{ $type }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Registration Number</label>
        <input name="registration_number" value="{{ old('registration_number', $record->registration_number ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Enrolment Number</label>
        <input name="enrolment_number" value="{{ old('enrolment_number', $record->enrolment_number ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Period Type</label>
        <select name="period_type" required class="w-full rounded-lg border p-2">
            @foreach(['monthly', 'quarterly', 'annual', 'one_time'] as $period)
                <option value="{{ $period }}" @selected(old('period_type', $record->period_type ?? 'monthly') === $period)>
                    {{ ucfirst(str_replace('_', ' ', $period)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Tax Period</label>
        <input name="tax_period" required maxlength="50"
               placeholder="e.g. April 2026 or FY 2026-27"
               value="{{ old('tax_period', $record->tax_period ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Due Date</label>
        <input type="date" name="due_date"
               value="{{ old('due_date', isset($record) ? $record->due_date?->format('Y-m-d') : '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Amount (@currency)</label>
        <input type="number" name="amount" min="0" step="0.01" required
               value="{{ old('amount', $record->amount ?? '0.00') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Payment Status</label>
        <select name="payment_status" required class="w-full rounded-lg border p-2">
            <option value="pending" @selected(old('payment_status', $record->payment_status ?? 'pending') === 'pending')>Pending</option>
            <option value="paid" @selected(old('payment_status', $record->payment_status ?? 'pending') === 'paid')>Paid</option>
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Payment Date</label>
        <input type="date" name="payment_date"
               value="{{ old('payment_date', isset($record) ? $record->payment_date?->format('Y-m-d') : '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Challan Number</label>
        <input name="challan_number" value="{{ old('challan_number', $record->challan_number ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Payment Reference</label>
        <input name="payment_reference" value="{{ old('payment_reference', $record->payment_reference ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Payment Method</label>
        <select name="payment_method" class="w-full rounded-lg border p-2">
            <option value="">Select</option>
            @foreach(['online', 'bank', 'other'] as $method)
                <option value="{{ $method }}" @selected(old('payment_method', $record->payment_method ?? '') === $method)>
                    {{ ucfirst($method) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Return Status</label>
        <select name="return_status" required class="w-full rounded-lg border p-2">
            @foreach(['not_required', 'pending', 'filed'] as $status)
                <option value="{{ $status }}" @selected(old('return_status', $record->return_status ?? 'pending') === $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Return Filing Date</label>
        <input type="date" name="return_filing_date"
               value="{{ old('return_filing_date', isset($record) ? $record->return_filing_date?->format('Y-m-d') : '') }}"
               class="w-full rounded-lg border p-2">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Return Acknowledgement Number</label>
        <input name="return_acknowledgement_number"
               value="{{ old('return_acknowledgement_number', $record->return_acknowledgement_number ?? '') }}"
               class="w-full rounded-lg border p-2">
    </div>
</div>

<div>
    <label class="mb-1 block text-sm font-medium">Notes</label>
    <textarea name="notes" rows="3" class="w-full rounded-lg border p-2">{{ old('notes', $record->notes ?? '') }}</textarea>
</div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('professional-tax.show', $record) }}" class="rounded-lg border px-4 py-2">Cancel</a>
            <button class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white">Update Record</button>
        </div>
    </form>
</div>
@endsection