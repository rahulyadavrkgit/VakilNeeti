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

    <x-page-header title="Tasks & Reminders" subtitle="Your work, deadlines and reminders.">
        <a href="{{ route('tasks.create') }}" data-sheet="{{ route('sheets.task') }}" class="inline-flex min-h-11 items-center rounded-xl bg-[#16776e] px-4 text-sm font-semibold text-white shadow-sm hover:bg-[#125f58]">+ Create task</a>
    </x-page-header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm font-medium text-slate-500">All Tasks</p>
            <p class="mt-2 text-3xl font-bold text-slate-800">{{ $totalTasks }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-white p-5">
            <p class="text-sm font-medium text-slate-500">Open Tasks</p>
            <p class="mt-2 text-3xl font-bold text-amber-700">{{ $pendingTasksCount }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-white p-5">
            <p class="text-sm font-medium text-slate-500">Overdue</p>
            <p class="mt-2 text-3xl font-bold text-red-700">{{ $overdueTasksCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Showing up to 6 overdue tasks below</p>
        </div>
        <div class="rounded-xl border border-green-200 bg-white p-5">
            <p class="text-sm font-medium text-slate-500">Completed Tasks</p>
            <p class="mt-2 text-3xl font-bold text-green-700">{{ $completedTasksCount }}</p>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-3">
        @foreach([
            ['title' => 'Overdue Tasks', 'items' => $overdueTasks, 'color' => 'red'],
            ["title" => "Today's Tasks", 'items' => $todayTasks, 'color' => 'blue'],
            ['title' => 'Upcoming Tasks', 'items' => $upcomingTasks, 'color' => 'amber'],
        ] as $taskGroup)
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">{{ $taskGroup['title'] }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $taskGroup['items']->count() }} shown</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($taskGroup['items'] as $task)
                        <div class="p-4">
                            <a href="{{ route('tasks.show', $task) }}" class="font-semibold text-blue-700 hover:underline">
                                {{ $task->title }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $task->client?->display_name ?: 'No client linked' }}
                                @if($task->legalCase)
                                    · {{ $task->legalCase->case_title }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs {{ $taskGroup['color'] === 'red' ? 'font-semibold text-red-700' : 'text-slate-600' }}">
                                Due: {{ $task->due_at?->format('d M Y, h:i A') ?? 'Not scheduled' }}
                            </p>
                        </div>
                    @empty
                        <p class="p-4 text-sm text-slate-500">No {{ strtolower($taskGroup['title']) }}.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>

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
        <table class="vn-stack-table w-full text-left text-sm">
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
                                  data-confirm="Delete this task?">
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