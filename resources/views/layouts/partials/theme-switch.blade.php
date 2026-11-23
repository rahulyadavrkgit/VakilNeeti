{{-- Appearance in the account menu: follow the device, or always light or dark. Saved to the account (resources/js/ui-actions.js). --}}
<div class="flex items-center justify-between gap-3 px-4 py-2" data-theme-switch data-save-url="{{ route('profile.theme.update') }}">
    <span class="text-slate-700">Appearance</span>
    <span class="inline-flex rounded-lg bg-slate-100 p-0.5" role="group" aria-label="Appearance">
        @foreach(['system' => ['device', 'Same as device'], 'light' => ['sun', 'Light'], 'dark' => ['moon', 'Dark']] as $value => [$icon, $label])
            <button type="button" data-theme-option="{{ $value }}" title="{{ $label }}" aria-label="{{ $label }}"
                    class="flex h-8 w-9 items-center justify-center rounded-md text-slate-500 hover:text-slate-900 aria-pressed:bg-white aria-pressed:text-[#16776e] aria-pressed:shadow-sm">
                <x-icon :name="$icon" :size="16" />
            </button>
        @endforeach
    </span>
</div>
