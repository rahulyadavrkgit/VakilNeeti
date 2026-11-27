{{-- Asks once to turn on alerts on this device (resources/js/push.js shows it only while the browser has not been
     asked). Once allowed, alerts pop up on screen and on a locked phone, with sound and vibration. --}}
<div data-push-offer hidden class="flex flex-wrap items-center gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm">
    <x-icon name="bell" :size="18" class="shrink-0 text-[#16776e]" />
    <span class="min-w-0 flex-[1_1_14rem] text-teal-900">
        <strong>Turn on alerts on this device?</strong>
        When a court with your matter goes live, and as your item comes near, they pop up even when the phone is locked or VakilNiti is closed.
    </span>
    <button type="button" data-push-enable class="min-h-11 rounded-lg bg-[#16776e] px-4 font-semibold text-white hover:bg-[#125f58] max-sm:w-full">Turn on</button>
</div>
<p data-push-ios-hint hidden class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
    On iPhone, alerts can pop up once VakilNiti is on your Home Screen: tap Share, then <strong>Add to Home Screen</strong>, open it from there and tap Turn on.
</p>
