<div class="space-y-5">

    @if($errors->any())
        <div class="rounded-lg bg-red-50 p-4 text-red-700">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label class="mb-1 block text-sm font-medium">Client</label>
        <select name="client_id" id="client_id"
            class="w-full rounded-lg border border-slate-300 p-3">
            <option value="">General task / No client</option>
            @foreach($clients as $client)
                <option value="{{ $client->id }}"
                    @selected((string)old('client_id', $selectedClientId ?? $task->client_id ?? '') === (string)$client->id)>
                    {{ $client->display_name ?: trim($client->first_name.' '.$client->last_name) }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Related Case</label>
        <select name="case_id" id="case_id"
            class="w-full rounded-lg border border-slate-300 p-3">
            <option value="">No case linked</option>
            @foreach($cases as $case)
                @php
                    $clientIds = $case->parties->pluck('client_id')->filter()->unique()->implode(',');
                    if(isset($task) && $task->case_id == $case->id && $task->client_id) {
                        $clientIds = collect(explode(',', $clientIds))->push((string)$task->client_id)->unique()->implode(',');
                    }
                @endphp
                <option value="{{ $case->id }}"
                    data-client-ids="{{ $clientIds }}"
                    @selected((string)old('case_id', $task->case_id ?? '') === (string)$case->id)>
                    {{ $case->case_title }} — {{ $case->case_number ?: 'No case number' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">
            Select a client to show cases connected to that client.
        </p>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Task Title *</label>
        <input type="text" name="title" required
            value="{{ old('title', $task->title ?? '') }}"
            class="w-full rounded-lg border border-slate-300 p-3">
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Description</label>
        <textarea name="description" rows="4"
            class="w-full rounded-lg border border-slate-300 p-3">{{ old('description', $task->description ?? '') }}</textarea>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium">Due Date & Time</label>
            <input type="datetime-local" name="due_at"
                value="{{ old('due_at', isset($task) && $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : '') }}"
                class="w-full rounded-lg border border-slate-300 p-3">
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium">Priority *</label>
            <select name="priority" required
                class="w-full rounded-lg border border-slate-300 p-3">
                @foreach(['low','medium','high','urgent'] as $priority)
                    <option value="{{ $priority }}"
                        @selected(old('priority', $task->priority ?? 'medium') === $priority)>
                        {{ ucfirst($priority) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="mb-1 block text-sm font-medium">Status *</label>
        <select name="status" required
            class="w-full rounded-lg border border-slate-300 p-3">
            @foreach(['pending','in_progress','completed','cancelled'] as $status)
                <option value="{{ $status }}"
                    @selected(old('status', $task->status ?? 'pending') === $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit"
            class="rounded-lg bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">
            {{ isset($task) ? 'Update Task' : 'Create Task' }}
        </button>

        <a href="{{ route('tasks.index') }}"
            class="rounded-lg border border-slate-300 px-5 py-3 font-semibold text-slate-700">
            Cancel
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const clientSelect = document.getElementById('client_id');
    const caseSelect = document.getElementById('case_id');

    function filterCases() {
        const clientId = clientSelect.value;
        const selectedCase = caseSelect.value;

        Array.from(caseSelect.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const linkedClients = (option.dataset.clientIds || '')
                .split(',')
                .filter(Boolean);

            option.hidden = !!clientId && !linkedClients.includes(clientId);
        });

        const currentOption = caseSelect.options[caseSelect.selectedIndex];

        if (currentOption && currentOption.hidden) {
            caseSelect.value = '';
        }
    }

    clientSelect.addEventListener('change', filterCases);
    filterCases();
});
</script>