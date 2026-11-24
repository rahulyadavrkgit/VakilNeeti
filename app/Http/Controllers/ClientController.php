<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', '');
        $type = in_array($request->input('type'), ['people', 'companies'], true) ? $request->input('type') : 'all';
        $view = $request->input('view') === 'grid' ? 'grid' : 'list';

        $query = Client::query()->with('company')->withCount('people');

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

        match ($type) {
            'people' => $query->whereNotIn('client_type', Client::COMPANY_TYPES),
            'companies' => $query->whereIn('client_type', Client::COMPANY_TYPES),
            default => null,
        };

        $clients = $query->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => Client::query()->count(),
            'people' => Client::query()->whereNotIn('client_type', Client::COMPANY_TYPES)->count(),
            'companies' => Client::query()->whereIn('client_type', Client::COMPANY_TYPES)->count(),
        ];

        return view('clients.index', compact('clients', 'search', 'status', 'type', 'view', 'counts'));
    }

    public function create(Request $request): View
    {
        return $this->form(new Client([
            'client_type' => $request->input('type') === 'company' ? 'company' : 'individual',
            'country' => 'India',
            'status' => 'active',
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['client_code'] = Client::nextCode();
        $validated['status'] = 'active';

        Client::create($validated);

        return redirect()->route('clients.index')
            ->with('success', 'Client added successfully.');
    }

    public function show(Client $client)
    {
        $client->load([
            'company',
            'people',
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

    public function edit(Client $client): View
    {
        return $this->form($client);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validated($request, $client));

        return redirect()->route('clients.show', $client)
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client moved to deleted clients.');
    }

    /**
     * Delete several selected clients at once (only the signed-in user's own clients).
     */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'Select at least one client.']);

        $deleted = Client::query()->whereIn('id', $validated['ids'])->get()->each->delete()->count();

        return redirect()->route('clients.index', $request->only('type', 'view'))
            ->with('success', $deleted === 1 ? '1 client deleted.' : "{$deleted} clients deleted.");
    }

    private function form(Client $client): View
    {
        return view('clients.form', [
            'client' => $client,
            'companies' => Client::query()
                ->whereIn('client_type', Client::COMPANY_TYPES)
                ->when($client->exists, fn ($query) => $query->whereKeyNot($client->id))
                ->orderBy('organisation_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Client $client = null): array
    {
        $isCompany = in_array($request->input('client_type'), Client::COMPANY_TYPES, true);

        $validated = $request->validate([
            'client_type' => ['required', Rule::in(['individual', 'company', 'organisation'])],
            'first_name' => [$isCompany ? 'nullable' : 'required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'organisation_name' => [$isCompany ? 'required' : 'nullable', 'string', 'max:255'],
            'company_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('user_id', auth()->user()->workspaceId())->whereIn('client_type', Client::COMPANY_TYPES)->whereNull('deleted_at'),
                Rule::notIn(array_filter([$client?->id])),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'alternate_mobile' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'address' => ['nullable', 'string'],
            'locality' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255', Rule::when(in_array($request->input('country'), [null, '', 'India'], true), [State::nameRule()])],
            'country' => ['nullable', 'string', 'max:255'],
            'pin_code' => ['nullable', 'string', 'max:10'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'referred_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => [$client ? 'required' : 'nullable', Rule::in(['active', 'inactive'])],
        ], [
            'first_name.required' => 'Enter the first name.',
            'organisation_name.required' => 'Enter the company name.',
            'company_id.exists' => 'Choose one of your companies.',
            'company_id.not_in' => 'A company cannot belong to itself.',
            'website.url' => 'Enter a website starting with http:// or https://.',
        ]);

        if ($isCompany) {
            // A company is named by its company name; it does not work for another company.
            $validated['first_name'] = ($validated['first_name'] ?? null) ?: $validated['organisation_name'];
            $validated['company_id'] = null;
        }

        if (! $client) {
            unset($validated['status']);
        }

        return $validated;
    }
}
