<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');

        $query = Client::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('organisation_name', 'like', "%{$search}%");
            });
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $clients = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients', 'search', 'status'));
    }

    public function create()
    {
        return view('clients.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_type' => 'required|in:individual,company,organisation',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'alternate_mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pin_code' => 'nullable|string|max:10',
            'occupation' => 'nullable|string|max:255',
            'organisation_name' => 'nullable|string|max:255',
            'referred_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['client_code'] = (Client::withTrashed()->max('id') ?? 0) + 1;
        $validated['status'] = 'active';

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client added successfully.');
    }

    public function show(Client $client)
    {
        $client->load([
            'caseParties.legalCase.hearings',
            'invoices.payments',
            'payments.invoice',
        ]);

        $cases = $client->caseParties
            ->pluck('legalCase')
            ->filter()
            ->unique('id')
            ->values();

        $hearingItems = collect();

        foreach ($cases as $case) {
            foreach ($case->hearings as $hearing) {
                $hearingItems->push((object) [
                    'case' => $case,
                    'hearing' => $hearing,
                ]);
            }
        }

        $hearingItems = $hearingItems->sortByDesc(
            fn ($item) => $item->hearing->hearing_date
                ?? $item->hearing->next_hearing_date
                ?? ''
        )->values();

        $tasks = $client->tasks()
            ->with('legalCase')
            ->orderByRaw("
                CASE
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'in_progress' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('due_at')
            ->get();

        $invoices = $client->invoices()
            ->with('payments')
            ->latest()
            ->get();

        $payments = $client->payments()
            ->with('invoice')
            ->latest('payment_date')
            ->get();

        $eligibleInvoices = $invoices->whereNotIn(
            'status',
            ['draft', 'cancelled']
        );

        $totalInvoiced = (float) $eligibleInvoices->sum('total_amount');

        $totalReceived = (float) $eligibleInvoices->sum(
            fn ($invoice) => $invoice->payments->sum('amount')
        );

        $totalOutstanding = max(0, $totalInvoiced - $totalReceived);

        $activities = $client->activities()
            ->latest('activity_at')
            ->paginate(10);

        return view('clients.show', compact(
            'client',
            'cases',
            'hearingItems',
            'tasks',
            'invoices',
            'payments',
            'totalInvoiced',
            'totalReceived',
            'totalOutstanding',
            'activities'
        ));
    }

    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'client_type' => 'required|in:individual,company,organisation',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'alternate_mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'pin_code' => 'nullable|string|max:10',
            'occupation' => 'nullable|string|max:255',
            'organisation_name' => 'nullable|string|max:255',
            'referred_by' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client moved to deleted clients.');
    }
}