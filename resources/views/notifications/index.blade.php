@extends('layouts.app')

@section('title', 'Notifications')
@section('page-heading', 'Notifications')

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    @if(session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('success') }}</div>
    @endif

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
            <p class="p-6 text-sm text-slate-500">No notifications yet. Reminders appear here before hearings and when tasks or invoices are due.</p>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
@endsection
