@extends('layouts.app')
@section('title', 'Case diagnostics')
@section('page-heading', 'Case diagnostics')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <a href="{{ route('admin.accounts.show', $account) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-teal-700 underline">{{ $account->name }}'s account</a>
        <x-page-header :title="$case->case_title" subtitle="Administrator case review" />
        <x-panel>
            <dl class="grid gap-3 break-words text-sm sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'Case number' => $case->case_number, 'CNR' => $case->cnr_number,
                    'Court' => $case->court_name, 'Court complex' => $case->court_complex, 'Court code' => $case->court_code,
                    'Matter number' => $case->matter_id, 'District' => $case->district, 'State' => $case->state_code,
                    'File number' => $case->file_number, 'Diary number' => $case->diary_number, 'Case type' => $case->case_type,
                    'Status' => ucfirst($case->case_status ?? 'Unknown'), 'Stage' => $case->stage, 'Source' => $case->source_type,
                    'Last updated' => $case->updated_at?->format('d M Y, g:i a'),
                ] as $label => $value)
                    <div><dt class="font-semibold">{{ $label }}</dt><dd>{{ $value ?: 'Not recorded' }}</dd></div>
                @endforeach
            </dl>
        </x-panel>
        <x-panel>
            <h2 class="font-bold">Hearing history</h2>
            <div class="my-2 divide-y divide-slate-200">
                @forelse($hearings as $hearing)
                    <div class="space-y-1 py-3 text-sm">
                        <h3 class="font-semibold">{{ $hearing->hearing_date?->format('d M Y') ?? 'Date not recorded' }}</h3>
                        <p>{{ collect([$hearing->hearing_time, $hearing->court_room, $hearing->judge_name, $hearing->hearing_stage])->filter()->implode(' · ') }}</p>
                        <p>Next date {{ $hearing->next_hearing_date?->format('d M Y') ?? 'not recorded' }}</p>
                        @if($hearing->proceedings)<p class="whitespace-pre-line">{{ $hearing->proceedings }}</p>@endif
                        @if($hearing->next_action)<p><strong>Next action</strong> {{ $hearing->next_action }}</p>@endif
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-600">No hearings recorded.</p>
                @endforelse
            </div>
            {{ $hearings->appends(request()->except('hearings'))->links() }}
        </x-panel>
        <x-panel>
            <h2 class="font-bold">Cause-list matches</h2>
            @forelse($listings as $listing)
                <p class="my-2 text-sm">{{ $listing->courtDocument?->listing_date?->format('d M Y') ?? 'Unknown date' }} &middot; {{ $listing->courtDocument?->document_label }} &middot; Room {{ $listing->court_room ?: 'unknown' }} &middot; Item {{ $listing->item_number ?: 'unknown' }}</p>
            @empty
                <p class="my-2 text-sm text-slate-600">No matching listings.</p>
            @endforelse
            {{ $listings->appends(request()->except('listings'))->links() }}
        </x-panel>
        <x-panel>
            <h2 class="font-bold">Document inventory</h2>
            @forelse($documents as $document)
                <p class="my-2 break-words text-sm">{{ $document->original_name }} &middot; {{ $document->created_at?->format('d M Y') }} &middot; {{ number_format($document->size / 1024, 1) }} KB</p>
            @empty
                <p class="my-2 text-sm text-slate-600">No documents uploaded.</p>
            @endforelse
            {{ $documents->appends(request()->except('documents'))->links() }}
        </x-panel>
    </div>
@endsection
