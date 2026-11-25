@extends('layouts.app')

@section('title', 'Billing')
@section('page-heading', 'Billing')

@section('content')
<div class="space-y-6">
    <x-page-header title="Billing" subtitle="What you have billed, received and are still owed." />

    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('billing.index') }}" class="flex flex-wrap items-end gap-2">
            <label class="text-sm font-medium text-slate-700">
                Period
                <select name="period" data-autosubmit class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2">
                    @foreach(\App\Http\Controllers\BillingController::PERIODS as $value => $label)
                        <option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if($period === 'custom')
                <label class="text-sm font-medium text-slate-700">From
                    <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2">
                </label>
                <label class="text-sm font-medium text-slate-700">To
                    <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2">
                </label>
                <button type="submit" class="rounded-lg bg-[#142235] px-4 py-2 text-sm font-semibold text-white">Apply</button>
            @endif
        </form>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('payments.create') }}" data-sheet="{{ route('sheets.payment-pick') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Record payment</a>
            <a href="{{ route('invoices.create') }}" class="rounded-xl bg-[#238b80] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#16776e]">+ New invoice</a>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach([
            ['Invoices raised', number_format($stats['invoices_raised']), 'text-slate-900'],
            ['Invoiced', \App\Enums\Currency::current()->format($stats['invoice_amount']), 'text-slate-900'],
            ['Received', \App\Enums\Currency::current()->format($stats['payment_received']), 'text-[#16776e]'],
            ['Expenses', \App\Enums\Currency::current()->format($stats['total_expenses']), 'text-slate-900'],
            ['Payment due', \App\Enums\Currency::current()->format($stats['payment_due']), 'text-amber-700'],
            ['Overdue', \App\Enums\Currency::current()->format($stats['payment_overdue']), 'text-rose-700'],
        ] as [$label, $value, $colour])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-xl font-bold {{ $colour }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>
    <p class="-mt-3 text-xs text-slate-500">Due and overdue are current balances; the other figures are for the selected period.</p>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-200 px-5 py-4 text-lg font-semibold text-slate-900">By client</h2>
        <div class="overflow-x-auto">
            <table class="vn-stack-table w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="p-3">Client</th><th class="p-3 text-right">Cases</th><th class="p-3 text-right">Invoiced</th><th class="p-3 text-right">Received</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clientRows as $row)
                        <tr>
                            <td class="p-3"><a href="{{ route('clients.show', $row['client']) }}" class="font-medium text-slate-900 hover:text-[#16776e]">{{ $row['client']->displayName() }}</a></td>
                            <td class="p-3 text-right">{{ $row['cases'] }}</td>
                            <td class="p-3 text-right">@money($row['invoiced'])</td>
                            <td class="p-3 text-right">@money($row['received'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">No billing activity in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
