@extends('layouts.app')

@section('title', 'Calendar events to import')
@section('page-heading', 'Calendar events to import')

@php
    $matched = $events->whereNotNull('matched_case_id')->count();
    $alreadySaved = $events->where('already_saved', true)->count();
    $select = 'w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-sm';
@endphp

@section('content')
<form method="POST" action="{{ route('cases.find.calendar.import') }}" class="mx-auto max-w-6xl space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="text-sm text-slate-600">
            <p>
                <span class="font-semibold text-slate-900">{{ $events->count() }}</span> {{ $likelyOnly ? 'court-related ' : '' }}events between
                {{ \Illuminate\Support\Carbon::parse($range[0])->format('d M Y') }} and {{ \Illuminate\Support\Carbon::parse($range[1])->format('d M Y') }}{{ $matched ? ', '.$matched.' matched to your cases' : '' }}.
            </p>
            <p class="mt-1">Each event becomes a hearing. Choose its case, make a new case (events with the same title share one case), or skip it.</p>
            @if($alreadySaved)
                <p class="mt-1 font-semibold text-slate-700">{{ $alreadySaved }} {{ \Illuminate\Support\Str::plural('event', $alreadySaved) }} already in your diary {{ $alreadySaved === 1 ? 'is' : 'are' }} set to Skip, so nothing is added twice.</p>
            @endif
        </div>
        <a href="{{ route('cases.find', ['method' => 'calendar']) }}" class="text-sm font-semibold text-[#16776e] hover:underline">&larr; Upload again</a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse($events as $index => $event)
            <div class="grid gap-2 border-b border-slate-100 px-4 py-3 last:border-0 md:grid-cols-[7rem_minmax(0,1fr)_16rem] md:items-center md:gap-4 {{ $event['matched_case_id'] ? 'bg-teal-50/40' : '' }}">
                <div class="text-sm">
                    <span class="block font-semibold text-slate-900">{{ \Illuminate\Support\Carbon::parse($event['date'])->format('d M Y') }}</span>
                    <span class="text-xs text-slate-500">{{ $event['time'] ?? 'All day' }}</span>
                </div>
                <div class="min-w-0 text-sm">
                    <p class="font-semibold text-slate-900">
                        {{ $event['title'] }}
                        @if($event['already_saved'] ?? false)
                            <span class="ml-1 whitespace-nowrap rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">Already in your diary</span>
                        @endif
                    </p>
                    @if($event['location'])<p class="truncate text-xs text-slate-500">{{ $event['location'] }}</p>@endif
                    @if($event['description'])<p class="truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($event['description'], 140) }}</p>@endif
                </div>
                <label class="text-sm">
                    <span class="sr-only">What to do with {{ $event['title'] }}</span>
                    <select name="actions[{{ $index }}]" class="{{ $select }}">
                        <option value="skip" @selected($event['default_action'] === 'skip')>Skip</option>
                        <option value="new" @selected($event['default_action'] === 'new')>New case: "{{ \Illuminate\Support\Str::limit($event['title'], 30) }}"</option>
                        @if($cases->isNotEmpty())
                            <optgroup label="Hearing for one of your cases">
                                @foreach($cases as $case)
                                    <option value="case:{{ $case->id }}" @selected($event['default_action'] === 'case:'.$case->id)>{{ \Illuminate\Support\Str::limit($case->case_title, 40) }}{{ $case->case_number ? ' · '.$case->case_number : '' }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                </label>
            </div>
        @empty
            <p class="p-6 text-sm text-slate-500">
                No events in this period{{ $likelyOnly ? ' look like court work' : '' }}.
                <a href="{{ route('cases.find', ['method' => 'calendar']) }}" class="font-semibold text-[#16776e] hover:underline">Try a wider date range{{ $likelyOnly ? ' or show all events' : '' }}</a>.
            </p>
        @endforelse
    </div>

    @if($events->isNotEmpty())
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ route('cases.find', ['method' => 'calendar']) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58]">Import hearings</button>
        </div>
    @endif
</form>
@endsection
