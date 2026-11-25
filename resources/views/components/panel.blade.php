<section {{ $attributes->class(['rounded-xl border border-slate-200 bg-white shadow-sm', 'p-4' => ! $attributes->has('class')]) }}>
    {{ $slot }}
</section>
