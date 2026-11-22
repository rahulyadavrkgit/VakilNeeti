@props([
    'groups',
    'selected' => null,
    'placeholder' => 'Select State / Union Territory',
])

{{-- Options for a State / Union Territory <select>, built from App\Models\State::dropdownGroups(). --}}
<option value="">{{ $placeholder }}</option>
@foreach($groups as $groupLabel => $stateOptions)
    <optgroup label="{{ $groupLabel }}">
        @foreach($stateOptions as $stateOption)
            <option value="{{ $stateOption['name'] }}"
                    data-state-code="{{ $stateOption['code'] }}"
                    data-gst-code="{{ $stateOption['gst_code'] }}"
                    data-high-court="{{ $stateOption['high_court'] }}"
                    @if($stateOption['id']) data-state-id="{{ $stateOption['id'] }}" @endif
                    @selected($selected === $stateOption['name'])>{{ $stateOption['name'] }}</option>
        @endforeach
    </optgroup>
@endforeach
