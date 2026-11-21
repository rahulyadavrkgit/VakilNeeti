<?php

namespace App\Http\Controllers;

use App\Models\FirmProfile;
use App\Models\TdsDeduction;
use App\Models\ProfessionalTaxRecord;
use Carbon\Carbon;

class TaxComplianceController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Indian financial year: 1 April to 31 March.
        $year = (int) $today->format('Y');
        $startYear = $today->month >= 4 ? $year : $year - 1;

        $financialYearStart = Carbon::create($startYear, 4, 1)->startOfDay();
        $financialYearEnd = Carbon::create($startYear + 1, 3, 31)->endOfDay();

        $financialYear = $startYear . '-' . substr((string) ($startYear + 1), -2);

        $firm = FirmProfile::where('is_active', true)->first();

        $tdsQuery = TdsDeduction::whereBetween('deduction_date', [
            $financialYearStart->toDateString(),
            $financialYearEnd->toDateString()
        ]);

        $tdsTotal = (clone $tdsQuery)->sum('amount');

        $tdsAwaited = (clone $tdsQuery)
            ->where('certificate_status', 'awaited')
            ->sum('amount');

        $tdsReceived = (clone $tdsQuery)
            ->where('certificate_status', 'received')
            ->sum('amount');

        $tdsVerified = (clone $tdsQuery)
            ->where('certificate_status', 'verified')
            ->sum('amount');

        $tdsAwaitedCount = (clone $tdsQuery)
            ->where('certificate_status', 'awaited')
            ->count();

        $tdsReceivedCount = (clone $tdsQuery)
            ->where('certificate_status', 'received')
            ->count();

        $tdsVerifiedCount = (clone $tdsQuery)
            ->where('certificate_status', 'verified')
            ->count();

        $tdsCertificates = (clone $tdsQuery)
            ->where('certificate_status', 'awaited')
            ->with(['client', 'invoice'])
            ->latest('deduction_date')
            ->limit(8)
            ->get();

        $professionalTaxPaid = 0;
        $professionalTaxPending = 0;
        $professionalTaxOverdue = 0;
        $professionalTaxUpcoming = 0;
        $professionalTaxRecords = collect();
        $professionalTaxByState = collect();

        if ($firm) {
            $ptQuery = ProfessionalTaxRecord::where(
                'firm_profile_id',
                $firm->id
            );

            $professionalTaxPaid = (clone $ptQuery)
                ->where('payment_status', 'paid')
                ->sum('amount');

            $professionalTaxPending = (clone $ptQuery)
                ->where('payment_status', 'pending')
                ->sum('amount');

            $professionalTaxOverdue = (clone $ptQuery)
                ->where('payment_status', 'pending')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today)
                ->sum('amount');

            $professionalTaxUpcoming = (clone $ptQuery)
                ->where('payment_status', 'pending')
                ->where(function ($query) use ($today) {
                    $query->whereDate('due_date', '>=', $today)
                        ->orWhereNull('due_date');
                })
                ->sum('amount');

            $professionalTaxRecords = (clone $ptQuery)
                ->latest('due_date')
                ->limit(8)
                ->get();

            $professionalTaxByState = (clone $ptQuery)
                ->selectRaw('state, SUM(amount) as total_amount')
                ->selectRaw("SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count")
                ->selectRaw("SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count")
                ->groupBy('state')
                ->orderBy('state')
                ->get();
        }

        return view('profile.tax-compliance', compact(
            'firm',
            'financialYear',
            'financialYearStart',
            'financialYearEnd',
            'tdsTotal',
            'tdsAwaited',
            'tdsReceived',
            'tdsVerified',
            'tdsAwaitedCount',
            'tdsReceivedCount',
            'tdsVerifiedCount',
            'tdsCertificates',
            'professionalTaxPaid',
            'professionalTaxPending',
            'professionalTaxOverdue',
            'professionalTaxUpcoming',
            'professionalTaxRecords',
            'professionalTaxByState'
        ));
    }
}