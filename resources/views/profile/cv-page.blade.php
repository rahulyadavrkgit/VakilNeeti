<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>{{ $cv['name'] }} · {{ $cv['title'] }}</title>
    <meta name="description" content="{{ Str::limit(collect([$cv['title'], $cv['location'], implode(', ', array_slice($cv['practice'], 0, 4))])->filter()->implode(' · '), 155) }}">
    <meta property="og:title" content="{{ $cv['name'] }} · {{ $cv['title'] }}">
    <meta property="og:description" content="{{ collect([$cv['location'], implode(', ', array_slice($cv['practice'], 0, 4))])->filter()->implode(' · ') }}">
    @if($cv['photo_url'])<meta property="og:image" content="{{ $cv['photo_url'] }}">@endif
    <meta name="theme-color" content="#142235">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-mark.svg') }}">
    @if($isEmbedded)<base target="_top">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f6f5f1] font-sans text-slate-800 antialiased">
    @if($isOwnerPreview && ! $isEmbedded)
        <div class="border-b border-amber-200 bg-amber-50 text-sm text-amber-900">
            <p class="mx-auto max-w-4xl px-4 py-2.5">Preview · {{ $shareUrl ? 'This is how your live page looks.' : 'Only you can see this until you publish.' }}</p>
        </div>
    @endif

    <header class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 pt-4 pb-3 sm:pt-6">
        <div class="flex min-w-0 items-center gap-3">
            @if($backUrl && ! $isEmbedded)
                <x-back-link :fallback="$backUrl" class="min-h-11 shrink-0 rounded-xl" />
            @endif
            <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-[#142235]" aria-label="VakilNiti home">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="h-7 w-7">
                <span>VakilNiti</span>
            </a>
        </div>
        <a href="{{ $downloadUrl }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.69L6.28 8.47a.75.75 0 1 0-1.06 1.06l4.25 4.25c.3.3.77.3 1.06 0l4.25-4.25a.75.75 0 1 0-1.06-1.06l-2.97 2.97V2.75Z"/><path d="M3.5 13.75a.75.75 0 0 0-1.5 0v1.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-1.5a.75.75 0 0 0-1.5 0v1.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-1.5Z"/></svg>
            Download CV
        </a>
    </header>

    <main class="mx-auto max-w-4xl px-4 pb-10">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm [overflow-wrap:anywhere]">
            {{-- Identity --}}
            <section class="relative bg-[#142235] px-5 py-7 text-white sm:px-9 sm:py-9">
                <div class="absolute inset-x-0 top-0 h-1 bg-[#16776e]"></div>
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-7">
                    @if($cv['photo_url'])
                        <img src="{{ $cv['photo_url'] }}" alt="Photo of {{ $cv['name'] }}" class="h-24 w-24 shrink-0 rounded-2xl object-cover ring-2 ring-white/20 sm:h-28 sm:w-28">
                    @else
                        <div aria-hidden="true" class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-[#16776e] font-serif text-3xl text-white ring-2 ring-white/15 sm:h-24 sm:w-24">{{ $cv['initials'] }}</div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="font-serif text-3xl leading-tight sm:text-4xl">{{ $cv['name'] }}</h1>
                        <p class="mt-1 text-base text-white/80">
                            {{ $cv['title'] }}@if($cv['location'])<span class="text-white/50"> · </span>{{ $cv['location'] }}@endif
                        </p>
                        @if($cv['courts'])
                            <p class="mt-1 text-sm text-white/70">{{ $cv['courts'][0] }}</p>
                        @endif
                    </div>
                </div>

                @if($cv['contacts'])
                    <ul class="mt-6 flex flex-wrap gap-2" aria-label="Contact">
                        @foreach($cv['contacts'] as $contact)
                            <li>
                                <a href="{{ $contact['href'] }}" @if($contact['type'] === 'website') target="_blank" rel="noopener noreferrer" @endif
                                   class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-white/10 px-3.5 text-sm text-white hover:bg-white/15">
                                    @if($contact['type'] === 'email')
                                        <svg aria-hidden="true" class="h-4 w-4 text-white/70" viewBox="0 0 20 20" fill="currentColor"><path d="M3 4a2 2 0 0 0-2 2v1.16l8.4 4.2a1.5 1.5 0 0 0 1.2 0L19 7.16V6a2 2 0 0 0-2-2H3Z"/><path d="m19 8.84-7.73 3.87a3 3 0 0 1-2.54 0L1 8.84V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.84Z"/></svg>
                                    @elseif($contact['type'] === 'phone')
                                        <svg aria-hidden="true" class="h-4 w-4 text-white/70" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 0 1 3.5 2h1.15c.69 0 1.29.47 1.46 1.14l.72 2.88a1.5 1.5 0 0 1-.55 1.56l-.97.73a.5.5 0 0 0-.15.6 10.5 10.5 0 0 0 5.93 5.93.5.5 0 0 0 .6-.15l.73-.97a1.5 1.5 0 0 1 1.56-.55l2.88.72c.67.17 1.14.77 1.14 1.46v1.15a1.5 1.5 0 0 1-1.5 1.5H15C7.82 18 2 12.18 2 5V3.5Z" clip-rule="evenodd"/></svg>
                                    @else
                                        <svg aria-hidden="true" class="h-4 w-4 text-white/70" viewBox="0 0 20 20" fill="currentColor"><path d="M12.23 3.2a4.25 4.25 0 0 1 6 6.02l-2.5 2.5a4.25 4.25 0 0 1-6.02 0 .75.75 0 0 1 1.06-1.06 2.75 2.75 0 0 0 3.9 0l2.5-2.5a2.75 2.75 0 0 0-3.9-3.9l-1 1a.75.75 0 0 1-1.06-1.06l1.02-1Z"/><path d="M7.77 16.8a4.25 4.25 0 0 1-6-6.02l2.5-2.5a4.25 4.25 0 0 1 6.02 0 .75.75 0 1 1-1.06 1.06 2.75 2.75 0 0 0-3.9 0l-2.5 2.5a2.75 2.75 0 0 0 3.9 3.9l1-1a.75.75 0 0 1 1.06 1.06l-1.02 1Z"/></svg>
                                    @endif
                                    {{ $contact['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Details --}}
            @php($hasMain = $cv['about'] || $cv['practice'] || $cv['courts'] || $cv['education'] || $cv['memberships'])
            @if($hasMain || $cv['facts'] || $shareUrl)
            <div @class(['grid gap-8 px-5 py-7 sm:px-9 sm:py-9', 'md:grid-cols-[minmax(0,1fr)_15rem]' => $hasMain])>
                @if($hasMain)
                <div class="min-w-0 space-y-7">
                    @if($cv['about'])
                        <section>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">About</h2>
                            <p class="mt-2 leading-relaxed text-slate-700">{!! nl2br(e($cv['about'])) !!}</p>
                        </section>
                    @endif

                    @if($cv['practice'])
                        <section>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">Practice areas</h2>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach($cv['practice'] as $area)
                                    <li class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-1.5 text-sm text-teal-900">{{ $area }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if($cv['courts'])
                        <section>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">Courts</h2>
                            <ul class="mt-2 space-y-1.5 text-slate-700">
                                @foreach($cv['courts'] as $court)
                                    <li class="flex gap-2"><span aria-hidden="true" class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#e6b84a]"></span>{{ $court }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @foreach(['Education' => $cv['education'], 'Memberships' => $cv['memberships']] as $heading => $entries)
                        @if($entries)
                            <section>
                                <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">{{ $heading }}</h2>
                                <ul class="mt-2 space-y-1.5 text-slate-700">
                                    @foreach($entries as $entry)
                                        <li class="flex gap-2"><span aria-hidden="true" class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#e6b84a]"></span>{{ $entry }}</li>
                                    @endforeach
                                </ul>
                            </section>
                        @endif
                    @endforeach
                </div>
                @endif

                <aside @class(['min-w-0', 'md:border-l md:border-slate-200 md:pl-7' => $hasMain])>
                    @if($cv['facts'])
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">At a glance</h2>
                        <dl @class(['mt-3', 'space-y-3.5' => $hasMain, 'grid gap-4 sm:grid-cols-2 lg:grid-cols-4' => ! $hasMain])>
                            @foreach($cv['facts'] as $label => $value)
                                <div>
                                    <dt class="text-xs text-slate-500">{{ $label }}</dt>
                                    <dd class="font-medium text-slate-900 tabular-nums">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif

                    @if($shareUrl)
                        <div @class(['space-y-2', 'mt-7 border-t border-slate-200 pt-6' => (bool) $cv['facts']])>
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-[#16776e]">Share</h2>
                            <div @class(['flex flex-wrap gap-2', 'md:flex-col' => $hasMain])>
                                <button type="button" data-copy="{{ $shareUrl }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-800 hover:bg-slate-50"><span data-copy-label>Copy link</span></button>
                                <a href="https://wa.me/?text={{ rawurlencode($cv['name'].' · '.$cv['title']."\n".$shareUrl) }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">WhatsApp</a>
                                <a href="mailto:?subject={{ rawurlencode($cv['name'].' · CV') }}&amp;body={{ rawurlencode($shareUrl) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-slate-800 hover:bg-slate-50">Email</a>
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
            @endif
        </article>

        @if($isOwnerPreview && ! $isEmbedded && $missing)
            <p class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
                Not shown yet: {{ implode(', ', $missing) }}.
                <a href="{{ route('profile.advocate.index') }}" class="font-semibold text-[#16776e] underline underline-offset-2">Add in Advocate profile</a>
            </p>
        @endif

        <footer class="mt-6 text-center text-xs text-slate-500">
            @if($cv['updated'])Updated {{ $cv['updated'] }} · @endif
            Profile on <a href="{{ url('/') }}" class="underline underline-offset-2">VakilNiti</a>
        </footer>
    </main>
</body>
</html>
