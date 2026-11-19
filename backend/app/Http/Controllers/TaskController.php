<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\LegalCase;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with(['client', 'legalCase'])->latest();

        if ($request->filled('status') &&
            in_array($request->status, ['pending', 'in_progress', 'completed', 'cancelled'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') &&
            in_array($request->priority, ['low', 'medium', 'high', 'urgent'], true)) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->paginate(15)->withQueryString();

        return view('tasks.index', compact('tasks'));
    }

    public function create(Request $request)
    {
        $selectedClientId = $request->query('client_id');

        $clients = Client::where(function ($query) use ($selectedClientId) {
            $query->where('status', 'active');

            if ($selectedClientId) {
                $query->orWhere('id', $selectedClientId);
            }
        })->orderBy('first_name')->get();

        $cases = LegalCase::with('parties')->latest()->get();

        return view('tasks.create', compact(
            'clients',
            'cases',
            'selectedClientId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'case_id' => 'nullable|exists:legal_cases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if (!empty($validated['client_id']) && !empty($validated['case_id'])) {
            $linked = LegalCase::whereKey($validated['case_id'])
                ->whereHas('parties', function ($query) use ($validated) {
                    $query->where('client_id', $validated['client_id']);
                })->exists();

            if (!$linked) {
                return back()
                    ->withErrors(['case_id' => 'The selected case is not connected to the selected client.'])
                    ->withInput();
            }
        }

        $validated['completed_at'] = $validated['status'] === 'completed'
            ? now()
            : null;

        Task::create($validated);

        return redirect()->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['client', 'legalCase']);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $clients = Client::where(function ($query) use ($task) {
            $query->where('status', 'active');

            if ($task->client_id) {
                $query->orWhere('id', $task->client_id);
            }
        })->orderBy('first_name')->get();

        $cases = LegalCase::with('parties')->latest()->get();

        return view('tasks.edit', compact('task', 'clients', 'cases'));
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'case_id' => 'nullable|exists:legal_cases,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_at' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        if (!empty($validated['client_id']) && !empty($validated['case_id'])) {
            $linked = LegalCase::whereKey($validated['case_id'])
                ->whereHas('parties', function ($query) use ($validated) {
                    $query->where('client_id', $validated['client_id']);
                })->exists();

            if (!$linked) {
                return back()
                    ->withErrors(['case_id' => 'The selected case is not connected to the selected client.'])
                    ->withInput();
            }
        }

        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($task->completed_at ?? now())
            : null;

        $task->update($validated);

        return redirect()->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }
}