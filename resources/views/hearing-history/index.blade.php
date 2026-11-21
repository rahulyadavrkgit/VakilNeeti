@extends('layouts.app')

@section('title', 'Hearing History')
@section('page-heading', 'Hearing History')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Hearing History</h2>
            <p class="text-sm text-slate-500">
                Chronological record of hearings linked to your cases.
            </p>
        </div>

        <a href="{{ route('cases.calendar') }}"
           class="rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">
            Open Calendar
        </a>
    </div>

    <form method="GET" action="{{ route('hearings.history') }}"
          class="flex flex-wrap gap-3 rounded-xl border bg-white p-4">

        <input type="text"
               name="search"
               value="{{ request('search') }}"
               placeholder="Search case number, CNR, title or judge"
               class="min-w-[240px] flex-1 rounded-lg border border-slate-300 p-3 text-sm">

        <button type="submit"
                class="rounded-lg bg-blue-600 px-5 py-3 text-sm font-semibold text-white">
            Search
        </button>

        <a href="{{ route('hearings.history') }}"
           class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">
            Reset
        </a>
    </form>

    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="p-4">Hearing Date</th>
                    <th class="p-4">Case Details</th>
                    <th class="p-4">Court / Judge</th>
                    <th class="p-4">Hearing Stage</th>
                    <th class="p-4">Proceedings</th>
                    <th class="p-4">Next Hearing</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($hearings as $hearing)
                    <tr class="border-t align-top hover:bg-slate-50">

                        <td class="whitespace-nowrap p-4 font-medium text-slate-800">
                            {{ $hearing->hearing_date
                                ? \Carbon\Carbon::parse($hearing->hearing_date)->format('d-m-Y')
                                : 'Not recorded' }}

                            @if($hearing->hearing_time)
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $hearing->hearing_time }}
                                </p>
                            @endif
                        </td>

                        <td class="p-4">
                            <p class="font-semibold text-slate-900">
                                {{ $hearing->case_title ?: 'Case details unavailable' }}
                            </p>

                            @if($hearing->case_number)
                                <p class="mt-1 text-xs text-slate-600">
                                    Case No.: {{ $hearing->case_number }}
                                </p>
                            @endif

                            @if($hearing->cnr_number)
                                <p class="mt-1 text-xs text-slate-500">
                                    CNR: {{ $hearing->cnr_number }}
                                </p>
                            @endif
                        </td>

                        <td class="p-4">
                            <p>{{ $hearing->court_name ?: 'Not recorded' }}</p>

                            @if($hearing->judge_name)
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $hearing->judge_name }}
                                </p>
                            @endif

                            @if($hearing->court_room)
                                <p class="mt-1 text-xs text-slate-500">
                                    Court Room: {{ $hearing->court_room }}
                                </p>
                            @endif
                        </td>

                        <td class="p-4">
                            {{ $hearing->hearing_stage ?: 'Not recorded' }}
                        </td>

                        <td class="max-w-xs whitespace-normal p-4 text-slate-600">
                            {{ $hearing->proceedings ?: 'No proceedings recorded' }}

                            @if($hearing->next_action)
                                <p class="mt-2 text-xs text-slate-500">
                                    Next action: {{ $hearing->next_action }}
                                </p>
                            @endif
                        </td>

                        <td class="whitespace-nowrap p-4">
                            {{ $hearing->next_hearing_date
                                ? \Carbon\Carbon::parse($hearing->next_hearing_date)->format('d-m-Y')
                                : 'Not scheduled' }}
                        </td>

                        <td class="whitespace-nowrap p-4">
                            @if($hearing->case_id)
                                <a href="{{ route('cases.show', $hearing->case_id) }}"
                                   class="font-semibold text-blue-700 hover:underline">
                                    View Case
                                </a>
                            @else
                                <span class="text-slate-400">Unavailable</span>
                            @endif
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-slate-500">
                            No hearing records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $hearings->links() }}
    </div>

</div>
@endsection