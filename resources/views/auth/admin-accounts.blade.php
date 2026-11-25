@extends('layouts.app')
@section('title', 'Account administration')
@section('page-heading', 'Account administration')

@section('content')
    <div class="mx-auto max-w-5xl space-y-4">
        <x-admin-nav />
        <x-page-header title="Account administration" />
        @if(session('success'))<p role="status" class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-900">{{ session('success') }}</p>@endif
        <dl class="grid grid-cols-3 gap-2 rounded-xl border border-slate-200 bg-white p-4">
            <div><dt>Accounts</dt><dd class="text-xl font-bold">{{ $accountCount }}</dd></div>
            <div><dt>Cases</dt><dd class="text-xl font-bold">{{ $caseCount }}</dd></div>
            <div><dt>Documents</dt><dd class="text-xl font-bold">{{ $documentCount }}</dd></div>
        </dl>
        <form method="GET" role="search" class="flex flex-wrap items-end gap-2">
            <label class="min-w-0 flex-1 text-sm font-medium">Find an account
                <input type="search" name="search" value="{{ $search }}" maxlength="120" placeholder="Name or email" class="mt-1 block min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
            </label>
            <button class="min-h-11 rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Search</button>
        </form>
        <x-panel class="divide-y divide-slate-200">
            @forelse($accounts as $account)
                <article class="flex flex-wrap justify-between gap-2 p-4">
                    <div class="min-w-0 break-words">
                        <h2 class="font-semibold"><a class="inline-flex min-h-11 items-center text-teal-700 underline underline-offset-2" href="{{ route('admin.accounts.show', $account) }}">{{ $account->name }}</a> @if($account->is_admin)<small>(Admin)</small>@endif</h2>
                        <p class="text-sm text-slate-600">{{ $account->email }}</p>
                    </div>
                    <p class="text-sm text-slate-600">{{ $account->cases_count }} cases &middot; {{ $account->documents_count }} documents &middot; Joined {{ $account->created_at->format('d M Y') }}</p>
                </article>
            @empty
                <p class="p-4 text-sm text-slate-600">No accounts match your search.</p>
            @endforelse
        </x-panel>
        {{ $accounts->links() }}
    </div>
@endsection
