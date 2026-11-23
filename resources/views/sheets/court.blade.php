{{-- Pop-up: the court room for a room or judge chip, from the official judges & VC lists. --}}
@php
    $judges = $bench ? $bench->judgeList() : [];
    $roomLabel = $bench?->court_room ?: $room;
    $link = $bench?->vc_link ?: $vcLink;
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ $roomLabel ? (preg_match('/^(court|room)\b/i', $roomLabel) ? $roomLabel : 'Room '.$roomLabel) : 'Court' }}</h2>
<p class="mt-0.5 text-sm text-slate-600">{{ $courtName ?: $court }}</p>

<ul class="mt-4 space-y-2">
    @forelse($judges as $judgeRow)
        <li class="rounded-xl border border-slate-200 px-3 py-2">
            <p class="font-semibold text-slate-900">{{ $judgeRow['name'] }}</p>
            @if($judgeRow['designation'] ?? null)
                <p class="text-sm text-slate-600">{{ $judgeRow['designation'] }}</p>
            @endif
        </li>
    @empty
        @if($judge)
            <li class="rounded-xl border border-slate-200 px-3 py-2 font-semibold text-slate-900">{{ $judge }}</li>
        @endif
    @endforelse
</ul>

@if($link || $bench?->vc_meeting_id || $bench?->email)
    <dl class="mt-3 space-y-2 text-sm">
        @if($bench?->vc_meeting_id)
            <div class="flex items-center justify-between gap-2">
                <dt class="text-slate-600">Meeting ID</dt>
                <dd class="flex items-center gap-2 font-semibold tabular-nums text-slate-900">
                    {{ $bench->vc_meeting_id }}
                    <button type="button" data-copy="{{ preg_replace('/\D+/', '', $bench->vc_meeting_id) }}" aria-label="Copy meeting ID" class="inline-flex h-8 items-center gap-1 rounded-full border border-slate-200 px-2 text-xs font-semibold text-slate-600 hover:border-[#16776e]"><x-icon name="copy" :size="14" /><span data-copy-label>Copy</span></button>
                </dd>
            </div>
        @endif
        @if($bench?->email)
            <div class="flex items-center justify-between gap-2">
                <dt class="text-slate-600">Email</dt>
                <dd><a href="mailto:{{ $bench->email }}" class="font-semibold text-[#16776e] hover:underline">{{ $bench->email }}</a></dd>
            </div>
        @endif
    </dl>
@endif

<div class="mt-5 flex flex-wrap gap-2">
    @if($link)
        <button type="button" data-copy="{{ $link }}" class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-[#16776e]">
            <x-icon name="copy" :size="16" /><span data-copy-label>Copy VC link</span>
        </button>
        <a href="{{ $link }}" target="_blank" rel="noopener noreferrer" data-direct-share class="inline-flex min-h-11 flex-1 items-center justify-center gap-1.5 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="screen" :size="16" /> Join VC
        </a>
    @endif
</div>
@if($listUrl)
    <a href="{{ $listUrl }}" class="mt-3 block text-center text-sm font-semibold text-[#16776e] hover:underline">This court's full judges list &rarr;</a>
@elseif(! $bench)
    <p class="mt-3 text-xs text-slate-500">This court isn't in the official lists VakilNiti has (Supreme Court, Delhi High Court, Delhi district courts).</p>
@endif
