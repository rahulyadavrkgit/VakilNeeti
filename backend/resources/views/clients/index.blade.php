@extends('layouts.app')
@section('title', 'Clients')
@section('page-heading', 'Clients')
@section('content')
<div class="space-y-6">

        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold">Clients</h2>
                <p class="mt-2 text-slate-600">
                    Manage your law firm's clients.
                </p>
            </div>

            <a href="{{ route('clients.create') }}"
               class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
                + Add New Client
            </a>
        </div>

        @if(session('success'))
            <div class="mt-6 rounded-lg bg-green-100 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <!-- Client Search and Filters -->
        <form method="GET" action="{{ route('clients.index') }}"
              class="rounded-xl bg-white p-5 shadow-sm">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div class="md:col-span-2">
                    <label for="search" class="mb-2 block text-sm font-semibold text-slate-700">
                        Search Clients
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search by name, mobile, email or organisation..."
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-semibold text-slate-700">
                        Client Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                    >
                        <option value="">All Clients</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">
                    Search
                </button>

                <a href="{{ route('clients.index') }}"
                   class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">
                    Clear Filters
                </a>
            </div>

        </form>

        <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
            <p>
                Showing
                <strong>{{ $clients->firstItem() ?? 0 }}–{{ $clients->lastItem() ?? 0 }}</strong>
                of
                <strong>{{ $clients->total() }}</strong>
                clients
            </p>
            @if($search !== '' || $status !== '')
                <p class="font-medium text-blue-600">Filters applied</p>
            @endif
        </div>

        <div class="mt-8 overflow-x-auto rounded-xl bg-white shadow-sm">

            <table class="w-full text-left">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="p-4">Client ID</th>
                        <th class="p-4">Client Name</th>
                        <th class="p-4">Mobile</th>
                        <th class="p-4">Email</th>
                        <th class="p-4">City</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($clients as $client)
                        <tr class="border-t border-slate-200">

                            <td class="p-4 font-semibold">
                                {{ $client->id }}
                            </td>

                            <td class="p-4 font-medium">
                                {{ $client->display_name ?: trim($client->first_name.' '.$client->middle_name.' '.$client->last_name) }}
                            </td>

                            <td class="p-4">
                                {{ $client->mobile ?? 'â€”' }}
                            </td>

                            <td class="p-4">
                                {{ $client->email ?? 'â€”' }}
                            </td>

                            <td class="p-4">
                                {{ $client->city ?? 'â€”' }}
                            </td>

                            <td class="p-4">
                                <span class="rounded-full px-3 py-1 text-sm
                                    {{ $client->status === 'active'
                                        ? 'bg-green-100 text-green-700'
                                        : 'bg-slate-200 text-slate-700' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex gap-3">
                                    <a href="{{ route('clients.show', $client) }}"
                                       class="font-semibold text-blue-600 hover:underline">
                                        View
                                    </a>

                                    <a href="{{ route('clients.edit', $client) }}"
                                       class="font-semibold text-indigo-600 hover:underline">
                                        Edit
                                    </a>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                @if($search !== '' || $status !== '')
                                    No clients match your search or selected filters.
                                @else
                                    No clients added yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>

        <div class="mt-6">
            {{ $clients->links() }}
        </div>

    </div>
@endsection
