@extends('layouts.app')

@section('title', 'Clients')
@section('page-heading', 'Clients')

@section('content')
@php
    $tabs = ['all' => 'All', 'people' => 'People', 'companies' => 'Companies'];
    $keep = fn (array $extra = []) => array_filter(array_merge(request()->only('search', 'status', 'type', 'view'), $extra), fn ($value) => $value !== null && $value !== '' && $value !== 'all');
@endphp
<div class="mx-auto max-w-5xl space-y-3">
    {{-- Header: how many, one Add button, and the rest in one "⋯" menu (as on My cases). --}}
    @php $menuItem = 'flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-slate-700 hover:bg-slate-50'; @endphp
    <x-page-header title="Clients" :subtitle="$counts['all'].' '.\Illuminate\Support\Str::plural('client', $counts['all']).' · people and companies you act for'">
        <div class="flex items-center gap-2">
            <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" data-shortcut="n" aria-keyshortcuts="N"
               class="inline-flex min-h-10 items-center gap-1.5 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">
                <x-icon name="plus" :size="18" /> Add client
            </a>
            <details class="vn-profile-menu relative">
                <summary aria-label="More actions" title="More actions"
                         class="inline-flex min-h-10 min-w-10 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-300 bg-white px-2.5 text-lg font-bold leading-none text-slate-700 hover:bg-slate-50">&middot;&middot;&middot;</summary>
                <div class="absolute right-0 z-30 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                    <a href="{{ route('clients.create', ['type' => 'company']) }}" data-sheet="{{ route('sheets.client', ['type' => 'company']) }}" class="{{ $menuItem }}"><x-icon name="building" :size="16" class="text-slate-400" /> Add a company</a>
                    <a href="{{ route('clients.from-cases') }}" class="{{ $menuItem }}"><x-icon name="briefcase" :size="16" class="text-slate-400" /> Add from my cases</a>
                </div>
            </details>
        </div>
    </x-page-header>

    <x-panel class="p-3 sm:p-4">
        <form method="GET" action="{{ route('clients.index') }}" role="search" class="flex flex-col gap-2 sm:flex-row">
            @if($type !== 'all')<input type="hidden" name="type" value="{{ $type }}">@endif
            @if($view === 'grid')<input type="hidden" name="view" value="grid">@endif
            <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white pl-3 pr-1 focus-within:border-[#16776e] focus-within:ring-2 focus-within:ring-[#16776e]/15">
                <x-icon name="search" :size="18" class="shrink-0 text-slate-400" />
                <span class="sr-only">Search clients</span>
                <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="Name, mobile or email"
                       class="min-h-11 w-full border-0 bg-transparent px-0 text-sm focus:ring-0">
                <button type="submit" class="inline-flex min-h-9 shrink-0 items-center rounded-lg bg-[#142235] px-3.5 text-sm font-semibold text-white hover:bg-[#20334a]">Search</button>
            </label>
            <label class="sr-only" for="client-status">Status</label>
            <select id="client-status" name="status" data-autosubmit class="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm sm:w-40">
                <option value="">Any status</option>
                <option value="active" @selected($status === 'active')>Active</option>
                <option value="inactive" @selected($status === 'inactive')>Inactive</option>
            </select>
        </form>
    </x-panel>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <nav aria-label="Client type" class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white">
            @foreach($tabs as $key => $label)
                <a href="{{ route('clients.index', $keep(['type' => $key, 'page' => null])) }}" @if($type === $key) aria-current="page" @endif
                   class="inline-flex min-h-10 items-center gap-1.5 px-3.5 text-sm font-semibold {{ $loop->first ? '' : 'border-l border-slate-200' }} {{ $type === $key ? 'bg-[#142235] text-white' : 'text-slate-700 hover:bg-slate-50' }}">
                    {{ $label }} <span class="rounded-full px-1.5 text-xs tabular-nums {{ $type === $key ? 'bg-white/20' : 'bg-slate-100 text-slate-600' }}">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

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
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-[#16776e]"><x-icon name="people" :size="24" /></span>
            <p class="mt-3 font-semibold text-slate-900">{{ $search !== '' || $status || $type !== 'all' ? 'No clients match.' : 'No clients yet' }}</p>
            <p class="mt-1 text-sm text-slate-500">Your cases already name the parties: pick the ones you act for and they become clients.</p>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <a href="{{ route('clients.from-cases') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Add from my cases</a>
                <a href="{{ route('clients.create') }}" data-sheet="{{ route('sheets.client') }}" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Add a client</a>
            </div>
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
                        <p class="text-xs text-slate-500">{{ collect([$client->locality, $client->city, $client->cases_count ? $client->cases_count.' '.\Illuminate\Support\Str::plural('case', $client->cases_count) : null])->filter()->implode(' · ') }}</p>
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
                            <th class="p-3">Cases</th>
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
                                <td class="p-3 tabular-nums" data-label="Cases">{{ $client->cases_count ?: '—' }}</td>
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
