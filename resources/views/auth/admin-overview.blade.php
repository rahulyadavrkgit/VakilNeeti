@extends('layouts.app')
@section('title', 'Admin overview')
@section('page-heading', 'Admin overview')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header title="Admin overview" />
        <x-panel>
            <h2 class="font-bold">Operations in the last 24 hours</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach(['imports' => ['Failed imports', $imports], 'syncs' => ['Failed or partial court syncs', $syncs], 'errors' => ['Site errors', $errorCount]] as $kind => [$label, $count])
                    <a class="rounded-lg border border-slate-200 p-3 underline underline-offset-2" href="{{ route('admin.operations', $kind) }}">
                        <strong class="block text-xl">{{ $count }}</strong>{{ $label }}
                    </a>
                @endforeach
            </div>
        </x-panel>
        <x-panel>
            <h2 class="font-bold">Case review queue</h2>
            <a class="inline-flex min-h-11 items-center font-semibold underline" href="{{ route('admin.reports') }}">Open problem reports ({{ $reports }})</a>
            <p class="my-2 text-sm text-slate-600">Priority reflects the number of open findings and prior review feedback. Confirmed findings raise priority and false positives lower it.</p>
            <ul class="divide-y divide-slate-200">
                @foreach($findings as $finding => $details)
                    <li class="py-2">
                        <a class="inline-flex min-h-11 items-center font-semibold underline underline-offset-2" href="{{ route('admin.review', $finding) }}">{{ $details['label'] }} ({{ $details['count'] }})</a>
                        <p class="text-sm text-slate-600">{{ $details['source'] }} &middot; {{ $details['confirmed'] }} confirmed &middot; {{ $details['dismissed'] }} false positives</p>
                    </li>
                @endforeach
            </ul>
        </x-panel>
    </div>
@endsection
