<?php

namespace App\Http\Controllers;

use App\Models\Client;

class ClientManagementController extends Controller
{
    public function index()
    {
        $totalClients = Client::count();

        $activeClients = Client::where('status', 'active')->count();

        $inactiveClients = Client::where('status', 'inactive')->count();

        $clients = Client::withCount([
                'caseParties',
                'invoices',
                'tasks',
            ])
            ->withSum('invoices as total_invoiced', 'total_amount')
            ->latest()
            ->paginate(15);

        $outstandingAmount = (float) Client::query()
            ->with('invoices.payments')
            ->get()
            ->sum(fn ($client) => $client->outstanding_balance);

        return view('client-management.index', compact(
            'totalClients',
            'activeClients',
            'inactiveClients',
            'clients',
            'outstandingAmount'
        ));
    }
}