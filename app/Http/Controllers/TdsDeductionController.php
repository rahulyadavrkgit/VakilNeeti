<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\TdsDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class TdsDeductionController extends Controller
{
    public function index(Request $request)
    {
        $currentYear = (int) now()->format('Y');
        $startYear = now()->month >= 4 ? $currentYear : $currentYear - 1;

        $financialYears = [];

        for ($i = 0; $i < 6; $i++) {
            $start = $startYear - $i;
            $financialYears[] = $start . '-' . substr((string) ($start + 1), -2);
        }

        $selectedYear = $request->input('financial_year');
        $certificateStatus = $request->input('certificate_status');
        $creditStatus = $request->input('credit_status');
        $search = trim((string) $request->input('search'));

        $query = TdsDeduction::with(['invoice', 'client']);

        if ($selectedYear && in_array($selectedYear, $financialYears, true)) {
            $start = (int) substr($selectedYear, 0, 4);

            $query->whereBetween('deduction_date', [
                Carbon::create($start, 4, 1)->toDateString(),
                Carbon::create($start + 1, 3, 31)->toDateString(),
            ]);
        }

        if (in_array($certificateStatus, ['awaited', 'received', 'verified'], true)) {
            $query->where('certificate_status', $certificateStatus);
        }

        if (in_array($creditStatus, ['not_checked', 'matched', 'not_reflected', 'mismatch'], true)) {
            $query->where('credit_status', $creditStatus);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('deduction_reference', 'like', '%' . $search . '%')
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('display_name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('invoice', function ($invoiceQuery) use ($search) {
                        $invoiceQuery->where('invoice_number', 'like', '%' . $search . '%');
                    });
            });
        }

        $summaryQuery = clone $query;

        $totalAmount = (clone $summaryQuery)->sum('amount');
        $totalRecords = (clone $summaryQuery)->count();

        $matchedAmount = (clone $summaryQuery)
            ->where('credit_status', 'matched')->sum('amount');

        $notReflectedAmount = (clone $summaryQuery)
            ->where('credit_status', 'not_reflected')->sum('amount');

        $mismatchAmount = (clone $summaryQuery)
            ->where('credit_status', 'mismatch')->sum('amount');

        $uncheckedAmount = (clone $summaryQuery)
            ->where(function ($q) {
                $q->whereNull('credit_status')
                    ->orWhere('credit_status', 'not_checked');
            })->sum('amount');

        $deductions = $query
            ->latest('deduction_date')
            ->paginate(20)
            ->withQueryString();

        return view('tds.index', compact(
            'deductions',
            'financialYears',
            'selectedYear',
            'certificateStatus',
            'creditStatus',
            'search',
            'totalAmount',
            'totalRecords',
            'matchedAmount',
            'notReflectedAmount',
            'mismatchAmount',
            'uncheckedAmount'
        ));
    }

    public function create(Request $request)
    {
        $selectedClientId = $request->query('client_id');

        $invoices = Invoice::with(['client', 'payments', 'tdsDeductions'])
            ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
            ->when($selectedClientId, function ($query) use ($selectedClientId) {
                $query->where('client_id', $selectedClientId);
            })
            ->latest()
            ->get()
            ->filter(function ($invoice) {
                $cashReceived = (float) $invoice->payments->sum('amount');
                $tdsDeducted = (float) $invoice->tdsDeductions->sum('amount');

                return (
                    (float) $invoice->total_amount
                    - $cashReceived
                    - $tdsDeducted
                ) > 0;
            });

        return view('tds.create', compact('invoices', 'selectedClientId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'deduction_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'tds_section' => 'nullable|string|max:30',
            'deduction_rate' => 'nullable|numeric|min:0|max:100',
            'deduction_base_amount' => 'nullable|numeric|min:0',
            'certificate_number' => 'nullable|string|max:100',
            'certificate_date' => 'nullable|date',
            'financial_year' => 'nullable|string|max:10',
            'certificate_status' => 'required|in:awaited,received,verified',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $invoice = Invoice::whereKey($validated['invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($invoice->status, ['cancelled', 'draft'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'TDS cannot be recorded against this invoice.',
                ]);
            }

            $cashReceived = (float) $invoice->payments()->sum('amount');
            $tdsAlreadyDeducted = (float) $invoice->tdsDeductions()->sum('amount');

            $remaining = (float) $invoice->total_amount
                - $cashReceived
                - $tdsAlreadyDeducted;

            $amount = (float) $validated['amount'];

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => 'TDS deduction cannot exceed the remaining invoice balance.',
                ]);
            }

            TdsDeduction::create([
                ...$validated,
                'client_id' => $invoice->client_id,
                'deduction_reference' => 'TDS-' . strtoupper(Str::random(12)),
                'credit_status' => 'not_checked',
            ]);

            $settled = $cashReceived + $tdsAlreadyDeducted + $amount;

            $invoice->update([
                'status' => $settled >= (float) $invoice->total_amount
                    ? 'paid'
                    : 'partially_paid',
            ]);
        });

        return redirect()->route('tds.index')
            ->with('success', 'TDS deduction recorded successfully.');
    }

    public function show(TdsDeduction $tdsDeduction)
    {
        $tdsDeduction->load(['invoice', 'client', 'followUps']);

        return view('tds.show', compact('tdsDeduction'));
    }

    public function updateReconciliation(Request $request, TdsDeduction $tdsDeduction)
    {
        $validated = $request->validate([
            'credit_status' => 'required|in:not_checked,matched,not_reflected,mismatch',
            'credit_checked_at' => 'nullable|required_unless:credit_status,not_checked|date',
            'credit_reference' => 'nullable|string|max:150',
            'credit_notes' => 'nullable|string',
        ]);

        if ($validated['credit_status'] === 'not_checked') {
            $validated['credit_checked_at'] = null;
        }

        $tdsDeduction->update($validated);

        return redirect()->route('tds.show', $tdsDeduction)
            ->with('success', 'TDS credit reconciliation updated.');
    }

    public function storeFollowUp(Request $request, TdsDeduction $tdsDeduction)
    {
        $validated = $request->validate([
            'contact_date' => 'required|date',
            'contact_method' => 'required|in:email,phone,whatsapp,letter,other',
            'follow_up_status' => 'required|in:completed,pending',
            'outcome' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'next_follow_up_date' => 'nullable|date|after_or_equal:contact_date',
        ]);

        $tdsDeduction->followUps()->create($validated);

        return redirect()->route('tds.show', $tdsDeduction)
            ->with('success', 'TDS follow-up entry recorded successfully.');
    }

    public function dueFollowUps()
    {
        $today = now()->toDateString();

        $followUps = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ])
        ->where('follow_up_status', 'pending')
        ->where(function ($query) use ($today) {
            $query->whereDate('next_follow_up_date', '<=', $today)
                ->orWhere(function ($query) use ($today) {
                    $query->whereNull('next_follow_up_date')
                        ->whereDate('contact_date', '<=', $today);
                });
        })
        ->orderByRaw('CASE WHEN next_follow_up_date IS NULL THEN 1 ELSE 0 END')
        ->orderBy('next_follow_up_date')
        ->paginate(25);

        return view('tds.follow-ups-due', compact('followUps', 'today'));
    }

    public function completeFollowUp(\App\Models\TdsFollowUp $tdsFollowUp)
    {
        $tdsFollowUp->update([
            'follow_up_status' => 'completed',
        ]);

        return redirect()->route('tds.follow-ups.due')
            ->with('success', 'Follow-up marked as completed.');
    }
    public function upcomingFollowUps()
    {
        $today = now()->toDateString();

        $followUps = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ])
        ->where('follow_up_status', 'pending')
        ->whereDate('next_follow_up_date', '>', $today)
        ->orderBy('next_follow_up_date')
        ->paginate(25);

        return view('tds.follow-ups-upcoming', compact('followUps', 'today'));
    }
    public function followUpDashboard()
    {
        $today = now()->toDateString();

        $totalFollowUps = \App\Models\TdsFollowUp::count();

        $completedFollowUps = \App\Models\TdsFollowUp::where(
            'follow_up_status',
            'completed'
        )->count();

        $pendingFollowUps = \App\Models\TdsFollowUp::where(
            'follow_up_status',
            'pending'
        )->count();

        $overdueFollowUps = \App\Models\TdsFollowUp::where(
            'follow_up_status',
            'pending'
        )->whereDate('next_follow_up_date', '<', $today)->count();

        $completionRate = $totalFollowUps > 0
            ? round(($completedFollowUps / $totalFollowUps) * 100, 1)
            : 0;

        $overdue = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ])->where('follow_up_status', 'pending')
          ->whereDate('next_follow_up_date', '<', $today)
          ->orderBy('next_follow_up_date')
          ->get();

        $dueToday = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ])->where('follow_up_status', 'pending')
          ->whereDate('next_follow_up_date', $today)
          ->orderBy('next_follow_up_date')
          ->get();

        $upcoming = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ])->where('follow_up_status', 'pending')
          ->whereDate('next_follow_up_date', '>', $today)
          ->orderBy('next_follow_up_date')
          ->limit(10)
          ->get();

        // Variables required by the dashboard Blade view.
        $overdueCount = $overdueFollowUps;
        $dueTodayCount = $dueToday->count();
        $upcomingCount = \App\Models\TdsFollowUp::where('follow_up_status', 'pending')
            ->whereDate('next_follow_up_date', '>', $today)
            ->count();
        $upcomingFollowUps = $upcoming;
        return view('tds.follow-up-dashboard', compact(
            'totalFollowUps',
            'completedFollowUps',
            'pendingFollowUps',
            'overdueFollowUps',
            'completionRate',
            'overdueCount', 'dueTodayCount', 'upcomingCount', 'upcomingFollowUps',
            'overdue',
            'dueToday',
            'upcoming'
        ));
    }
    public function followUpHistory(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\TdsFollowUp::with([
            'tdsDeduction.client',
            'tdsDeduction.invoice',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->whereHas('tdsDeduction', function ($deductionQuery) use ($search) {
                $deductionQuery->where('deduction_reference', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('display_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('follow_up_status', $request->status);
        }

        if ($request->filled('method')) {
            $query->where('contact_method', $request->method);
        }

        if ($request->filled('from')) {
            $query->whereDate('contact_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('contact_date', '<=', $request->to);
        }

        $followUps = $query
            ->orderByDesc('contact_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('tds.follow-up-history', compact('followUps'));
    }
}
