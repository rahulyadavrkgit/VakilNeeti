{{-- Guided profile setup: which step this is, the steps ahead, and Skip / Finish later. Shown with ?setup=1. --}}
@if(request()->boolean('setup') && auth()->check())
    @php
        $setupSteps = app(\App\Services\ProfileCompletion::class)->steps(auth()->user());
        $stepNumber = collect($setupSteps)->search(fn ($setupStep) => $setupStep['key'] === $step);
        $percent = app(\App\Services\ProfileCompletion::class)->forUser(auth()->user())['percent'];
    @endphp
    @if($stepNumber !== false)
        <section aria-label="Profile setup" class="rounded-2xl border border-teal-200 bg-teal-50/70 p-4 sm:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">Profile setup · step {{ $stepNumber + 1 }} of {{ count($setupSteps) }}</p>
                    <p class="mt-0.5 text-sm text-slate-700">Your profile is {{ $percent }}% complete. {{ $continueLabel ?? 'Save this page to go on to the next step.' }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @isset($continueUrl)
                        <a href="{{ $continueUrl }}" class="inline-flex min-h-10 items-center rounded-lg bg-[#16776e] px-4 text-sm font-semibold text-white hover:bg-[#125f58]">Continue &rarr;</a>
                    @endisset
                    <a href="{{ route('profile.setup.skip', $step) }}" class="inline-flex min-h-10 items-center rounded-lg border border-[#16776e] bg-white px-3.5 text-sm font-semibold text-[#16776e] hover:bg-teal-50">Skip this step</a>
                    <a href="{{ route('dashboard') }}" class="inline-flex min-h-10 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-white">Finish later</a>
                </div>
            </div>
            <ol class="mt-3 grid gap-2" style="grid-template-columns: repeat({{ count($setupSteps) }}, minmax(0, 1fr))">
                @foreach($setupSteps as $index => $setupStep)
                    <li>
                        <span class="block h-1.5 rounded-full {{ $index === $stepNumber ? 'bg-[#16776e]' : ($setupStep['done'] ? 'bg-teal-300' : 'bg-white ring-1 ring-inset ring-teal-200') }}"></span>
                        <span class="mt-1 block truncate text-[11px] font-semibold {{ $index === $stepNumber ? 'text-[#16776e]' : 'text-slate-500' }}">
                            {{ $setupStep['done'] ? '✓ ' : '' }}{{ $setupStep['label'] }}
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif
@endif
