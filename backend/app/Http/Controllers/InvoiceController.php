<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\FirmProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('client')
            ->latest()
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $selectedClientId = $request->query('client_id');

        $clients = Client::where(function ($query) use ($selectedClientId) {
                $query->where('status', 'active');

                if ($selectedClientId) {
                    $query->orWhere('id', $selectedClientId);
                }
            })
            ->orderBy('first_name')
            ->get();

        return view('invoices.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $discount = $validated['discount'] ?? 0;
        $tax = $validated['tax_amount'] ?? 0;

        if ($discount > $validated['subtotal']) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot exceed subtotal.',
            ]);
        }

        $validated['discount'] = $discount;
        $validated['tax_amount'] = $tax;
        $validated['total_amount'] = $validated['subtotal'] - $discount + $tax;
        $validated['currency'] = 'INR';
        $validated['invoice_number'] = 'INV-' . strtoupper(Str::random(10));
        $validated['status'] = 'sent';

        Invoice::create($validated);

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'payments']);

        $firm = FirmProfile::first();

        return view('invoices.show', compact('invoice', 'firm'));
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'payments']);

        $firm = FirmProfile::first();

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'firm'))
            ->setPaper('a4', 'portrait');

        return $pdf->download(
            'invoice-' . $invoice->invoice_number . '.pdf'
        );
    }

    public function edit(Invoice $invoice)
    {
        $clients = Client::where('status', 'active')->get();

        return view('invoices.edit', compact('invoice', 'clients'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,sent,partially_paid,paid,overdue,cancelled',
        ]);

        $discount = $validated['discount'] ?? 0;
        $tax = $validated['tax_amount'] ?? 0;

        if ($discount > $validated['subtotal']) {
            throw ValidationException::withMessages([
                'discount' => 'Discount cannot exceed subtotal.',
            ]);
        }

        $validated['discount'] = $discount;
        $validated['tax_amount'] = $tax;
        $validated['total_amount'] = $validated['subtotal'] - $discount + $tax;

        $invoice->update($validated);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Invoice with payments cannot be deleted.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
