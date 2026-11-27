@extends('layouts.app')

@section('title', 'Notifications')
@section('page-heading', 'Notifications')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

    @include('partials.push-offer')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Reminders for hearings, appointments, tasks and overdue invoices.</p>
        @if(auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Mark all as read</button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @include('notifications.partials.item', ['notification' => $notification])
        @empty
            <div class="flex flex-col items-center px-6 py-10 text-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-teal-50 text-[#16776e]"><x-icon name="bell" :size="24" /></span>
                <p class="mt-3 font-semibold text-slate-900">No alerts yet</p>
                <p class="mt-1 max-w-sm text-sm text-slate-500">Reminders appear here before hearings and when tasks or invoices are due. Give your cases their next dates so nothing is missed.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('cases.index', ['hearing_filter' => 'no_upcoming']) }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Add next dates</a>
                    <a href="{{ route('profile.index') }}#system-preferences" class="inline-flex min-h-11 items-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reminder settings</a>
                </div>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
@endsection
