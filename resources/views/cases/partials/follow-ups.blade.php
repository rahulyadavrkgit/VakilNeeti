<section id="case-follow-ups" data-tab-panel role="tabpanel" aria-labelledby="tab-case-follow-ups">
    <x-panel>
        <h2 class="text-lg font-semibold text-slate-900">Case follow-ups</h2>
        <div class="my-3 divide-y divide-slate-200">
            @forelse($followUps as $task)
                <article class="flex flex-wrap items-center justify-between gap-2 py-3">
                    <div>
                        <p class="font-semibold">{{ $task->title }}</p>
                        <p class="text-sm text-slate-600">{{ $task->assignee?->name ?? 'Unassigned' }} &middot; Due {{ $task->due_at?->format('d M Y') }} &middot; {{ str($task->status)->replace('_', ' ')->ucfirst() }}</p>
                    </div>
                    @if(in_array(auth()->id(), [$task->assigned_to, $case->user_id], true))
                        <form method="POST" action="{{ route('cases.follow-ups.status', [$case, $task]) }}" class="flex gap-2">
                            @csrf @method('PATCH')
                            <select name="status" aria-label="Status for {{ $task->title }}" class="min-h-11 rounded-lg border-slate-300">
                                @foreach(['pending' => 'Pending', 'in_progress' => 'In progress', 'completed' => 'Completed'] as $value => $label)
                                    <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="min-h-11 rounded-lg border border-slate-300 px-3 text-sm font-semibold">Save</button>
                        </form>
                    @endif
                </article>
            @empty
                <p class="py-3 text-sm text-slate-600">No follow-ups assigned.</p>
            @endforelse
        </div>
        @foreach(['title', 'assigned_to', 'due_at', 'status'] as $field)
            @error($field)<p role="alert" class="text-sm text-red-700">{{ $message }}</p>@enderror
        @endforeach
        <form method="POST" action="{{ route('cases.follow-ups.store', $case) }}" class="grid gap-2 sm:grid-cols-3">
            @csrf
            <label class="text-sm font-semibold">Next action<input required name="title" maxlength="255" value="{{ old('title') }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
            <label class="text-sm font-semibold">Assign to<select required name="assigned_to" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300">
                @foreach($followUpTeam as $person)<option value="{{ $person->id }}" @selected((int)old('assigned_to', auth()->id()) === $person->id)>{{ $person->name }}</option>@endforeach
            </select></label>
            <label class="text-sm font-semibold">Due date<input required type="date" name="due_at" value="{{ old('due_at') }}" min="{{ today()->toDateString() }}" class="mt-1 block min-h-11 w-full rounded-lg border-slate-300"></label>
            <button class="min-h-11 justify-self-start rounded-lg bg-teal-700 px-4 text-sm font-semibold text-white">Assign follow-up</button>
        </form>
    </x-panel>
</section>
