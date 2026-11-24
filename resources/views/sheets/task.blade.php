{{-- Pop-up: a quick task. Saves through tasks.store. --}}
@php
    $field = 'mt-1 w-full rounded-xl border border-slate-300 bg-transparent px-3 py-2.5 text-base sm:text-sm';
    $label = 'block text-sm font-semibold text-slate-700';
@endphp
<h2 data-sheet-heading class="text-lg font-semibold text-slate-900">{{ __('New task') }}</h2>

<form method="POST" action="{{ route('tasks.store') }}" data-sheet-form data-sheet-success="{{ __('Task added') }}" class="mt-4 space-y-3">
    @csrf
    <input type="hidden" name="status" value="pending">
    <label class="{{ $label }}">
        {{ __('Task') }}
        <input type="text" name="title" required maxlength="255" autofocus placeholder="{{ __('File reply, collect documents…') }}" class="{{ $field }}">
    </label>
    <div class="grid grid-cols-2 gap-3">
        <label class="{{ $label }}">
            {{ __('Due') }}
            <input type="datetime-local" name="due_at" value="{{ today()->setTime(18, 0)->format('Y-m-d\TH:i') }}" class="{{ $field }}">
        </label>
        <label class="{{ $label }}">
            {{ __('Priority') }}
            <select name="priority" class="{{ $field }}">
                @foreach(['medium' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent', 'low' => 'Low'] as $value => $text)
                    <option value="{{ $value }}">{{ __($text) }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <label class="{{ $label }}">
        {{ __('Case') }} <span class="font-normal text-slate-500">({{ __('optional') }})</span>
        <select name="case_id" class="{{ $field }}">
            <option value="">{{ __('No case') }}</option>
            @foreach($cases as $option)
                <option value="{{ $option->id }}" @selected($caseId === $option->id)>{{ $option->case_title }}</option>
            @endforeach
        </select>
    </label>
    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('tasks.create') }}" class="text-sm font-semibold text-[#16776e] hover:underline">{{ __('More details') }}</a>
        <button type="submit" class="min-h-11 rounded-xl bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">{{ __('Add task') }}</button>
    </div>
</form>
