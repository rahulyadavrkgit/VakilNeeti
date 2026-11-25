{{-- The standard section box (DESIGN.md). Pass a class to change the padding or add spacing. --}}
<section {{ $attributes->class(['rounded-2xl border border-slate-200 bg-white shadow-sm', 'p-4 sm:p-5' => ! $attributes->has('class')]) }}>
    {{ $slot }}
</section>
