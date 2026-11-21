<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientActivityController extends Controller
{
    public function index(Client $client)
    {
        $activities = $client->activities()
            ->latest('activity_at')
            ->paginate(20);

        return view('client-activities.index', compact(
            'client',
            'activities'
        ));
    }

    public function create(Client $client)
    {
        return view('client-activities.create', compact('client'));
    }

    public function store(Request $request, Client $client)
    {
        $validated = $request->validate([
            'activity_type' => 'required|in:call,meeting,whatsapp,email,client_instruction,follow_up,note,other',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_at' => 'required|date',
            'follow_up_at' => 'nullable|date|after_or_equal:activity_at',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $client->activities()->create($validated);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Client activity recorded successfully.');
    }
}
