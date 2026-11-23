@php
    // Store links appear once the apps are published (PLAY_STORE_URL / APP_STORE_URL in .env).
    // Until then both buttons lead to the install page, which adds VakilNiti to the phone's home screen.
    $playStoreUrl = config('services.mobile_apps.play_store');
    $appStoreUrl = config('services.mobile_apps.app_store');
    $storeButton = 'inline-flex min-h-12 items-center gap-3 rounded-xl border border-white/15 bg-black/30 px-4 py-2 text-left text-white transition hover:border-white/30 hover:bg-black/45';
@endphp

<div class="flex flex-wrap gap-2.5">
    <a href="{{ $playStoreUrl ?: route('app.download').'#android' }}" class="{{ $storeButton }}"
       @if($playStoreUrl) target="_blank" rel="noopener" data-direct-share @endif>
        <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" class="shrink-0"><path fill="#3ddc84" d="M17.6 9.48l1.84-3.18a.38.38 0 0 0-.66-.38l-1.87 3.23A11.4 11.4 0 0 0 12 8.12c-1.77 0-3.43.4-4.91 1.03L5.22 5.92a.38.38 0 0 0-.66.38L6.4 9.48A10.8 10.8 0 0 0 1 18h22a10.8 10.8 0 0 0-5.4-8.52zM7 15.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm10 0a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5z"/></svg>
        <span class="leading-tight">
            <span class="block text-[10px] uppercase tracking-wide text-white/70">{{ $playStoreUrl ? 'Get it on' : 'Install on' }}</span>
            <span class="block text-sm font-semibold">{{ $playStoreUrl ? 'Google Play' : 'Android' }}</span>
        </span>
    </a>
    <a href="{{ $appStoreUrl ?: route('app.download').'#iphone' }}" class="{{ $storeButton }}"
       @if($appStoreUrl) target="_blank" rel="noopener" data-direct-share @endif>
        <x-icon name="phone" :size="22" class="text-white" />
        <span class="leading-tight">
            <span class="block text-[10px] uppercase tracking-wide text-white/70">{{ $appStoreUrl ? 'Download on the' : 'Install on' }}</span>
            <span class="block text-sm font-semibold">{{ $appStoreUrl ? 'App Store' : 'iPhone & iPad' }}</span>
        </span>
    </a>
</div>
