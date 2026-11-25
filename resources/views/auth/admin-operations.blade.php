@extends('layouts.app')
@section('title', 'Admin operations')
@section('page-heading', 'Admin operations')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header :title="match($kind) { 'imports' => 'Failed imports', 'syncs' => 'Court sync failures', default => 'Site errors' }" subtitle="Last 24 hours" />
        @if(session('success'))<p role="status" class="text-sm text-teal-700">{{ session('success') }}</p>@endif
        <x-panel class="divide-y divide-slate-200 p-4">
            @forelse($rows as $key => $row)
                <div class="break-words py-3 text-sm">
                    @if($kind === 'errors')
                        <strong>{{ $row }} occurrences</strong><p>{{ $key }}</p>
                    @elseif($kind === 'imports')
                        <strong>{{ $row->source_name }}</strong>
                        <p>Import #{{ $row->id }} &middot; {{ $row->import_status }} &middot; {{ $row->created_at }}</p>
                        @if($row->parsed_data['reason'] ?? null)
                            <p>{{ $row->parsed_data['reason'] }}</p>
                        @elseif($row->import_status === 'partial')
                            <p>Selected entries unavailable for saving {{ $row->parsed_data['counts']['failed'] ?? 'Unknown' }}</p>
                        @endif
                        @if($lastRetry = $retries->get($row->id)?->first())
                            <p>Latest retry #{{ $lastRetry->id }} &middot; {{ ucfirst($lastRetry->import_status) }} &middot; {{ $lastRetry->created_at }}</p>
                        @endif
                        @if($row->retryMethod() && $row->user_id)
                            <form method="POST" action="{{ route('admin.operations.retry', $row) }}">@csrf
                                <button class="min-h-11 text-sm font-semibold underline">Request corrected retry</button>
                            </form>
                        @endif
                        @if($row->user_id)<a class="inline-flex min-h-11 items-center underline" href="{{ route('admin.accounts.show', $row->user_id) }}">Review account</a>@endif
                    @else
                        <strong>{{ $row->provider_key }} &middot; {{ $row->entity_type }}</strong>
                        <p>Run #{{ $row->id }} &middot; {{ $row->status }} &middot; {{ $row->started_at }}</p>
                    @endif
                </div>
            @empty
                <p class="py-2 text-sm text-slate-600">No matching records in the last 24 hours.</p>
            @endforelse
        </x-panel>
        @if($kind !== 'errors'){{ $rows->links() }}@endif
    </div>
@endsection
