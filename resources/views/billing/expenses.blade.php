@extends('layouts.app')

@section('title', 'Expenses')
@section('page-heading', 'Expenses')

@section('content')
@php $field = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2'; @endphp
<div class="space-y-6">

    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-lg font-semibold text-slate-900">Add expense</h2>
        <form method="POST" action="{{ route('billing.expenses.store') }}" enctype="multipart/form-data" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-[2fr_2fr_1fr_1fr_1fr_1.5fr_auto] lg:items-end">
            @csrf
            <label class="text-sm font-medium text-slate-700">Case
                <select name="case_id" required class="{{ $field }}">
                    <option value="">Select case</option>
                    @foreach($cases as $caseOption)
                        <option value="{{ $caseOption->id }}" @selected((string) old('case_id') === (string) $caseOption->id)>{{ $caseOption->case_title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">Description
                <input name="description" required maxlength="500" value="{{ old('description') }}" placeholder="e.g. Court fee stamp" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Date
                <input type="date" name="expense_date" required value="{{ old('expense_date', today()->format('Y-m-d')) }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Amount (@currency)
                <input type="number" name="amount" min="0.01" step="0.01" required value="{{ old('amount') }}" class="{{ $field }}">
            </label>
            <label class="text-sm font-medium text-slate-700">Paid by
                <select name="payment_method" class="{{ $field }}">
                    <option value="">—</option>
                    @foreach($methods as $method)
                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium text-slate-700">Receipt <span class="font-normal text-slate-400">(PDF/JPG/PNG)</span>
                <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full text-sm file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2">
            </label>
            <button type="submit" class="rounded-lg bg-[#238b80] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16776e]">Add</button>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 px-5 py-4">
            <form method="GET" action="{{ route('billing.expenses') }}" class="flex flex-wrap items-end gap-2 text-sm">
                <label class="font-medium text-slate-700">Case
                    <select name="case_id" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2">
                        <option value="">All cases</option>
                        @foreach($cases as $caseOption)
                            <option value="{{ $caseOption->id }}" @selected((string) request('case_id') === (string) $caseOption->id)>{{ $caseOption->case_title }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="font-medium text-slate-700">From <input type="date" name="from" value="{{ request('from') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"></label>
                <label class="font-medium text-slate-700">To <input type="date" name="to" value="{{ request('to') }}" class="mt-1 block rounded-lg border border-slate-300 px-3 py-2"></label>
                <button type="submit" class="rounded-lg bg-[#142235] px-4 py-2 font-semibold text-white">Filter</button>
            </form>
            <div class="flex items-center gap-3 text-sm">
                <span class="font-semibold text-slate-700">Total: @money($total)</span>
                <a href="{{ route('billing.expenses.export', request()->query()) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-semibold text-slate-700 hover:bg-slate-50">Export CSV</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="p-3">Date</th><th class="p-3">Case</th><th class="p-3">Description</th><th class="p-3">Paid by</th><th class="p-3 text-right">Amount</th><th class="p-3"></th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $expense)
                        <tr>
                            <td class="p-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d M Y') }}</td>
                            <td class="p-3"><a href="{{ route('cases.show', $expense->case_id) }}#case-expenses" class="text-[#16776e] hover:underline">{{ $expense->legalCase?->case_title }}</a></td>
                            <td class="p-3">{{ $expense->description }}</td>
                            <td class="p-3">{{ $expense->payment_method ? ucfirst(str_replace('_', ' ', $expense->payment_method)) : '—' }}</td>
                            <td class="p-3 text-right">@money($expense->amount)</td>
                            <td class="p-3">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    @if($expense->receipt_path)<a href="{{ route('billing.expenses.receipt', $expense) }}" class="text-xs font-semibold text-[#16776e] hover:underline">Receipt</a>@endif
                                    @if($expense->invoice_id)<a href="{{ route('invoices.show', $expense->invoice_id) }}" class="rounded-full bg-[#e8f4f2] px-2 py-0.5 text-xs font-semibold text-[#16776e]">Billed</a>@endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">No expenses yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200 p-4">{{ $expenses->links() }}</div>
    </section>
</div>
@endsection
