{{-- What a visitor sees first on Home: what VakilNiti does for an advocate, and one clear way to start. --}}
<section aria-labelledby="landing-heading" class="overflow-hidden rounded-3xl border border-[#e7dfd0] bg-[#fbf8f2] p-5 shadow-sm sm:p-8 dark:border-white/10 dark:bg-white/[0.04]">
    <div class="grid items-center gap-6 lg:grid-cols-[1.1fr_1fr]">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-[#16776e]">{{ __('For advocates in India') }}</p>
            <h1 id="landing-heading" class="mt-2 text-3xl font-extrabold leading-tight tracking-tight text-[#142235] sm:text-4xl dark:text-white">
                {{ __('Your case diary, next dates and court links in one place') }}
            </h1>
            <p class="mt-3 max-w-xl text-base text-slate-700 dark:text-white/80">
                {{ __('Note the next date in court, get reminded the day before, see where your Delhi High Court matter is listed, and send clients their update — from your phone or your desk.') }}
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center rounded-xl bg-[#16776e] px-5 text-base font-bold text-white shadow-sm hover:bg-[#12635c]">{{ __('Create your account') }}</a>
                <a href="{{ route('login') }}" class="inline-flex min-h-12 items-center rounded-xl border border-slate-300 bg-white px-5 text-base font-semibold text-[#142235] hover:border-[#16776e] hover:text-[#16776e] dark:border-white/20 dark:bg-transparent dark:text-white">{{ __('Sign in') }}</a>
            </div>
            <p class="mt-3 text-sm text-slate-600 dark:text-white/70">{{ __('Sign in with your email and a one-time code. No password to remember.') }}</p>
        </div>

        {{-- A small picture of the day, drawn with the same pieces the app uses. --}}
        <div aria-hidden="true" class="rounded-2xl bg-[#142235] p-4 text-white shadow-lg">
            <p class="text-sm font-semibold text-white/80">{{ __('Tuesday, 10 Mar') }}</p>
            <div class="mt-2 flex flex-wrap gap-2 text-sm">
                <span class="rounded-full bg-red-600 px-3 py-1 font-semibold">{{ __('3 today') }}</span>
                <span class="rounded-full bg-white/10 px-3 py-1 font-semibold text-white/80">{{ __('5 tomorrow') }}</span>
                <span class="rounded-full bg-[#e6b84a] px-3 py-1 font-semibold text-[#142235]">{{ __('2 tasks due') }}</span>
            </div>
            <div class="mt-3 space-y-2 rounded-xl bg-white/[0.06] p-3 text-sm">
                <p class="font-semibold">{{ __('In the Delhi HC cause list') }}</p>
                <p class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-red-600 px-2 text-xs font-bold">{{ __('Today') }}</span><span class="font-semibold tabular-nums">{{ __('Court 33 · Item 12') }}</span><span class="text-white/80">{{ __('Sharma vs Union of India') }}</span></p>
                <p class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-white/10 px-2 text-xs font-bold text-white/80">{{ __('Tomorrow') }}</span><span class="font-semibold tabular-nums">{{ __('Court 4 · Item 7') }}</span><span class="text-white/80">{{ __('Mehta vs DDA') }}</span></p>
            </div>
            <div class="mt-3 rounded-xl bg-white p-3 text-sm text-[#142235] dark:text-white">
                <p class="text-xs font-bold uppercase tracking-wide text-red-600">{{ __('Next hearing · Today · 10:30') }}</p>
                <p class="mt-1 font-bold">{{ __('Asha Gupta vs State') }}</p>
                <p class="text-slate-600">{{ __('Saket Courts · Room 214 · Arguments') }}</p>
            </div>
        </div>
    </div>

    <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['bell', 'Never miss a next date', "A reminder in the bell before every hearing, task and appointment, and a list of tomorrow's matters."],
            ['building', 'Delhi HC cause list, checked for you', 'Each morning and evening we look for your matters and show the court and item number.'],
            ['calendar', 'Court day on your phone', "See today's and tomorrow's matters, with VC links and client numbers."],
            ['board', 'Live display boards', 'See which item is running in your court before you walk in.'],
            ['people', 'Clients and billing', 'Client records, GST invoices, payments, and a link clients open to see their next date.'],
            ['briefcase', 'Juniors and clerks', 'Share your diary with your team. Clerks add dates; billing stays with you.'],
        ] as [$icon, $title, $text])
            <li class="flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/[0.04]">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-[#16776e] dark:bg-teal-400/10 dark:text-[#8ed2c8]"><x-icon :name="$icon" :size="20" /></span>
                <span>
                    <span class="block text-base font-bold text-[#142235] dark:text-white">{{ __($title) }}</span>
                    <span class="mt-0.5 block text-sm text-slate-600 dark:text-white/70">{{ __($text) }}</span>
                </span>
            </li>
        @endforeach
    </ul>

    <p class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600 dark:text-white/70">
        <span class="inline-flex items-center gap-1.5"><x-icon name="shield" :size="16" class="text-[#16776e]" /> Your cases are private to you and your team</span>
        <a href="{{ route('app.download') }}" class="font-semibold text-[#16776e] hover:underline dark:text-[#8ed2c8]">{{ __('Put it on your phone\'s home screen') }}</a>
        <a href="{{ route('legal.privacy') }}" class="font-semibold text-[#16776e] hover:underline dark:text-[#8ed2c8]">{{ __('How we handle your data') }}</a>
    </p>
</section>
