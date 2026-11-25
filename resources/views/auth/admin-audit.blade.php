@extends('layouts.app')
@section('title', 'Admin audit history')
@section('page-heading', 'Admin audit history')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header title="Audit history" />
        <x-panel class="divide-y divide-slate-200 p-4">
            @forelse($entries as $entry)
                <article class="space-y-1 py-3 text-sm">
                    <h2 class="font-semibold">{{ ucfirst($entry->action) }}</h2>
                    <p>{{ $entry->actor_name ?? 'Account #'.$entry->actor_id }} &middot; {{ $entry->created_at }}</p>
                    @if($entry->account_id)<p>Account {{ $entry->account_name ?? '#'.$entry->account_id }} @if($entry->case_id)&middot; Case #{{ $entry->case_id }}@endif</p>@endif
                    @if($entry->reason)<p class="break-words">Reason {{ $entry->reason }}</p>@endif
                </article>
            @empty
                <p class="py-2 text-sm text-slate-600">No admin activity recorded.</p>
            @endforelse
        </x-panel>
        {{ $entries->links() }}
    </div>
@endsection
