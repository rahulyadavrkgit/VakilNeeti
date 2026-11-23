@props(['caseId', 'return' => 'back', 'open' => false, 'label' => 'Add next date'])

{{--
    "Add next date": opens the next-date pop-up for this case (sheets.next-date). Without JavaScript the link
    opens the full hearing form. `open` highlights it where the case has no date yet.
--}}
<a href="{{ route('hearings.create', ['case' => $caseId, 'return' => $return === 'case' ? 'case' : null]) }}"
   data-sheet="{{ route('sheets.next-date', ['case' => $caseId]) }}"
   {{ $attributes->merge(['class' => 'inline-flex min-h-9 items-center gap-1.5 rounded-lg border border-[#16776e] px-3 text-sm font-semibold '.($open ? 'bg-[#16776e] text-white hover:bg-[#125f58]' : 'bg-white text-[#16776e] hover:bg-teal-50')]) }}>
    <x-icon name="calendar" :size="16" /> {{ $label }}
</a>
