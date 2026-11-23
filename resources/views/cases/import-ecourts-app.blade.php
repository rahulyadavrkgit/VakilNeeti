@extends('layouts.app')

@section('title', 'Cases in your eCourts export')
@section('page-heading', 'Cases in your eCourts export')

@php
    $pending = $rows->where('case_status', 'pending');
    $existingCount = $rows->whereNotNull('existing_case_id')->count();
    $statusClass = fn (string $status) => match ($status) {
        'pending' => 'bg-teal-50 text-teal-800',
        'transferred' => 'bg-amber-50 text-amber-800',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

@section('content')
<form method="POST" action="{{ route('cases.find.ecourts-app.import') }}" id="ecourts-app-form" data-bulk-form class="mx-auto max-w-6xl space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="text-sm text-slate-600">
            <p>
                <span class="font-semibold text-slate-900">{{ $rows->count() }}</span> cases found:
                {{ $pending->count() }} pending, {{ $rows->count() - $pending->count() }} disposed or transferred{{ $existingCount ? ', '.$existingCount.' already in your cases' : '' }}.
            </p>
            <p class="mt-1">Pending cases are ticked. Tick disposed cases too if you want them in your records.</p>
        </div>
        <a href="{{ route('cases.find', ['method' => 'app']) }}" class="text-sm font-semibold text-[#16776e] hover:underline">&larr; Upload again</a>
    </div>

    <label class="flex items-start gap-2 rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-900">
        <input type="hidden" name="update_existing" value="0">
        <input type="checkbox" name="update_existing" value="1" checked class="mt-0.5 rounded border-slate-300 text-[#16776e]">
        <span>Update ticked cases that are already in your diary with the latest status, stage and next date. Hearings you typed in yourself are never changed.</span>
    </label>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="hidden grid-cols-[2.5rem_1.3fr_2fr_1.7fr_1.1fr_7rem] gap-3 border-b border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold uppercase text-slate-500 lg:grid">
            <label class="flex items-center"><input type="checkbox" data-select-all="ecourts-app-form" aria-label="Select all cases" class="rounded border-slate-300 text-[#16776e]"></label>
            <span>Case</span>
            <span>Parties</span>
            <span>Court</span>
            <span>Status</span>
            <span>Next date</span>
        </div>

        @foreach($rows as $row)
            <label class="grid cursor-pointer gap-1 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 lg:grid-cols-[2.5rem_1.3fr_2fr_1.7fr_1.1fr_7rem] lg:items-center lg:gap-3">
                <span class="flex items-center gap-2">
                    <input type="checkbox" name="selected[]" value="{{ $row['cnr'] }}" data-select-item @checked($row['case_status'] === 'pending')
                           class="rounded border-slate-300 text-[#16776e]">
                    <span class="text-xs font-semibold text-slate-500 lg:hidden">{{ $row['is_high_court'] ? 'High Court' : 'District court' }}</span>
                </span>
                <span>
                    <span class="block font-mono text-sm font-semibold text-slate-900">{{ $row['case_number'] }}</span>
                    <span class="block font-mono text-xs text-slate-500">{{ $row['cnr'] }}</span>
                    @if($row['existing_case_id'])
                        <span class="mt-0.5 inline-block rounded bg-slate-100 px-1.5 text-xs font-semibold text-slate-600">Already added</span>
                    @endif
                </span>
                <span class="text-sm text-slate-800">{{ $row['case_title'] }}</span>
                <span class="text-sm text-slate-600">
                    {{ $row['court_name'] }}
                    @if($row['court_complex'])<span class="block text-xs text-slate-400">{{ $row['court_complex'] }}</span>@endif
                </span>
                <span class="text-sm">
                    <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClass($row['case_status']) }}">{{ $row['disposal'] ? \Illuminate\Support\Str::title(strtolower($row['disposal'])) : 'Pending' }}</span>
                    @if($row['stage'])<span class="block text-xs text-slate-500">{{ $row['stage'] }}</span>@endif
                </span>
                <span class="text-sm {{ $row['next_date'] && $row['next_date'] >= today()->toDateString() ? 'font-semibold text-[#16776e]' : 'text-slate-500' }}">
                    {{ $row['next_date'] ? \Illuminate\Support\Carbon::parse($row['next_date'])->format('d M Y') : ($row['disposed_on'] ? 'Decided '.\Illuminate\Support\Carbon::parse($row['disposed_on'])->format('d M Y') : '—') }}
                </span>
            </label>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3">
        <a href="{{ route('cases.find', ['method' => 'app']) }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" data-bulk-submit data-bulk-label="Import selected cases" class="rounded-lg bg-[#16776e] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#125f58] disabled:opacity-50">Import selected cases ({{ $pending->count() }})</button>
    </div>
</form>
@endsection
