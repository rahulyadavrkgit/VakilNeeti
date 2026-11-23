{{-- One tap records where the petitioner's counsel stands in this court (facing the judge). --}}
<div class="mt-1.5 flex gap-1.5 print:hidden">
    @foreach(\App\Models\CourtStandSide::SIDES as $sideValue => $sideLabel)
        <form method="POST" action="{{ route('cases.stand-side', $case) }}" class="flex-1">
            @csrf
            <input type="hidden" name="petitioner_side" value="{{ $sideValue }}">
            <button type="submit" class="min-h-9 w-full rounded-lg border border-slate-300 bg-white px-2 text-sm font-semibold text-slate-800 hover:border-[#16776e] hover:text-[#16776e]">Your {{ strtolower($sideLabel) }}</button>
        </form>
    @endforeach
</div>
