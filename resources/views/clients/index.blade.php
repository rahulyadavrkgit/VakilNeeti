@extends('layouts.app')

@section('title', 'Clients')
@section('page-heading', 'Clients')

@section('content')
@php
    $tabs = ['all' => 'All', 'people' => 'People', 'companies' => 'Companies'];
    $keep = fn (array $extra = []) => array_filter(array_merge(request()->only('search', 'status', 'type', 'view'), $extra), fn ($value) => $value !== null && $value !== '' && $value !== 'all');
@endphp
<div class="space-y-6">
    <x-page-header title="Clients" subtitle="People and companies you act for, linked to their cases.">
        <a href="{{ route('clients.from-cases') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add from my cases</a>
        <a href="{{ route('clients.create', ['type' => 'company']) }}" data-sheet="{{ route('sheets.client', ['type' => 'company']) }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">+ Company</a>
        <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">+ Person</a>
    </x-page-header>

    <nav aria-label="Client type" class="-mx-1 flex gap-1 overflow-x-auto border-b border-slate-200 pb-px">
        @foreach($tabs as $key => $label)
            <a href="{{ route('clients.index', $keep(['type' => $key, 'page' => null])) }}" @if($type === $key) aria-current="page" @endif
               class="whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-semibold {{ $type === $key ? 'border-[#238b80] text-[#16776e]' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                {{ $label }} <span class="text-slate-400">({{ $counts[$key] }})</span>
            </a>
        @endforeach
    </nav>

    <div class="flex flex-wrap items-end justify-between gap-3">
        <form method="GET" action="{{ route('clients.index') }}" class="flex flex-wrap items-end gap-2">
            @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
            @if($view === 'grid')<input type="hidden" name="view" value="grid">@endif
            <input type="search" name="search" value="{{ $search }}" placeholder="Search name, mobile or email" aria-label="Search clients"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm sm:w-64">
            <select name="status" data-autosubmit aria-label="Status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
            <button type="submit" class="rounded-lg bg-[#142235] px-4 py-2 text-sm font-semibold text-white">Search</button>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" role="group" aria-label="Layout">
                <a href="{{ route('clients.index', $keep(['view' => null])) }}" @if($view === 'list') aria-current="true" @endif aria-label="List view" title="List view"
                   class="flex h-11 w-11 items-center justify-center {{ $view === 'list' ? 'bg-teal-50 text-[#16776e]' : 'text-slate-500 hover:bg-slate-50' }}">
                    <x-icon name="list" :size="18" />
                </a>
                <a href="{{ route('clients.index', $keep(['view' => 'grid'])) }}" @if($view === 'grid') aria-current="true" @endif aria-label="Grid view" title="Grid view"
                   class="flex h-11 w-11 items-center justify-center border-l border-slate-200 {{ $view === 'grid' ? 'bg-teal-50 text-[#16776e]' : 'text-slate-500 hover:bg-slate-50' }}">
                    <x-icon name="dashboard" :size="18" />
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if($errors->has('ids'))
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first('ids') }}</div>
    @endif

    <form id="clients-bulk" method="POST" action="{{ route('clients.bulk-destroy') }}" data-bulk-form data-confirm="Delete the selected clients? They move to deleted clients.">
        @csrf
        @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
        @if($view === 'grid')<input type="hidden" name="view" value="grid">@endif
    </form>

    @if($clients->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <p class="font-semibold text-slate-700">No clients found.</p>
            <p class="mt-1 text-sm text-slate-500"><a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="font-semibold text-[#16776e] hover:underline">Add your first client</a>.</p>
        </div>
    @else
        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" data-select-all form="clients-bulk" class="rounded border-slate-300 text-[#238b80]">
                Select all on this page
            </label>
            <button type="submit" form="clients-bulk" data-bulk-submit disabled
                    class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40">Delete selected</button>
        </div>

        @if($view === 'grid')
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($clients as $client)
                    <article class="relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <input type="checkbox" name="ids[]" value="{{ $client->id }}" form="clients-bulk" data-select-item aria-label="Select {{ $client->displayName() }}"
                               class="absolute right-3 top-3 rounded border-slate-300 text-[#238b80]">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $client->isCompany() ? 'bg-indigo-50 text-indigo-700' : 'bg-[#e8f4f2] text-[#16776e]' }}">{{ $client->isCompany() ? 'Company' : 'Person' }}</span>
                        <a href="{{ route('clients.show', $client) }}" data-sheet="{{ route('sheets.client-card', $client) }}" class="mt-2 block pr-6 font-semibold text-slate-900 hover:text-[#16776e]">{{ $client->displayName() }}</a>
                        @if($client->company)<p class="text-xs text-slate-500">at {{ $client->company->displayName() }}</p>@endif
                        @if($client->isCompany())<p class="text-xs text-slate-500">{{ $client->people_count }} {{ \Illuminate\Support\Str::plural('person', $client->people_count) }}</p>@endif
                        <p class="mt-2 text-sm text-slate-600">{{ collect([$client->mobile, $client->email])->filter()->implode(' · ') ?: 'No contact details' }}</p>
                        <p class="text-xs text-slate-500">{{ collect([$client->locality, $client->city])->filter()->implode(', ') }}</p>
                    </article>
                @endforeach
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="vn-stack-table w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="w-10 p-3"><span class="sr-only">Select</span></th>
                            <th class="p-3">Name</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Mobile</th>
                            <th class="p-3">Email</th>
                            <th class="p-3">City</th>
                            <th class="p-3">Status</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($clients as $client)
                            <tr class="hover:bg-slate-50">
                                <td class="p-3" data-stack="corner"><input type="checkbox" name="ids[]" value="{{ $client->id }}" form="clients-bulk" data-select-item aria-label="Select {{ $client->displayName() }}" class="rounded border-slate-300 text-[#238b80]"></td>
                                <td class="p-3" data-stack="title">
                                    <a href="{{ route('clients.show', $client) }}" data-sheet="{{ route('sheets.client-card', $client) }}" class="font-semibold text-slate-900 hover:text-[#16776e]">{{ $client->displayName() }}</a>
                                    @if($client->company)<span class="block text-xs text-slate-500">at {{ $client->company->displayName() }}</span>@endif
                                    @if($client->isCompany() && $client->people_count)<span class="block text-xs text-slate-500">{{ $client->people_count }} {{ \Illuminate\Support\Str::plural('person', $client->people_count) }}</span>@endif
                                </td>
                                <td class="p-3" data-label="Type">{{ $client->isCompany() ? 'Company' : 'Person' }}</td>
                                <td class="p-3" data-label="Mobile">{{ $client->mobile ?: '—' }}</td>
                                <td class="p-3 break-all" data-label="Email" data-stack="full">{{ $client->email ?: '—' }}</td>
                                <td class="p-3" data-label="City">{{ $client->city ?: '—' }}</td>
                                <td class="p-3" data-label="Status"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $client->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($client->status) }}</span></td>
                                <td class="p-3 text-right whitespace-nowrap" data-stack="full">
                                    <a href="{{ route('clients.show', $client) }}" class="font-semibold text-[#16776e] hover:underline">View</a>
                                    <a href="{{ route('clients.edit', $client) }}" class="ml-3 text-slate-600 hover:underline">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div>{{ $clients->links() }}</div>
    @endif
</div>
@endsection
