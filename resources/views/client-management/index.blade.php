@extends('layouts.app')

@section('title', 'Client management')
@section('page-heading', 'Client management')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Client Management System</h1>
            <p class="mt-1 text-sm text-slate-500">Manage client relationships, cases, activities and billing.</p>
        </div>

        <a href="{{ route('clients.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
            + Add New Client
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Total Clients</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $totalClients }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Active Clients</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $activeClients }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Inactive Clients</p>
            <p class="mt-2 text-3xl font-bold text-slate-700">{{ $inactiveClients }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Outstanding Amount</p>
            <p class="mt-2 text-2xl font-bold text-amber-700">
                @money($outstandingAmount)
            </p>
        </div>

    </div>

    <div class="rounded-xl border border-slate-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <div>
                <h2 class="font-semibold text-slate-800">Client Directory</h2>
                <p class="mt-1 text-sm text-slate-500">Open a client profile to access related records.</p>
            </div>

            <a href="{{ route('clients.index') }}"
               class="text-sm font-medium text-blue-600 hover:underline">
                View All Clients
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Client</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Contact</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Cases</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Invoices</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Tasks</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($clients as $client)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800">{{ $client->display_name }}</div>
                                <div class="text-xs text-slate-500">{{ $client->client_code }}</div>
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-600">
                                {{ $client->mobile ?: $client->email ?: '—' }}
                            </td>

                            <td class="px-5 py-4 text-sm text-slate-700">{{ $client->case_parties_count }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $client->invoices_count }}</td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ $client->tasks_count }}</td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-2 py-1 text-xs {{ $client->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('clients.show', $client) }}"
                                   class="text-sm font-medium text-blue-600 hover:underline">
                                    Open Profile
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">
                                No clients have been added yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 p-5">
            {{ $clients->links() }}
        </div>
    </div>

</div>
@endsection