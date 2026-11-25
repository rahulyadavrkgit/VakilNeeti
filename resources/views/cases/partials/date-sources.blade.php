@php
    $official = $causeListings->first()?->courtDocument;
    $disagrees = $savedDate && $official && ! $savedDate['date']->isSameDay($official->listing_date);
@endphp
<x-panel>
    <h2 class="font-bold">Upcoming hearing dates</h2>
    <dl class="mt-2 grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-semibold">{{ $savedDate ? $savedDate['hearing']->dateSourceLabel() : 'Saved date' }}</dt>
            <dd>{{ $savedDate ? $savedDate['date']->format('d M Y') : 'No upcoming date saved' }}</dd>
            @if($savedDate && $savedDate['hearing']->updated_at)<dd class="text-slate-600">Updated {{ $savedDate['hearing']->updated_at->format('d M Y, g:i a') }}</dd>@endif
        </div>
        <div>
            <dt class="font-semibold">From the cause list</dt>
            <dd>{{ $official?->listing_date?->format('d M Y') ?? 'No upcoming match available' }}</dd>
            @if($official?->source_url)<dd><a class="inline-flex min-h-11 items-center font-semibold text-teal-800 underline" href="{{ $official->source_url }}" target="_blank" rel="noopener">Open source list</a></dd>@endif
            @if($official?->fetched_at)<dd class="text-slate-600">Checked {{ $official->fetched_at->format('d M Y, g:i a') }}</dd>@endif
        </div>
    </dl>
    @if($disagrees)
        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">These dates differ. Check the latest court listing before changing your saved date.</p>
    @endif
    <x-case-report :case-id="$case->id" page="case-details" />
    @error('message')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
</x-panel>
