@extends('layouts.app')

@section('content')
@include('tds.partials.follow-up-navigation')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Upcoming TDS Follow-ups</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pending follow-ups scheduled for future dates.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('tds.follow-ups.due') }}"
               class="rounded-lg border px-4 py-2 text-sm font-medium">
                Due Follow-ups
            </a>
            <a href="{{ route('tds.index') }}"
               class="rounded-lg border px-4 py-2 text-sm font-medium">
                TDS Register
            </a>
        </div>
    </div>

    <div class="rounded-xl border bg-white p-4">
        <p class="text-sm text-slate-500">Future pending follow-ups</p>
        <p class="mt-1 text-3xl font-bold text-slate-800">{{ $followUps->total() }}</p>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">Scheduled Date</th>
                    <th class="px-4 py-3 text-left">Client</th>
                    <th class="px-4 py-3 text-left">Invoice</th>
                    <th class="px-4 py-3 text-left">TDS Reference</th>
                    <th class="px-4 py-3 text-left">Last Contact</th>
                    <th class="px-4 py-3 text-left">Outcome</th>
                    <th class="px-4 py-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($followUps as $followUp)
                    @php
                        $deduction = $followUp->tdsDeduction;
                    @endphp

                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-indigo-700">
                            {{ $followUp->next_follow_up_date?->format('d M Y') }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->client?->display_name ?? 'Ã¢â‚¬â€' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->invoice?->invoice_number ?? 'Ã¢â‚¬â€' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $deduction?->deduction_reference ?? 'Ã¢â‚¬â€' }}
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

                        <td class="px-4 py-3">
                            @if($deduction)
                                <div class="flex flex-col items-start gap-2">
                                    <a href="{{ route('tds.show', $deduction) }}"
                                       class="font-medium text-indigo-600 hover:underline">
                                        View TDS
                                    </a>

                                    <form method="POST"
                                          action="{{ route('tds.follow-ups.complete', $followUp) }}"
                                          onsubmit="return confirm('Mark this follow-up as completed?')">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit"
                                                class="font-medium text-green-700 hover:underline">
                                            Mark Completed
                                        </button>
                                    </form>
                                </div>
                            @else
                                Ã¢â‚¬â€
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                            No upcoming TDS follow-ups scheduled.
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