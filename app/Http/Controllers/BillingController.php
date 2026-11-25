<?php

namespace App\Http\Controllers;

use App\Models\CaseExpense;
use App\Models\CaseTimeEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LegalCase;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BillingController extends Controller
{
    /**
     * @var array<string, string>
     */
    public const PERIODS = [
        '7' => 'Last 7 days',
        '15' => 'Last 15 days',
        '30' => 'Last 30 days',
        'week' => 'This week',
        'month' => 'This month',
        'year' => 'This year',
        'all' => 'All time',
        'custom' => 'Custom',
    ];

    /**
     * @var list<string>
     */
    public const PAYMENT_METHODS = ['cash', 'upi', 'bank_transfer', 'cheque', 'card', 'other'];

    public function index(Request $request): View
    {
        [$from, $to, $period] = $this->period($request);
        $inPeriod = fn ($query, string $column) => $query
            ->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn ($q) => $q->whereDate($column, '<=', $to));

        $periodInvoices = $inPeriod(Invoice::query(), 'invoice_date')->get();
        $periodPayments = $inPeriod(Payment::query(), 'payment_date')->get();
        $openInvoices = $this->openInvoices();

        $clients = Client::query()
            ->withCount(['caseParties as cases_count' => fn ($q) => $q->where('is_client', true)])
            ->orderBy('display_name')
            ->get()
            ->map(fn (Client $client) => [
                'client' => $client,
                'cases' => $client->cases_count,
                'invoiced' => (float) $periodInvoices->where('client_id', $client->id)->sum('total_amount'),
                'received' => (float) $periodPayments->where('client_id', $client->id)->sum('amount'),
            ])
            ->filter(fn (array $row) => $row['cases'] > 0 || $row['invoiced'] > 0 || $row['received'] > 0)
            ->values();

        return view('billing.index', [
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'stats' => [
                'invoices_raised' => $periodInvoices->count(),
                'invoice_amount' => (float) $periodInvoices->sum('total_amount'),
                'payment_received' => (float) $periodPayments->sum('amount'),
                'total_expenses' => (float) $inPeriod(CaseExpense::query(), 'expense_date')->sum('amount'),
                'payment_due' => (float) $openInvoices->reject(fn (Invoice $invoice) => $this->isOverdue($invoice))->sum(fn (Invoice $invoice) => $invoice->balanceDue()),
                'payment_overdue' => (float) $openInvoices->filter(fn (Invoice $invoice) => $this->isOverdue($invoice))->sum(fn (Invoice $invoice) => $invoice->balanceDue()),
            ],
            'clientRows' => $clients,
        ]);
    }

    public function overdues(Request $request): View
    {
        $search = trim($request->string('q')->limit(100, '')->toString());
        $matches = fn (Invoice $invoice): bool => $search === ''
            || Str::contains($invoice->invoice_number, $search, ignoreCase: true)
            || Str::contains($invoice->client?->displayName() ?? '', $search, ignoreCase: true);
        $overdue = $this->openInvoices()->filter(fn (Invoice $invoice) => $this->isOverdue($invoice))->filter($matches)->sortBy('due_date');
        $paginate = function (Collection $invoices, string $pageName, string $section) use ($request): LengthAwarePaginator {
            $page = LengthAwarePaginator::resolveCurrentPage($pageName);

            return (new LengthAwarePaginator($invoices->forPage($page, 15)->values(), $invoices->count(), 15, $page, [
                'path' => $request->url(), 'pageName' => $pageName,
            ]))->withQueryString()->fragment($section);
        };

        return view('billing.overdues', [
            'search' => $search,
            'pending' => $paginate($overdue->filter(fn (Invoice $invoice) => $invoice->amountPaid() <= 0), 'pending_page', 'pending'),
            'partial' => $paginate($overdue->filter(fn (Invoice $invoice) => $invoice->amountPaid() > 0), 'partial_page', 'partly-paid'),
            'writtenOff' => $paginate(Invoice::with(['client', 'payments'])->whereNotNull('written_off_at')->latest('written_off_at')->get()->filter($matches), 'written_off_page', 'written-off'),
        ]);
    }

    public function writeOff(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $request->validate(['write_off_reason' => ['nullable', 'string', 'max:255']]);

        $invoice->update([
            'written_off_at' => now(),
            'write_off_reason' => $validated['write_off_reason'] ?? null,
            'status' => 'written_off',
        ]);

        return back()->with('success', "Invoice {$invoice->invoice_number} written off.");
    }

    public function undoWriteOff(Invoice $invoice): RedirectResponse
    {
        $invoice->update([
            'written_off_at' => null,
            'write_off_reason' => null,
            'status' => $invoice->payments()->exists() ? 'partially_paid' : 'sent',
        ]);

        return back()->with('success', "Write-off of {$invoice->invoice_number} undone.");
    }

    public function timeSheet(Request $request): View
    {
        $entries = $this->filteredTime($request)->with('legalCase:id,case_title')->latest('entry_date')->paginate(25)->withQueryString();

        return view('billing.time', [
            'entries' => $entries,
            'cases' => LegalCase::query()->orderBy('case_title')->get(['id', 'case_title']),
            'totalMinutes' => (int) $this->filteredTime($request)->get()->sum(fn ($entry) => $entry->hours * 60 + $entry->minutes),
        ]);
    }

    public function storeTimeEntry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_id' => ['required', 'integer', $this->ownedCase()],
            'description' => ['required', 'string', 'max:500'],
            'entry_date' => ['required', 'date'],
            'hours' => ['required', 'integer', 'min:0', 'max:24'],
            'minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ], ['case_id.exists' => 'Choose one of your cases.']);

        if ((int) $validated['hours'] === 0 && (int) $validated['minutes'] === 0) {
            return back()->withErrors(['minutes' => 'Enter the time spent.'])->withInput();
        }

        CaseTimeEntry::create($validated);

        return redirect()->route('billing.time')->with('success', 'Time entry added.');
    }

    public function exportTime(Request $request): StreamedResponse
    {
        $entries = $this->filteredTime($request)->with('legalCase:id,case_title')->latest('entry_date')->get();

        return $this->csv('time-entries', ['Date', 'Case', 'Description', 'Hours', 'Minutes', 'Hourly rate', 'Billed'], $entries->map(fn ($entry) => [
            Carbon::parse($entry->entry_date)->format('d-m-Y'),
            $entry->legalCase?->case_title,
            $entry->description,
            $entry->hours,
            $entry->minutes,
            $entry->hourly_rate,
            $entry->invoice_id ? 'Yes' : 'No',
        ]));
    }

    public function expenses(Request $request): View
    {
        return view('billing.expenses', [
            'expenses' => $this->filteredExpenses($request)->with('legalCase:id,case_title')->latest('expense_date')->paginate(25)->withQueryString(),
            'cases' => LegalCase::query()->orderBy('case_title')->get(['id', 'case_title']),
            'total' => (float) $this->filteredExpenses($request)->sum('amount'),
            'methods' => self::PAYMENT_METHODS,
        ]);
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_id' => ['required', 'integer', $this->ownedCase()],
            'description' => ['required', 'string', 'max:500'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'payment_method' => ['nullable', Rule::in(self::PAYMENT_METHODS)],
            'receipt' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'case_id.exists' => 'Choose one of your cases.',
            'receipt.mimes' => 'Receipts can be PDF, JPG or PNG.',
        ]);

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('expense-receipts', 'local');
            $validated['receipt_name'] = Str::limit($request->file('receipt')->getClientOriginalName(), 255, '');
        }

        unset($validated['receipt']);
        CaseExpense::create($validated);

        return redirect()->route('billing.expenses')->with('success', 'Expense added.');
    }

    public function downloadReceipt(CaseExpense $expense)
    {
        abort_unless($expense->receipt_path && Storage::disk('local')->exists($expense->receipt_path), 404);

        return Storage::disk('local')->download($expense->receipt_path, $expense->receipt_name ?: 'receipt');
    }

    public function exportExpenses(Request $request): StreamedResponse
    {
        $expenses = $this->filteredExpenses($request)->with('legalCase:id,case_title')->latest('expense_date')->get();

        return $this->csv('expenses', ['Date', 'Case', 'Description', 'Amount', 'Payment method', 'Billed'], $expenses->map(fn ($expense) => [
            Carbon::parse($expense->expense_date)->format('d-m-Y'),
            $expense->legalCase?->case_title,
            $expense->description,
            $expense->amount,
            $expense->payment_method ? str_replace('_', ' ', $expense->payment_method) : '',
            $expense->invoice_id ? 'Yes' : 'No',
        ]));
    }

    public function exportPayments(): StreamedResponse
    {
        $payments = Payment::with(['client', 'invoice.payments'])->latest('payment_date')->get();

        return $this->csv('payments', ['Date', 'Receipt No.', 'Invoice No.', 'Client', 'Method', 'Amount', 'Invoice outstanding'], $payments->map(fn (Payment $payment) => [
            Carbon::parse($payment->payment_date)->format('d-m-Y'),
            $payment->receipt_number,
            $payment->invoice?->invoice_number,
            $payment->client?->displayName(),
            str_replace('_', ' ', (string) $payment->payment_method),
            $payment->amount,
            $payment->invoice ? number_format($payment->invoice->balanceDue(), 2, '.', '') : '',
        ]));
    }

    /**
     * Unpaid invoices that still have a balance (not cancelled, draft or written off).
     *
     * @return Collection<int, Invoice>
     */
    private function openInvoices(): Collection
    {
        return Invoice::with(['client', 'payments'])
            ->whereNull('written_off_at')
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->balanceDue() > 0)
            ->values();
    }

    private function isOverdue(Invoice $invoice): bool
    {
        return $invoice->due_date !== null && $invoice->due_date->lt(today());
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function period(Request $request): array
    {
        $request->validate([
            'period' => ['nullable', Rule::in(array_keys(self::PERIODS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $period = $request->input('period', '30');

        return match ($period) {
            '7', '15', '30' => [today()->subDays((int) $period - 1), today(), $period],
            'week' => [today()->startOfWeek(), today()->endOfWeek(), $period],
            'month' => [today()->startOfMonth(), today()->endOfMonth(), $period],
            'year' => [today()->startOfYear(), today()->endOfYear(), $period],
            'custom' => [$request->date('from'), $request->date('to'), $period],
            default => [null, null, 'all'],
        };
    }

    private function filteredTime(Request $request)
    {
        return CaseTimeEntry::query()
            ->when($request->integer('case_id'), fn ($q, $caseId) => $q->where('case_id', $caseId))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('entry_date', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('entry_date', '<=', $to));
    }

    private function filteredExpenses(Request $request)
    {
        return CaseExpense::query()
            ->when($request->integer('case_id'), fn ($q, $caseId) => $q->where('case_id', $caseId))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('expense_date', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('expense_date', '<=', $to));
    }

    private function ownedCase(): Exists
    {
        return Rule::exists('legal_cases', 'id')->where('user_id', auth()->id())->whereNull('deleted_at');
    }

    /**
     * @param  list<string>  $header
     * @param  Collection<int, list<mixed>>  $rows
     */
    private function csv(string $name, array $header, Collection $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, $header);
            $rows->each(fn (array $row) => fputcsv($output, $row));
            fclose($output);
        }, $name.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
