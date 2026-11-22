@extends('layouts.app')

@section('title', 'TDS follow-up history')
@section('page-heading', 'TDS follow-up history')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">

    @include('tds.partials.follow-up-navigation')

    <div>
        <h1 class="text-2xl font-bold text-slate-800">TDS Follow-up History</h1>
        <p class="mt-1 text-sm text-slate-500">
            Complete communication record with deductors.
        </p>
    </div>

    <form method="GET" action="{{ route('tds.follow-ups.history') }}"
          class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-5 md:grid-cols-5">

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Search Client / Reference</label>
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Client name or deduction reference"
                   class="w-full rounded-lg border-slate-300">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Status</label>
            <select name="status" class="w-full rounded-lg border-slate-300">
                <option value="">All statuses</option>
                <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Contact Method</label>
            <select name="method" class="w-full rounded-lg border-slate-300">
                <option value="">All methods</option>
                @foreach(['email', 'phone', 'whatsapp', 'letter', 'other'] as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>
                        {{ ucfirst($method) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">From Date</label>
            <input type="date" name="from" value="{{ request('from') }}"
                   class="w-full rounded-lg border-slate-300">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">To Date</label>
            <input type="date" name="to" value="{{ request('to') }}"
                   class="w-full rounded-lg border-slate-300">
        </div>

        <div class="flex flex-wrap gap-2 md:col-span-5">
            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white">
                Apply Filters
            </button>
            <a href="{{ route('tds.follow-ups.history') }}"
               class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-medium text-slate-700">
                Clear Filters
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Contact Date</th>
                        <th class="px-4 py-3">Deduction Reference</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Outcome</th>
                        <th class="px-4 py-3">Additional Notes</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Next Follow-up</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($followUps as $followUp)
                        <tr class="align-top">
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $followUp->contact_date?->format('d-m-Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $followUp->tdsDeduction?->deduction_reference ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if($followUp->tdsDeduction?->client)
                                    {{ $followUp->tdsDeduction->client->display_name
                                        ?: trim($followUp->tdsDeduction->client->first_name . ' ' . $followUp->tdsDeduction->client->last_name) }}
                                @else
                                    —
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ ucfirst($followUp->contact_method ?? 'other') }}
                            </td>

                            <td class="min-w-48 px-4 py-3">
                                <div class="whitespace-pre-wrap">{{ $followUp->outcome ?: '—' }}</div>
                            </td>

                            <td class="min-w-48 px-4 py-3">
                                <div class="whitespace-pre-wrap text-slate-600">{{ $followUp->notes ?: '—' }}</div>
                            </td>

                            <td class="px-4 py-3">
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-medium
                                    {{ $followUp->follow_up_status === 'completed'
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-amber-50 text-amber-700' }}">
                                    {{ ucfirst($followUp->follow_up_status) }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $followUp->next_follow_up_date?->format('d-m-Y') ?? '—' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                @if($followUp->tdsDeduction)
                                    <a href="{{ route('tds.show', $followUp->tdsDeduction) }}"
                                       class="font-medium text-indigo-600 hover:underline">
                                        View TDS
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                No follow-up records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-4">
            {{ $followUps->links() }}
        </div>
    </div>
</div>
@endsection