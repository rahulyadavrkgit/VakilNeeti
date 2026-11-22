@extends('layouts.app')

@section('title', 'Search')
@section('page-heading', 'Search')

@section('content')
<div class="mx-auto max-w-4xl space-y-5">
    <form method="GET" action="{{ route('search') }}" role="search" class="flex gap-2">
        <label class="flex flex-1 items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 shadow-sm focus-within:border-[#16776e]">
            <x-icon name="search" class="text-slate-400" />
            <span class="sr-only">Search cases, clients and invoices</span>
            <input type="search" name="q" value="{{ $query }}" autofocus maxlength="100" placeholder="Case title, number, CNR, client, mobile or invoice"
                   class="min-h-12 w-full border-0 bg-transparent px-0 focus:ring-0">
        </label>
        <button type="submit" class="rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">Search</button>
    </form>

    @if(mb_strlen($query) < 2)
        <p class="text-sm text-slate-500">Type at least 2 letters to search your cases, clients and invoices.</p>
    @elseif($total === 0)
        <p class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Nothing matches "{{ $query }}". Try a case number, CNR, client name or mobile number.</p>
    @else
        @foreach([
            'cases' => ['Cases', 'briefcase'],
            'clients' => ['Clients', 'people'],
            'invoices' => ['Invoices', 'receipt'],
        ] as $key => [$label, $icon])
            @if($results[$key]->isNotEmpty())
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h2 class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 text-sm font-bold uppercase tracking-wide text-slate-500">
                        <x-icon :name="$icon" :size="16" /> {{ $label }} <span class="font-normal">({{ $results[$key]->count() }})</span>
                    </h2>
                    <ul class="divide-y divide-slate-100">
                        @foreach($results[$key] as $item)
                            <li>
                                @if($key === 'cases')
                                    <a href="{{ route('cases.show', $item) }}" class="block px-4 py-3 hover:bg-slate-50">
                                        <span class="block font-semibold text-slate-900">{{ $item->case_title }}</span>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->case_number, $item->cnr_number, $item->court_name])->filter()->implode(' · ') }}</span>
                                    </a>
                                @elseif($key === 'clients')
                                    <a href="{{ route('clients.show', $item) }}" class="block px-4 py-3 hover:bg-slate-50">
                                        <span class="block font-semibold text-slate-900">{{ $item->displayName() }}</span>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->mobile, $item->email])->filter()->implode(' · ') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('invoices.show', $item) }}" class="block px-4 py-3 hover:bg-slate-50">
                                        <span class="block font-semibold text-slate-900">{{ $item->invoice_number }}</span>
                                        <span class="block text-xs text-slate-500">{{ collect([$item->client?->displayName(), ucfirst(str_replace('_', ' ', (string) $item->status))])->filter()->implode(' · ') }}</span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    @endif
</div>
@endsection
