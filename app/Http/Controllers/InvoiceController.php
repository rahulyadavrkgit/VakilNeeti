<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use App\Models\CaseExpense;
use App\Models\CaseTimeEntry;
use App\Models\Client;
use App\Models\FirmProfile;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\State;
use App\Services\InvoiceCalculator;
use App\Services\InvoiceNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceCalculator $calculator,
        private InvoiceNumberGenerator $numbers,
    ) {}

    public function index()
    {
        $invoices = Invoice::with('client')
            ->latest()
            ->paginate(15);

        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $settings = $this->settings();
        $dueDays = (int) ($settings->default_payment_due_days ?? 15);
        $terms = array_search($dueDays, Invoice::PAYMENT_TERMS, true) ?: 'custom';

        $invoice = new Invoice([
            'client_id' => $request->query('client_id'),
            'legal_case_id' => $request->query('legal_case_id'),
            'invoice_date' => today(),
            'payment_terms' => $terms,
            'due_date' => today()->addDays($dueDays),
        ]);

        return $this->form($invoice);
    }

    public function store(Request $request): RedirectResponse
    {
        $invoice = $this->save($request, new Invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', "Invoice {$invoice->invoice_number} created.");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'payments', 'items', 'legalCase']);
        $firm = FirmProfile::first();

        return view('invoices.show', [
            'invoice' => $invoice,
            'firm' => $firm,
            'gst' => $this->gstSplit($invoice, $firm),
            // A link the client can open without logging in, for sharing on WhatsApp or email.
            'shareUrl' => URL::temporarySignedRoute('invoices.shared-pdf', now()->addDays(30), ['invoice' => $invoice->id]),
        ]);
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'payments', 'items', 'legalCase']);

        return $this->renderPdf($invoice, FirmProfile::first())->download($this->pdfFileName($invoice));
    }

    /**
     * The invoice PDF behind a signed, expiring link, so a client can open it from WhatsApp or email.
     */
    public function sharedPdf(int $invoice)
    {
        $invoice = Invoice::withoutGlobalScopes()->findOrFail($invoice);
        $owned = fn ($query) => $query->withoutGlobalScopes();
        $invoice->load(['client' => $owned, 'payments' => $owned, 'items' => $owned, 'legalCase' => $owned]);
        $firm = FirmProfile::withoutGlobalScopes()->where('user_id', $invoice->user_id)->first();

        return $this->renderPdf($invoice, $firm)->stream($this->pdfFileName($invoice));
    }

    private function renderPdf(Invoice $invoice, ?FirmProfile $firm): \Barryvdh\DomPDF\PDF
    {
        $gst = $this->gstSplit($invoice, $firm);

        return Pdf::loadView('invoices.pdf', compact('invoice', 'firm', 'gst'))->setPaper('a4', 'portrait');
    }

    private function pdfFileName(Invoice $invoice): string
    {
        return 'invoice-'.str_replace(['/', '\\'], '-', $invoice->invoice_number).'.pdf';
    }

    public function edit(Invoice $invoice): View
    {
        return $this->form($invoice->load('items'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->save($request, $invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->payments()->exists()) {
            return back()->with('error', 'Invoice with payments cannot be deleted.');
        }

        DB::transaction(function () use ($invoice): void {
            $this->releaseSources($invoice);
            $invoice->delete();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /**
     * Unbilled expenses and time entries of a case (plus those already on the given invoice).
     */
    public function billables(Request $request, LegalCase $case): JsonResponse
    {
        $invoiceId = $request->integer('invoice') ?: null;
        $available = fn ($query) => $query->whereNull('invoice_id')->when($invoiceId, fn ($q) => $q->orWhere('invoice_id', $invoiceId));

        $expenses = CaseExpense::query()->where('case_id', $case->id)->where($available)->orderBy('expense_date')->get()
            ->map(fn (CaseExpense $expense) => [
                'source_type' => 'expense',
                'source_id' => $expense->id,
                'description' => 'Expense: '.$expense->description.' ('.Carbon::parse($expense->expense_date)->format('d M Y').')',
                'quantity' => 1,
                'rate' => (float) $expense->amount,
            ]);

        $timeEntries = CaseTimeEntry::query()->where('case_id', $case->id)->where($available)->orderBy('entry_date')->get()
            ->map(fn (CaseTimeEntry $entry) => [
                'source_type' => 'time',
                'source_id' => $entry->id,
                'description' => 'Time: '.$entry->description.' ('.$entry->hours.'h '.$entry->minutes.'m, '.Carbon::parse($entry->entry_date)->format('d M Y').')',
                'quantity' => round($entry->hours + $entry->minutes / 60, 2),
                'rate' => (float) ($entry->hourly_rate ?? 0),
            ]);

        return response()->json(['data' => $expenses->concat($timeEntries)->values()]);
    }

    private function form(Invoice $invoice): View
    {
        $settings = $this->settings();
        $firm = FirmProfile::first();

        return view('invoices.form', [
            'invoice' => $invoice,
            'clients' => Client::query()
                ->where(fn ($query) => $query->where('status', 'active')->when($invoice->client_id, fn ($q) => $q->orWhere('id', $invoice->client_id)))
                ->orderBy('display_name')->orderBy('first_name')->get(),
            'cases' => LegalCase::query()->orderByDesc('updated_at')->get(['id', 'case_title', 'case_number']),
            'nextNumber' => $invoice->exists ? $invoice->invoice_number : $this->numbers->next(today(), $settings->invoice_prefix ?? null, $settings->invoice_number_format ?? null),
            'gstEnabled' => (bool) ($settings->gst_enabled ?? false),
            'defaultTaxRate' => (float) ($settings->default_tax_rate ?? 0),
            'defaultTaxMode' => $settings->default_tax_mode ?? 'exclusive',
            'firmState' => $firm?->state,
        ]);
    }

    private function save(Request $request, Invoice $invoice): Invoice
    {
        $request->merge([
            'client_pan' => strtoupper(trim((string) $request->input('client_pan'))) ?: null,
            'client_gstin' => strtoupper(trim((string) $request->input('client_gstin'))) ?: null,
        ]);

        $owned = fn (string $table) => Rule::exists($table, 'id')->where('user_id', auth()->id())->whereNull('deleted_at');

        $validated = $request->validate([
            'client_id' => ['required', 'integer', $owned('clients')],
            'legal_case_id' => ['nullable', 'integer', $owned('legal_cases')],
            'invoice_date' => ['required', 'date'],
            'payment_terms' => ['required', Rule::in(array_keys(Invoice::PAYMENT_TERMS))],
            'due_date' => ['nullable', 'required_if:payment_terms,custom', 'date', 'after_or_equal:invoice_date'],
            'client_pan' => ['nullable', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],
            'client_gstin' => ['nullable', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
            'place_of_supply' => ['nullable', 'string', State::nameRule()],
            'billing_address' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => [$invoice->exists ? 'required' : 'nullable', Rule::in(array_diff(Invoice::STATUSES, ['written_off']))],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'items.*.rate' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:28'],
            'items.*.tax_mode' => ['nullable', Rule::in(['inclusive', 'exclusive'])],
            'items.*.source_type' => ['nullable', Rule::in(['expense', 'time'])],
            'items.*.source_id' => ['nullable', 'integer'],
        ], [
            'client_id.exists' => 'Choose one of your clients.',
            'legal_case_id.exists' => 'Choose one of your cases.',
            'client_pan.regex' => 'Enter a valid PAN, e.g. ABCDE1234F.',
            'client_gstin.regex' => 'Enter a valid 15-character GSTIN.',
            'items.required' => 'Add at least one line item.',
            'items.*.description.required' => 'Each line item needs a description.',
        ]);

        $items = array_values($validated['items']);
        $firm = FirmProfile::first();
        $totals = $this->calculator->calculate($items, $validated['discount'] ?? 0, $firm?->state, $validated['place_of_supply'] ?? null);

        if ((float) ($validated['discount'] ?? 0) > $totals['subtotal'] + 0.001) {
            throw ValidationException::withMessages(['discount' => 'Discount cannot exceed the subtotal.']);
        }

        $invoiceDate = Carbon::parse($validated['invoice_date']);
        $dueDays = Invoice::PAYMENT_TERMS[$validated['payment_terms']];

        return DB::transaction(function () use ($invoice, $validated, $items, $totals, $invoiceDate, $dueDays): Invoice {
            $settings = $this->settings();

            $invoice->fill([
                'client_id' => $validated['client_id'],
                'legal_case_id' => $validated['legal_case_id'] ?? null,
                'invoice_date' => $invoiceDate,
                'payment_terms' => $validated['payment_terms'],
                'due_date' => $dueDays === null ? $validated['due_date'] : $invoiceDate->copy()->addDays($dueDays),
                'client_pan' => $validated['client_pan'] ?? null,
                'client_gstin' => $validated['client_gstin'] ?? null,
                'place_of_supply' => $validated['place_of_supply'] ?? null,
                'billing_address' => $validated['billing_address'] ?? null,
                'description' => $validated['description'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'status' => $validated['status'] ?? ($invoice->status ?: 'sent'),
            ]);

            if (! $invoice->exists) {
                $invoice->currency = Currency::current()->value;
                $invoice->invoice_number = $this->numbers->next($invoiceDate, $settings->invoice_prefix ?? null, $settings->invoice_number_format ?? null);
            }

            $invoice->save();

            $this->releaseSources($invoice);
            $invoice->items()->delete();

            foreach ($items as $position => $item) {
                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'rate' => $item['rate'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_mode' => $item['tax_mode'] ?? 'exclusive',
                    'source_type' => $item['source_type'] ?? null,
                    'source_id' => $item['source_id'] ?? null,
                    'position' => $position,
                ] + $totals['lines'][$position]);
            }

            $this->claimSources($invoice, $items);

            return $invoice;
        });
    }

    /**
     * Mark the case expenses and time entries billed on this invoice, so they are not billed twice.
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function claimSources(Invoice $invoice, array $items): void
    {
        foreach (['expense' => CaseExpense::class, 'time' => CaseTimeEntry::class] as $type => $model) {
            $ids = collect($items)->where('source_type', $type)->pluck('source_id')->filter()->map(fn ($id) => (int) $id);

            if ($ids->isNotEmpty()) {
                $model::query()->whereIn('id', $ids)->whereNull('invoice_id')->update(['invoice_id' => $invoice->id]);
            }
        }
    }

    private function releaseSources(Invoice $invoice): void
    {
        CaseExpense::query()->where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
        CaseTimeEntry::query()->where('invoice_id', $invoice->id)->update(['invoice_id' => null]);
    }

    /**
     * @return array{cgst: float, sgst: float, igst: float}
     */
    private function gstSplit(Invoice $invoice, ?FirmProfile $firm): array
    {
        return InvoiceCalculator::gstSplit((float) $invoice->tax_amount, $firm?->state, $invoice->place_of_supply);
    }

    /**
     * The signed-in user's invoice settings (null when none are saved yet).
     */
    private function settings(): ?object
    {
        return DB::table('invoice_settings')->where('user_id', auth()->id())->latest('id')->first();
    }
}
