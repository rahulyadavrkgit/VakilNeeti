@extends('layouts.app')

@section('title', 'TDS follow-ups due')
@section('page-heading', 'TDS follow-ups due')

@section('content')
@include('tds.partials.follow-up-navigation')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">TDS Follow-ups Due</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pending TDS certificate and deductor communication follow-ups.
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
            {{ $errors->first() }}
        </div>
    @endif

    <div class="rounded-xl border bg-white p-4">
        <p class="text-sm text-slate-500">Pending follow-ups due on or before {{ \Carbon\Carbon::parse($today)->format('d M Y') }}</p>
        <p class="mt-1 text-3xl font-bold text-slate-800">{{ $followUps->total() }}</p>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">Due Date</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">TDS Reference</th>
                    <th class="px-4 py-3 text-left">Last Contact</th>
                    <th class="px-4 py-3 text-left">Outcome</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($followUps as $followUp)
                    @php
                        $dueDate = $followUp->next_follow_up_date ?? $followUp->contact_date;
                        $isOverdue = $dueDate && $dueDate->toDateString() < $today;
                        $deduction = $followUp->tdsDeduction;
                    @endphp

                    <tr>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="{{ $isOverdue ? 'font-semibold text-red-700' : 'font-medium text-amber-700' }}">
                                {{ $dueDate?->format('d M Y') }}
                            </span>
                            <div class="mt-1 text-xs">
                                {{ $isOverdue ? 'Overdue' : 'Due' }}
                            </div>
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->client?->display_name ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->invoice?->invoice_number ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->deduction_reference ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $followUp->contact_date?->format('d M Y') }}
                            <div class="text-xs text-slate-500">
                                {{ ucfirst($followUp->contact_method) }}
                            </div>
                        </td>

                        <td class="max-w-xs px-4 py-3">
                            {{ $followUp->outcome ?: 'No outcome recorded' }}
                        </td>

                        <td class="whitespace-nowrap px-4 py-3">
                            @if($deduction)
                                <a href="{{ route('tds.show', $deduction) }}"
                                   class="text-indigo-600 hover:underline">
                                    View TDS
                                </a>
                            @endif

                            <form method="POST"
                                  action="{{ route('tds.follow-ups.complete', $followUp) }}"
                                  class="mt-2">
                                @csrf
                                @method('PUT')
                                <button type="submit"
                                       
                                        class="text-sm font-medium text-green-700 hover:underline">
                                    Mark Completed
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                            No pending follow-ups are currently due.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $followUps->links() }}
    </div>
</div>
@endsection