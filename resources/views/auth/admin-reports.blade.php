@extends('layouts.app')
@section('title', 'Case problem reports')
@section('page-heading', 'Case problem reports')
@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header title="Case problem reports" />
        @if(session('success'))<p role="status" class="text-sm text-teal-700">{{ session('success') }}</p>@endif
        @forelse($reports as $report)
            <x-panel>
                <h2 class="font-semibold"><a class="inline-flex min-h-11 items-center underline" href="{{ route('admin.accounts.cases.show', [$report->user_id, $report->case_id]) }}">{{ $report->case_title }}</a></h2>
                <p class="text-sm text-slate-600">{{ $report->name }} &middot; {{ str($report->page)->replace('-', ' ')->title() }} &middot; {{ $report->created_at }}</p>
                <p class="my-2 whitespace-pre-line break-words text-sm">{{ $report->message }}</p>
                <form method="POST" action="{{ route('admin.reports.resolve', $report->id) }}">@csrf
                    <button class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-semibold">Mark resolved</button>
                </form>
            </x-panel>
        @empty
            <x-panel>No open reports.</x-panel>
        @endforelse
        {{ $reports->links() }}
    </div>
@endsection
