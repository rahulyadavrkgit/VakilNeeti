@php
    $official = $causeListings->first()?->courtDocument;
    $disagrees = $savedDate && $official && ! $savedDate['date']->isSameDay($official->listing_date);
@endphp
<details class="rounded-lg border border-slate-200 px-3 py-2" @if($disagrees || $errors->has('message')) open @endif>
    <summary class="cursor-pointer text-sm font-semibold">Date sources and support</summary>
    @if($disagrees)
        <p class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">These dates differ. Saved date {{ $savedDate['date']->format('d M Y') }}. Cause list {{ $official->listing_date->format('d M Y') }}. Check the latest court listing before changing your saved date.</p>
    @endif
    <ul class="mt-2 space-y-1 text-sm text-slate-600">
        @if($savedDate)
            <li>{{ $savedDate['hearing']->dateSourceLabel() }} @if($savedDate['hearing']->updated_at)&middot; Updated {{ $savedDate['hearing']->updated_at->format('d M Y, g:i a') }}@endif</li>
        @endif
        @if($official)
            <li>From the cause list @if($official->fetched_at)&middot; Checked {{ $official->fetched_at->format('d M Y, g:i a') }}@endif</li>
        @else
            <li>No upcoming cause-list match available.</li>
        @endif
    </ul>
    <x-case-report :case-id="$case->id" page="case-details" />
    @error('message')<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
</details>
