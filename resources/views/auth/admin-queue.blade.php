@extends('layouts.app')
@section('title', 'Case review queue')
@section('page-heading', 'Case review queue')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header :title="$label" :subtitle="$source" />
        <a class="inline-flex min-h-11 items-center text-sm font-semibold underline" href="{{ route('admin.review', ['finding' => $finding, 'reviewed' => $showReviewed ? null : 1]) }}">{{ $showReviewed ? 'Hide reviewed cases' : 'Include reviewed cases' }}</a>
        <div class="flex flex-wrap gap-3 text-sm">
            <a class="underline" href="{{ route('admin.review', $finding) }}">Unresolved</a>
            <a class="underline" href="{{ route('admin.review', ['finding' => $finding, 'queue' => 'mine']) }}">Assigned to me</a>
            <a class="underline" href="{{ route('admin.review', ['finding' => $finding, 'queue' => 'recurring']) }}">Recurring</a>
        </div>
        @if(session('success'))<p role="status" class="text-sm text-teal-700">{{ session('success') }}</p>@endif
        @foreach($errors->all() as $error)<p role="alert" class="text-sm text-red-700">{{ $error }}</p>@endforeach

        @forelse($cases as $case)
            <x-panel>
                <h2 class="font-semibold"><a class="inline-flex min-h-11 items-center underline" href="{{ route('admin.accounts.cases.show', [$case->user_id, $case]) }}">{{ $case->case_title }}</a></h2>
                <p class="text-sm">{{ $case->case_number ?: 'No case number' }} &middot; {{ $case->court_name ?: 'Court missing' }}</p>
                <p class="text-sm text-slate-600">Case last updated {{ $case->updated_at?->format('d M Y, g:i a') ?? 'Unknown' }}</p>
                @if($finding === 'listing-conflict')
                    <p class="text-sm">Saved {{ collect([$case->hearing_day, $case->fixed_day])->filter()->sort()->first() }} &middot; Cause list {{ $case->listed_date }}</p>
                @elseif($finding === 'court-conflict')
                    <p class="text-sm">CNR {{ $case->cnr_number }} &middot; Saved state {{ $case->state_code }}</p>
                @elseif($finding === 'duplicate')
                    <p class="text-sm">CNR {{ $case->cnr_number ?: 'Not recorded' }}</p>
                @endif
                <p class="mt-2 text-sm font-semibold">Review status {{ str($decisions[$case->id]->status ?? 'open')->replace('-', ' ')->title() }}
                    @if(($decisions[$case->id]->occurrences ?? 0) > 1)&middot; Recurring ({{ $decisions[$case->id]->occurrences }})@endif
                </p>
                @if($decisions[$case->id]->note ?? null)<p class="text-sm text-slate-600">{{ $decisions[$case->id]->note }}</p>@endif
                @if($finding === 'listing-conflict')
                    <form method="POST" action="{{ route('admin.review.notify', $case) }}">@csrf<button class="min-h-11 text-sm font-semibold underline">Send to workspace owner</button></form>
                @endif
                <form method="POST" action="{{ route('admin.review.feedback', [$finding, $case]) }}" class="mt-2 flex flex-wrap items-end gap-2">
                    @csrf
                    <label class="text-sm">Decision
                        <select name="status" required class="mt-1 block min-h-11 rounded-lg border-slate-300">
                            <option value="">Choose a decision</option>
                            @foreach(\App\Services\AdminCaseReview::STATUSES as $status => $text)<option value="{{ $status }}">{{ $text }}</option>@endforeach
                        </select>
                    </label>
                    <label class="text-sm">Assigned administrator
                        <select name="assigned_to" class="mt-1 block min-h-11 rounded-lg border-slate-300">
                            <option value="">Unassigned</option>
                            @foreach($reviewers as $person)<option value="{{ $person->id }}" @selected(($decisions[$case->id]->assigned_to ?? null) === $person->id)>{{ $person->name }}</option>@endforeach
                        </select>
                    </label>
                    <label class="min-w-0 flex-1 text-sm">Follow-up note (optional)
                        <input name="reason" maxlength="500" value="{{ $decisions[$case->id]->note ?? '' }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
                    </label>
                    <button class="min-h-11 rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Save decision</button>
                </form>
            </x-panel>
        @empty
            <x-panel>No cases match this check.</x-panel>
        @endforelse
        {{ $cases->links() }}
    </div>
@endsection
