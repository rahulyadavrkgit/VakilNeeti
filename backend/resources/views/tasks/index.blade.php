@extends('layouts.app')

@section('title', 'Tasks & Reminders')
@section('page-heading', 'Tasks & Reminders')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-lg bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Tasks & Reminders</h2>
            <p class="text-sm text-slate-500">Manage client-related and case-related work.</p>
        </div>

        <a href="{{ route('tasks.create') }}"
           class="rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
            + Create Task
        </a>
    </div>

    <form method="GET" action="{{ route('tasks.index') }}"
          class="flex flex-wrap gap-3 rounded-xl border bg-white p-4">

        <select name="status" class="rounded-lg border border-slate-300 p-3 text-sm">
            <option value="">All Statuses</option>
            @foreach(['pending','in_progress','completed','cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>
                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                </option>
            @endforeach
        </select>

        <select name="priority" class="rounded-lg border border-slate-300 p-3 text-sm">
            <option value="">All Priorities</option>
            @foreach(['low','medium','high','urgent'] as $priority)
                <option value="{{ $priority }}" @selected(request('priority') === $priority)>
                    {{ ucfirst($priority) }}
                </option>
            @endforeach
        </select>

        <button type="submit"
            class="rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white">
            Filter
        </button>

        <a href="{{ route('tasks.index') }}"
           class="rounded-lg border px-5 py-3 text-sm font-semibold text-slate-700">
            Reset
        </a>
    </form>

    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-100 text-slate-700">
                <tr>
                    <th class="p-4">Task</th>
                    <th class="p-4">Client</th>
                    <th class="p-4">Related Case</th>
                    <th class="p-4">Due Date</th>
                    <th class="p-4">Priority</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($tasks as $task)
                    <tr class="border-t hover:bg-slate-50">

                        <td class="p-4">
                            <a href="{{ route('tasks.show', $task) }}"
                               class="font-semibold text-blue-700 hover:underline">
                                {{ $task->title }}
                            </a>
                            @if($task->description)
                                <p class="mt-1 max-w-xs text-xs text-slate-500">
                                    {{ $task->description }}
                                </p>
                            @endif
                        </td>

                        <td class="p-4">
                            @if($task->client)
                                <a href="{{ route('clients.show', $task->client) }}"
                                   class="text-blue-700 hover:underline">
                                    {{ $task->client->display_name ?: trim($task->client->first_name.' '.$task->client->last_name) }}
                                </a>
                            @else
                                <span class="text-slate-400">General task</span>
                            @endif
                        </td>

                        <td class="p-4">
                            @if($task->legalCase)
                                <a href="{{ route('cases.show', $task->legalCase) }}"
                                   class="text-blue-700 hover:underline">
                                    {{ $task->legalCase->case_title }}
                                </a>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $task->legalCase->case_number ?: 'No case number' }}
                                </p>
                            @else
                                <span class="text-slate-400">Not linked</span>
                            @endif
                        </td>

                        <td class="p-4 whitespace-nowrap">
                            {{ $task->due_at?->format('d-m-Y H:i') ?? 'Not scheduled' }}
                            @if($task->is_overdue)
                                <span class="mt-1 block text-xs font-semibold text-red-600">Overdue</span>
                            @endif
                        </td>

                        <td class="p-4">
                            {{ ucfirst($task->priority) }}
                        </td>

                        <td class="p-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                        </td>

                        <td class="p-4 whitespace-nowrap">
                            <a href="{{ route('tasks.show', $task) }}"
                               class="text-blue-600 hover:underline">View</a>

                            <span class="mx-1 text-slate-300">|</span>

                            <a href="{{ route('tasks.edit', $task) }}"
                               class="text-blue-600 hover:underline">Edit</a>

                            <form method="POST"
                                  action="{{ route('tasks.destroy', $task) }}"
                                  class="mt-2"
                                  onsubmit="return confirm('Delete this task?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            </form>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">
                            No tasks found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $tasks->links() }}
    </div>

</div>
@endsection