@extends('layouts.app')
@section('title', 'Import results')
@section('page-heading', 'Import results')
@section('content')
    <div class="mx-auto max-w-4xl space-y-4">
        <a class="inline-flex min-h-11 items-center text-sm font-semibold underline" href="{{ route('cases.import.history') }}">Import history</a>
        <x-page-header :title="$import->source_name" :subtitle="'Attempt #'.$import->id.' · '.ucfirst($import->import_status)" />
        <x-panel>
            <ul class="divide-y divide-slate-200">
                @forelse($entries as $entry)
                    <li class="flex flex-wrap items-start justify-between gap-2 py-3 text-sm">
                        <span class="min-w-0 break-words">{{ $entry['reference'] }}</span>
                        <strong>{{ $entry['outcome'] }}</strong>
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-600">No individual results recorded for this attempt.</li>
                @endforelse
            </ul>
        </x-panel>
    </div>
@endsection
