@extends('layouts.app')
@section('title', 'Client Activities')
@section('page-heading', 'Client Activities')
@section('content')
<div class="space-y-6">

        <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold">Client Activity</h2>
                <p class="mt-2 text-slate-500">
                    {{ $client->display_name ?: trim($client->first_name.' '.$client->last_name) }}
                    | Client ID: {{ $client->id }}
                </p>
            </div>

            <a href="{{ route('clients.activities.create', $client) }}"
               class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                + Add Activity
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 rounded-lg bg-green-100 p-4 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-slate-600">
                        <tr>
                            <th class="p-4">Date</th>
                            <th class="p-4">Type</th>
                            <th class="p-4">Subject / Details</th>
                            <th class="p-4">Follow-up</th>
                            <th class="p-4">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($activities as $activity)
                            <tr class="border-t border-slate-100">
                                <td class="p-4 whitespace-nowrap">
                                    {{ $activity->activity_at->format('d M Y, h:i A') }}
                                </td>

                                <td class="p-4">
                                    {{ ucwords(str_replace('_', ' ', $activity->activity_type)) }}
                                </td>

                                <td class="p-4">
                                    <p class="font-semibold">{{ $activity->subject }}</p>
                                    <p class="mt-1 text-slate-500">{{ $activity->description }}</p>
                                </td>

                                <td class="p-4 whitespace-nowrap">
                                    {{ $activity->follow_up_at?->format('d M Y, h:i A') ?? 'â€”' }}
                                </td>

                                <td class="p-4">
                                    {{ ucfirst($activity->status) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center text-slate-500">
                                    No activities recorded for this client.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t p-4">
                {{ $activities->links() }}
            </div>
        </div>

        <a href="{{ route('clients.show', $client) }}"
           class="mt-6 inline-block text-blue-600 hover:underline">
            â† Back to Client Profile
        </a>

    </div>
@endsection
