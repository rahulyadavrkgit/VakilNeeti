@extends('layouts.app')

@section('title', 'eCourts integration')
@section('page-heading', 'eCourts integration')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold">eCourts Integration</h1>
        <p class="text-gray-600 mt-1">
            Official District and Subordinate Court Services
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm text-gray-500">Source status</p>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                {{ $source->is_enabled ? 'Registered' : 'Disabled' }}
            </h2>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm text-gray-500">Integration mode</p>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                Portal-assisted
            </h2>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-sm text-gray-500">Imported source records</p>
            <h2 class="mt-2 text-lg font-semibold text-slate-900">
                {{ number_format($recordCount) }}
            </h2>
        </div>

    </div>

    <div class="bg-white rounded-lg shadow p-5 mb-6">
        <h2 class="text-lg font-semibold mb-3">Official source</h2>

        <p class="font-medium">{{ $source->provider_name }}</p>

        <p class="text-sm text-gray-600 mt-2">
            {{ $source->attribution_text }}
        </p>

        <p class="text-sm text-gray-600 mt-2">
            Last successful synchronisation:
            {{ $source->last_synced_at
                ? $source->last_synced_at->format('d M Y, h:i A')
                : 'No synchronisation recorded' }}
        </p>

        <a
            href="{{ $source->base_url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-block mt-4 px-4 py-2 rounded bg-blue-600 text-white"
        >
            Open Official eCourts Portal
        </a>

        <a
            href="{{ route('courts.ecourts-services') }}"
            class="inline-block mt-4 ml-2 px-4 py-2 rounded bg-gray-700 text-white"
        >
            Search Official Court Records
        </a>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 mb-6">
        <h2 class="font-semibold text-amber-900">
            Integration capability and limitations
        </h2>

        <ul class="list-disc ml-5 mt-3 text-sm text-amber-900 space-y-2">
            <li>
                This source is registered as an official government portal.
            </li>
            <li>
                Live API access has not been configured or verified.
            </li>
            <li>
                Searches requiring CAPTCHA must be completed through the official portal.
            </li>
            <li>
                Imported information must retain its official source and retrieval history.
            </li>
        </ul>
    </div>

    <div class="bg-white rounded-lg shadow p-5">
        <h2 class="text-lg font-semibold mb-4">
            Recent synchronisation history
        </h2>

        @if ($syncRuns->isEmpty())
            <p class="text-gray-500">
                No synchronisation attempts have been recorded yet.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="py-3 pr-3">Date</th>
                            <th class="py-3 pr-3">Entity</th>
                            <th class="py-3 pr-3">Mode</th>
                            <th class="py-3 pr-3">Status</th>
                            <th class="py-3 pr-3">Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($syncRuns as $run)
                            <tr class="border-b">
                                <td class="py-3 pr-3">
                                    {{ $run->created_at?->format('d M Y, h:i A') }}
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $run->entity_type ?? '—' }}
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $run->sync_mode }}
                                </td>
                                <td class="py-3 pr-3">
                                    {{ ucfirst($run->status) }}
                                </td>
                                <td class="py-3 pr-3">
                                    {{ $run->records_received }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<div class="mt-5">
    <a href="{{ route('courts.ecourts-services') }}"
       class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-700 text-white text-sm font-medium hover:bg-blue-800">
        Open eCourts Case Search
    </a>
</div>
@endsection