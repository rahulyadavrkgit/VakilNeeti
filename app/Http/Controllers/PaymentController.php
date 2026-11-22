<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['client', 'invoice'])
            ->latest()
            ->paginate(15);

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $selectedClientId = $request->query('client_id');

        $query = Invoice::with(['client', 'payments', 'tdsDeductions'])
            ->whereIn('status', ['sent', 'partially_paid', 'overdue']);

        if ($selectedClientId) {
            $query->where('client_id', $selectedClientId);
        }

        $invoices = $query->latest()->get()
            ->filter(function ($invoice) {
                $cashReceived = (float) $invoice->payments->sum('amount');
                $tdsDeducted = (float) $invoice->tdsDeductions->sum('amount');

                $outstanding = (float) $invoice->total_amount
                    - $cashReceived
                    - $tdsDeducted;

                return $outstanding > 0;
            })
            ->values();

        return view('payments.create', compact('invoices', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('user_id', auth()->id())],
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'payment_method' => 'required|in:cash,upi,bank_transfer,cheque,card,other',
            'transaction_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'invoice_id.exists' => 'Choose one of your invoices.',
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = Invoice::whereKey($validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($invoice->status, ['cancelled', 'draft'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Payments cannot be recorded against this invoice.',
                ]);
            }

            $cashReceived = (float) $invoice->payments()->sum('amount');
            $tdsDeducted = (float) $invoice->tdsDeductions()->sum('amount');

            $remaining = (float) $invoice->total_amount
                - $cashReceived
                - $tdsDeducted;

            $amount = (float) $validated['amount'];

            if ($remaining <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot exceed the remaining invoice balance after TDS deductions.',
                ]);
            }

            Payment::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'receipt_number' => 'REC-'.strtoupper(Str::random(10)),
                'payment_date' => $validated['payment_date'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'transaction_reference' => $validated['transaction_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $totalSettled = $cashReceived + $tdsDeducted + $amount;

            $invoice->update([
                'status' => $totalSettled >= (float) $invoice->total_amount
                    ? 'paid'
                    : 'partially_paid',
            ]);
        });

        return redirect()->route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'client',
            'invoice.payments',
            'invoice.tdsDeductions',
        ]);

        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        return view('payments.edit', compact('payment'));
    }

    public function update(Request $request, Payment $payment)
    {
        return back()->with(
            'error',
            'Payment editing is disabled to preserve the financial audit trail. Use a reversal or adjustment entry.'
        );
    }

    public function destroy(Payment $payment)
    {
        return back()->with(
            'error',
            'Payments cannot be deleted. Use a reversal or refund entry to preserve the financial audit trail.'
        );
    }
}
