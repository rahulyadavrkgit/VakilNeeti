@extends('layouts.app')

@section('title', 'TDS deduction')
@section('page-heading', 'TDS deduction')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">TDS Deduction Details</h1>
            <p class="text-sm text-slate-500">{{ $tdsDeduction->deduction_reference }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="rounded-xl border bg-white p-6">
        <h2 class="mb-4 font-semibold text-slate-800">Deduction Information</h2>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm text-slate-500">Reference</dt>
                <dd class="font-medium">{{ $tdsDeduction->deduction_reference }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Deduction Date</dt>
                <dd>{{ $tdsDeduction->deduction_date?->format('d-m-Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Client</dt>
                <dd>{{ $tdsDeduction->client?->display_name ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Invoice</dt>
                <dd>{{ $tdsDeduction->invoice?->invoice_number ?? 'Unavailable' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">TDS Section</dt>
                <dd>{{ $tdsDeduction->tds_section ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Rate</dt>
                <dd>{{ $tdsDeduction->deduction_rate !== null ? $tdsDeduction->deduction_rate . '%' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Deduction Amount</dt>
                <dd class="font-semibold">&#8377;{{ number_format((float) $tdsDeduction->amount, 2) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Financial Year</dt>
                <dd>{{ $tdsDeduction->financial_year ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Certificate Status</dt>
                <dd>{{ ucfirst($tdsDeduction->certificate_status) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Certificate Number</dt>
                <dd>{{ $tdsDeduction->certificate_number ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-slate-500">Certificate Date</dt>
                <dd>{{ $tdsDeduction->certificate_date?->format('d-m-Y') ?? '—' }}</dd>
            </div>
        </dl>

        @if($tdsDeduction->notes)
            <div class="mt-5 border-t pt-4">
                <p class="text-sm text-slate-500">Notes</p>
                <p>{{ $tdsDeduction->notes }}</p>
            </div>
        @endif
    </section>

    <section class="rounded-xl border bg-white p-6">
        <h2 class="font-semibold text-slate-800">Form 26AS / AIS Credit Reconciliation</h2>
        <p class="mt-1 text-sm text-slate-500">Record the result after checking your tax statement.</p>

        <div class="mt-4 rounded-lg bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Current reconciliation status</p>
            <p class="mt-1 font-semibold text-slate-800">
                @switch($tdsDeduction->credit_status ?? 'not_checked')
                    @case('matched') Matched in Form 26AS / AIS @break
                    @case('not_reflected') Not reflected @break
                    @case('mismatch') Mismatch @break
                    @default Not checked
                @endswitch
            </p>
            @if($tdsDeduction->credit_checked_at)
                <p class="mt-1 text-xs text-slate-500">
                    Checked on {{ $tdsDeduction->credit_checked_at->format('d-m-Y') }}
                </p>
            @endif
        </div>

        <form method="POST"
              action="{{ route('tds.reconciliation.update', $tdsDeduction) }}"
              class="mt-5 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-sm font-medium">Credit Reconciliation Status</label>
                <select name="credit_status" required class="w-full rounded-lg border p-2">
                    <option value="not_checked" @selected(old('credit_status', $tdsDeduction->credit_status ?? 'not_checked') === 'not_checked')>Not checked</option>
                    <option value="matched" @selected(old('credit_status', $tdsDeduction->credit_status) === 'matched')>Matched in Form 26AS / AIS</option>
                    <option value="not_reflected" @selected(old('credit_status', $tdsDeduction->credit_status) === 'not_reflected')>Not reflected</option>
                    <option value="mismatch" @selected(old('credit_status', $tdsDeduction->credit_status) === 'mismatch')>Mismatch</option>
                </select>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Date Checked</label>
                    <input type="date" name="credit_checked_at"
                           value="{{ old('credit_checked_at', $tdsDeduction->credit_checked_at?->format('Y-m-d')) }}"
                           class="w-full rounded-lg border p-2">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Reference (optional)</label>
                    <input type="text" name="credit_reference"
                           value="{{ old('credit_reference', $tdsDeduction->credit_reference) }}"
                           placeholder="Statement reference / acknowledgement"
                           class="w-full rounded-lg border p-2">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Reconciliation Notes</label>
                <textarea name="credit_notes" rows="3"
                          class="w-full rounded-lg border p-2"
                          placeholder="Record the amount reflected, discrepancy or follow-up required">{{ old('credit_notes', $tdsDeduction->credit_notes) }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Save Reconciliation
                </button>
            </div>
        </form>
    </section>
</div>

<section class="rounded-xl border bg-white p-6 mt-6">
    <h2 class="font-semibold text-slate-800">TDS Follow-up History</h2>
    <p class="mt-1 text-sm text-slate-500">
        Record communication with the deductor about the TDS certificate or missing tax credit.
    </p>

    <form method="POST"
          action="{{ route('tds.follow-ups.store', $tdsDeduction) }}"
          class="mt-5 space-y-4">
        @csrf

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Contact Date</label>
                <input type="date" name="contact_date"
                       value="{{ old('contact_date', date('Y-m-d')) }}"
                       required class="w-full rounded-lg border p-2">
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Contact Method</label>
                <select name="contact_method" required class="w-full rounded-lg border p-2">
                    <option value="email">Email</option>
                    <option value="phone">Phone</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="letter">Letter</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Entry Status</label>
                <select name="follow_up_status" required class="w-full rounded-lg border p-2">
                    <option value="completed">Contact completed</option>
                    <option value="pending">Contact pending</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Next Follow-up Date</label>
                <input type="date" name="next_follow_up_date"
                       value="{{ old('next_follow_up_date') }}"
                       class="w-full rounded-lg border p-2">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Outcome</label>
            <textarea name="outcome" rows="2"
                      class="w-full rounded-lg border p-2"
                      placeholder="Enter the response received from the deductor.">{{ old('outcome') }}</textarea>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Additional Notes</label>
            <textarea name="notes" rows="2"
                      class="w-full rounded-lg border p-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white">
                Save Follow-up
            </button>
        </div>
    </form>

    <div class="mt-7 border-t pt-5">
        <h3 class="mb-4 font-semibold text-slate-800">Previous Follow-ups</h3>

        <div class="space-y-4">
            @forelse($tdsDeduction->followUps as $followUp)
                <div class="rounded-lg border p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-medium text-slate-800">
                            {{ $followUp->contact_date?->format('d M Y') }}
                            · {{ ucfirst($followUp->contact_method) }}
                        </p>

                        <span class="rounded-full px-3 py-1 text-xs
                            {{ $followUp->follow_up_status === 'completed' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                            {{ ucfirst($followUp->follow_up_status) }}
                        </span>
                    </div>

                    @if($followUp->outcome)
                        <p class="mt-2 text-sm">{{ $followUp->outcome }}</p>
                    @endif

                    @if($followUp->notes)
                        <p class="mt-1 text-sm text-slate-500">{{ $followUp->notes }}</p>
                    @endif

                    @if($followUp->next_follow_up_date)
                        <p class="mt-2 text-xs font-medium text-indigo-700">
                            Next follow-up: {{ $followUp->next_follow_up_date->format('d M Y') }}
                        </p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No follow-up entries recorded yet.</p>
            @endforelse
        </div>
    </div>
</section>

@endsection