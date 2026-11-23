@php
    $kind = $notification->data['kind'] ?? 'other';
    $dotClass = ['hearing' => 'bg-teal-600', 'appointment' => 'bg-sky-500', 'task' => 'bg-amber-500', 'invoice' => 'bg-red-500'][$kind] ?? 'bg-slate-400';
@endphp
<a href="{{ route('notifications.open', $notification->id) }}"
   class="flex gap-3 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50 {{ $notification->read_at ? '' : 'bg-teal-50/50' }}">
    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
    <span class="min-w-0 flex-1">
        <span class="block text-sm {{ $notification->read_at ? 'font-medium text-slate-700' : 'font-semibold text-slate-900' }}">{{ $notification->data['title'] ?? 'Notification' }}</span>
        @if(! empty($notification->data['body']))
            <span class="block truncate text-xs text-slate-500">{{ $notification->data['body'] }}</span>
        @endif
        <span class="block text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
    </span>
    @unless($notification->read_at)
        <span class="sr-only">Unread</span>
    @endunless
</a>
