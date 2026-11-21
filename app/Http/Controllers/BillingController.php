<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;

class BillingController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')
            ->latest('invoice_date')
            ->paginate(10, ['*'], 'invoice_page');

        $payments = Payment::with(['client', 'invoice'])
            ->latest('payment_date')
            ->paginate(10, ['*'], 'payment_page');

        return view('billing.index', compact('invoices', 'payments'));
    }
}