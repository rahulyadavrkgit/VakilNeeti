@extends('layouts.app')
@section('title', 'My Case Diary')
@section('page-heading', 'My Case Diary')

@section('content')

@include('cases.partials.management-nav')
<div class="space-y-6">

    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-end gap-2">
        <a href="{{ route('cases.find') }}"
           class="rounded-xl border border-[#8fc9c0] bg-[#e8f4f2] px-4 py-2.5 text-sm font-semibold text-[#16776e] hover:bg-[#d9efeb]">Find my cases</a>
        <a href="{{ route('cases.calendar') }}"
           class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Calendar</a>
        <a href="{{ route('cases.export', request()->query()) }}"
           class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Export CSV</a>
        <a href="{{ route('cases.create') }}"
           class="rounded-xl border border-[#8fc9c0] bg-[#e8f4f2] px-4 py-2.5 text-sm font-semibold text-[#16776e] hover:bg-[#d9efeb]">Import from court</a>
        <a href="{{ route('cases.manual') }}"
           class="rounded-xl bg-[#238b80] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#16776e]">+ Add case</a>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="mb-4 text-lg font-semibold">Find a Saved Case</h3>

        <form method="GET" action="{{ route('cases.index') }}"
              class="grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="md:col-span-2">
                <label for="search" class="mb-2 block text-sm font-medium">
                    Case Number / CNR / Party-related Case Title / Court
                </label>
                <input id="search" type="search" name="search"
                       value="{{ request('search') }}"
                       placeholder="Enter case number, CNR or court name"
                       class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
                <label for="case_status" class="mb-2 block text-sm font-medium">
                    Case Status
                </label>
                <select id="case_status" name="case_status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3">
                    <option value="">All Statuses</option>
                    @foreach(\App\Models\LegalCase::STATUSES as $status)
                        <option value="{{ $status }}"
                            {{ request('case_status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="hearing_filter" class="mb-2 block text-sm font-medium">
                    Hearing Date
                </label>
                <select id="hearing_filter" name="hearing_filter"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3">
                    <option value="all" {{ ($hearingFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Cases</option>
                    <option value="today" {{ ($hearingFilter ?? '') === 'today' ? 'selected' : '' }}>Today's Hearings</option>
                    <option value="tomorrow" {{ ($hearingFilter ?? '') === 'tomorrow' ? 'selected' : '' }}>Tomorrow's Hearings</option>
                    <option value="next7" {{ ($hearingFilter ?? '') === 'next7' ? 'selected' : '' }}>Next 7 Days</option>
                    <option value="next30" {{ ($hearingFilter ?? '') === 'next30' ? 'selected' : '' }}>Next 30 Days</option>
                    <option value="past" {{ ($hearingFilter ?? '') === 'past' ? 'selected' : '' }}>Past Dates Requiring Review</option>
                    <option value="no_upcoming" {{ ($hearingFilter ?? '') === 'no_upcoming' ? 'selected' : '' }}>No Upcoming Hearing</option>
                </select>
            </div>

            <div>
                <label for="tag" class="mb-2 block text-sm font-medium">Tag</label>
                <select id="tag" name="tag" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3">
                    <option value="">All tags</option>
                    @foreach($tags as $tagOption)
                        <option value="{{ $tagOption->id }}" @selected((string) request('tag') === (string) $tagOption->id)>{{ $tagOption->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-wrap gap-2 md:col-span-3">
                <button type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-700">
                    Search Cases
                </button>
                <a href="{{ route('cases.index') }}"
                   class="rounded-lg border border-slate-300 px-5 py-3 text-sm hover:bg-slate-50">
                    Reset
                </a>
            </div>
        </form>

    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5">
            <h3 class="text-lg font-semibold">My Saved Cases</h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $cases->total() }} records available
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-600">
                    <tr>
                        <th class="p-4">Case Information</th>
                        <th class="p-4">Court</th>
                        <th class="p-4">Parties</th>
                        <th class="p-4">Hearings</th>
                        <th class="p-4">Next Hearing Date</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Details</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($cases as $case)

                        @php
                            $hearingRecords = $case->hearings
                                ->filter(fn($hearing) => !empty($hearing->next_hearing_date));

                            $nextHearing = $hearingRecords
                                ->filter(fn($hearing) =>
                                    \Carbon\Carbon::parse($hearing->next_hearing_date)->toDateString()
                                    >= now()->toDateString()
                                )
                                ->sortBy(fn($hearing) =>
                                    \Carbon\Carbon::parse($hearing->next_hearing_date)->toDateString()
                                )
                                ->first();

                            $lastPastHearing = $hearingRecords
                                ->filter(fn($hearing) =>
                                    \Carbon\Carbon::parse($hearing->next_hearing_date)->toDateString()
                                    < now()->toDateString()
                                )
                                ->sortByDesc(fn($hearing) =>
                                    \Carbon\Carbon::parse($hearing->next_hearing_date)->toDateString()
                                )
                                ->first();
                        @endphp

                        <tr class="hover:bg-slate-50">
                            <td class="p-4">
                                <div class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900">
                                    {{ $case->case_title }}
                                    <x-case-side :side="$case->appearing_for" :parties="$case->appearing_for_parties" />
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    Case No: {{ $case->case_number ?: 'Not available' }}
                                </div>
                                <div class="text-xs text-slate-500">
                                    CNR: {{ $case->cnr_number ?: 'Not available' }}
                                </div>
                                @if($case->file_number)
                                    <div class="text-xs text-slate-500">File No: {{ $case->file_number }}</div>
                                @endif
                                @if($case->tags->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1">
                                        @foreach($case->tags as $caseTag)
                                            <a href="{{ route('cases.index', ['tag' => $caseTag->id]) }}" class="rounded-full bg-[#e8f4f2] px-2 py-0.5 text-xs font-medium text-[#16776e] hover:bg-[#d9efeb]">{{ $caseTag->name }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <td class="p-4">
                                {{ $case->court_name ?: 'Not available' }}
                            </td>

                            <td class="p-4">
                                {{ $case->parties->count() }}
                            </td>

                            <td class="p-4">
                                {{ $case->hearings_count }}
                            </td>

                            <td class="p-4">
                                @if($nextHearing)
                                    @php
                                        $hearingDate = \Carbon\Carbon::parse($nextHearing->next_hearing_date);
                                    @endphp

                                    <div class="font-semibold text-slate-900">
                                        {{ $hearingDate->format('d M Y') }}
                                    </div>

                                    @if($hearingDate->isToday())
                                        <span class="mt-1 inline-block rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                                            Today
                                        </span>
                                    @elseif($hearingDate->isTomorrow())
                                        <span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                            Tomorrow
                                        </span>
                                    @else
                                        <span class="mt-1 inline-block rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                            Upcoming
                                        </span>
                                    @endif

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $nextHearing->hearing_time ?: 'Time not available' }}
                                    </div>
                                @elseif($lastPastHearing)
                                    <div class="font-medium text-slate-600">
                                        {{ \Carbon\Carbon::parse($lastPastHearing->next_hearing_date)->format('d M Y') }}
                                    </div>
                                    <span class="mt-1 inline-block rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                        Past date — review
                                    </span>
                                @else
                                    <span class="text-xs text-slate-500">
                                        Not scheduled
                                    </span>
                                @endif
                            </td>

                            <td class="p-4">
                                @php
                                    $statusClass = match ($case->case_status) {
                                        'pending' => 'bg-amber-100 text-amber-800',
                                        'disposed', 'closed' => 'bg-rose-100 text-rose-700',
                                        'transferred', 'withdrawn' => 'bg-slate-200 text-slate-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($case->case_status ?: 'unknown') }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex items-center gap-3 whitespace-nowrap">
                                    <a href="{{ route('cases.show', $case) }}" class="font-semibold text-[#16776e] hover:underline">View</a>
                                    <a href="{{ route('cases.edit', $case) }}" class="text-slate-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('cases.destroy', $case) }}" data-confirm="Delete this case from your diary?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-700 hover:underline">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="p-10 text-center">
                                <div class="font-semibold text-slate-700">
                                    No case records available
                                </div>
                                <p class="mt-2 text-sm text-slate-500">
                                    <a href="{{ route('cases.manual') }}" class="font-semibold text-[#16776e] hover:underline">Add a case</a>
                                    or <a href="{{ route('cases.create') }}" class="font-semibold text-[#16776e] hover:underline">import one from the court portal</a>.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-5">
            {{ $cases->links() }}
        </div>
    </section>
</div>
@endsection