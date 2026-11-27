@props(['bench', 'todaysList' => true])

@php
    $emails = preg_split('/\s*[\/,;]\s*/', (string) $bench->email, -1, PREG_SPLIT_NO_EMPTY);
    $copyButton = 'inline-flex size-8 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:border-teal-500 [&.is-copied]:border-teal-500 [&.is-copied]:text-teal-800';
    // Supreme Court rooms: the court's own cause list for this room, dated the day the link is opened.
    $causeListUrl = $todaysList && $bench->court_key === 'supreme-court' ? \App\Services\SupremeCourtBenches::linkFor('Supreme Court of India', $bench->court_room) : null;
@endphp

@if($bench->vc_meeting_id || $emails || $bench->vc_link || $causeListUrl)
    <div {{ $attributes->class(['mt-3 space-y-2']) }}>
        @if($bench->vc_meeting_id || $emails)
            <dl class="grid gap-px overflow-hidden rounded-lg border border-slate-200 bg-slate-200 {{ $bench->vc_meeting_id && $emails ? 'min-[380px]:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)]' : '' }}">
                @if($bench->vc_meeting_id)
                    <div class="min-w-0 bg-slate-50 px-2.5 py-2">
                        <dt class="text-xs font-medium text-slate-600">VC meeting ID</dt>
                        <dd class="mt-0.5 flex flex-wrap items-center gap-x-1.5 text-sm font-semibold tabular-nums text-slate-900">
                            <span class="wrap-anywhere">{{ $bench->vc_meeting_id }}</span>
                            <button type="button" data-copy="{{ preg_replace('/\D+/', '', $bench->vc_meeting_id) }}" title="Copy meeting ID" aria-label="Copy meeting ID"
                                    class="{{ $copyButton }}">
                                <x-icon name="copy" :size="15" />
                                <span data-copy-label class="sr-only" aria-live="polite">Copy meeting ID</span>
                            </button>
                        </dd>
                    </div>
                @endif
                @if($emails)
                    <div class="min-w-0 bg-slate-50 px-2.5 py-2">
                        <dt class="text-xs font-medium text-slate-600">Court email</dt>
                        <dd class="mt-0.5 space-y-0.5 text-sm font-medium">
                            @foreach($emails as $email)
                                <div class="flex items-center gap-1.5">
                                    <a class="vn-link min-w-0 flex-1 wrap-anywhere" href="mailto:{{ $email }}" data-direct-share>{{ $email }}</a>
                                    <button type="button" data-copy="{{ $email }}" title="Copy email" aria-label="Copy email {{ $email }}" class="{{ $copyButton }}">
                                        <x-icon name="copy" :size="15" />
                                        <span data-copy-label class="sr-only" aria-live="polite">Copy email</span>
                                    </button>
                                </div>
                            @endforeach
                        </dd>
                    </div>
                @endif
            </dl>
        @endif
        @if($bench->vc_link || $causeListUrl)
            <div class="flex flex-wrap items-center gap-1.5">
                @if($bench->vc_link)
                    <a href="{{ $bench->vc_link }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       class="vn-primary-button inline-flex min-h-8 items-center justify-center gap-1.5 rounded-md px-2.5 text-xs font-semibold">
                        <x-icon name="screen" :size="15" /> Join VC
                    </a>
                    <button type="button" data-copy="{{ $bench->vc_link }}" title="Copy VC link" aria-label="Copy VC link" class="{{ $copyButton }}">
                        <x-icon name="copy" :size="15" />
                        <span data-copy-label class="sr-only" aria-live="polite">Copy VC link</span>
                    </button>
                @endif
                @if($causeListUrl)
                    <a href="{{ $causeListUrl }}" target="_blank" rel="noopener noreferrer" data-direct-share
                       class="vn-link inline-flex min-h-8 items-center gap-1 px-1 text-xs font-semibold">Today's cause list</a>
                @endif
            </div>
        @endif
    </div>
@endif
