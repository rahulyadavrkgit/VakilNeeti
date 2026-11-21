<?php

namespace App\Http\Controllers;

use App\Models\FirmProfile;
use App\Models\ProfessionalTaxRecord;
use Illuminate\Http\Request;

class ProfessionalTaxController extends Controller
{
    private function activeFirm(): FirmProfile
    {
        return FirmProfile::where('is_active', true)->firstOrFail();
    }

    public function index()
    {
        $firm = $this->activeFirm();

        $records = ProfessionalTaxRecord::where('firm_profile_id', $firm->id)
            ->latest('due_date')
            ->paginate(20);

        $pendingAmount = ProfessionalTaxRecord::where('firm_profile_id', $firm->id)
            ->where('payment_status', 'pending')
            ->sum('amount');

        $paidAmount = ProfessionalTaxRecord::where('firm_profile_id', $firm->id)
            ->where('payment_status', 'paid')
            ->sum('amount');

        return view('professional-tax.index', compact(
            'records',
            'pendingAmount',
            'paidAmount'
        ));
    }

    public function create()
    {
        $this->activeFirm();

        return view('professional-tax.create');
    }

    public function store(Request $request)
    {
        $firm = $this->activeFirm();

        $validated = $request->validate([
            'state' => 'required|string|max:120',
            'registration_type' => 'required|in:PTEC,PTRC,Both,Other',
            'registration_number' => 'nullable|string|max:100',
            'enrolment_number' => 'nullable|string|max:100',
            'period_type' => 'required|in:monthly,quarterly,annual,one_time',
            'tax_period' => 'required|string|max:50',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
            'payment_date' => 'nullable|date',
            'challan_number' => 'nullable|string|max:120',
            'payment_reference' => 'nullable|string|max:150',
            'payment_method' => 'nullable|in:online,bank,other',
            'return_status' => 'required|in:not_required,pending,filed',
            'return_filing_date' => 'nullable|date',
            'return_acknowledgement_number' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        if ($validated['payment_status'] === 'paid' && empty($validated['payment_date'])) {
            return back()->withErrors([
                'payment_date' => 'Payment date is required when payment status is paid.',
            ])->withInput();
        }

        if ($validated['return_status'] === 'filed' && empty($validated['return_filing_date'])) {
            return back()->withErrors([
                'return_filing_date' => 'Filing date is required when return status is filed.',
            ])->withInput();
        }

        $record = ProfessionalTaxRecord::create([
            ...$validated,
            'firm_profile_id' => $firm->id,
        ]);

        return redirect()->route('professional-tax.show', $record)
            ->with('success', 'Professional Tax record created successfully.');
    }

    public function show(ProfessionalTaxRecord $professionalTax)
    {
        $firm = $this->activeFirm();

        abort_unless(
            $professionalTax->firm_profile_id === $firm->id,
            404
        );

        return view('professional-tax.show', [
            'record' => $professionalTax,
        ]);
    }

    public function edit(ProfessionalTaxRecord $professionalTax)
    {
        $firm = $this->activeFirm();

        abort_unless(
            $professionalTax->firm_profile_id === $firm->id,
            404
        );

        return view('professional-tax.edit', [
            'record' => $professionalTax,
        ]);
    }

    public function update(Request $request, ProfessionalTaxRecord $professionalTax)
    {
        $firm = $this->activeFirm();

        abort_unless(
            $professionalTax->firm_profile_id === $firm->id,
            404
        );

        $validated = $request->validate([
            'state' => 'required|string|max:120',
            'registration_type' => 'required|in:PTEC,PTRC,Both,Other',
            'registration_number' => 'nullable|string|max:100',
            'enrolment_number' => 'nullable|string|max:100',
            'period_type' => 'required|in:monthly,quarterly,annual,one_time',
            'tax_period' => 'required|string|max:50',
            'due_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
            'payment_date' => 'nullable|date',
            'challan_number' => 'nullable|string|max:120',
            'payment_reference' => 'nullable|string|max:150',
            'payment_method' => 'nullable|in:online,bank,other',
            'return_status' => 'required|in:not_required,pending,filed',
            'return_filing_date' => 'nullable|date',
            'return_acknowledgement_number' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        if ($validated['payment_status'] === 'paid' && empty($validated['payment_date'])) {
            return back()->withErrors([
                'payment_date' => 'Payment date is required when payment status is paid.',
            ])->withInput();
        }

        if ($validated['return_status'] === 'filed' && empty($validated['return_filing_date'])) {
            return back()->withErrors([
                'return_filing_date' => 'Filing date is required when return status is filed.',
            ])->withInput();
        }

        $professionalTax->update($validated);

        return redirect()->route('professional-tax.show', $professionalTax)
            ->with('success', 'Professional Tax record updated successfully.');
    }
}