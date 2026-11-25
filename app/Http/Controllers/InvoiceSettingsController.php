<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceSettingsController extends Controller
{
    private function activeFirm()
    {
        return DB::table('firm_profiles')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();
    }

    public function index()
    {
        $firm = $this->activeFirm();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index', array_filter(['next' => 'invoice-settings', 'setup' => request('setup')]))
                ->with('error', 'Add your office name and address first. Your bank and UPI details come next.');
        }

        $settings = DB::table('invoice_settings')
            ->where('user_id', auth()->id())
            ->where('firm_profile_id', $firm->id)
            ->first();

        return view('profile.invoice-settings', compact('firm', 'settings'));
    }

    public function update(Request $request)
    {
        $firm = $this->activeFirm();

        if (! $firm) {
            return redirect()
                ->route('firm-profile.index', array_filter(['next' => 'invoice-settings', 'setup' => request('setup')]))
                ->with('error', 'Add your office name and address first. Your bank and UPI details come next.');
        }

        $validated = $request->validate([
            'gst_registration_type' => [
                'required',
                Rule::in(['regular', 'composition', 'unregistered']),
            ],
            'default_tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_sac_code' => ['nullable', 'string', 'max:30'],
            'default_tax_mode' => [
                'required',
                Rule::in(['exclusive', 'inclusive']),
            ],
            'invoice_prefix' => ['required', 'string', 'max:30'],
            'invoice_number_format' => ['required', 'string', 'max:50'],
            'default_payment_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_holder_name' => ['nullable', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'ifsc_code' => ['nullable', 'string', 'max:20'],
            'branch_name' => ['nullable', 'string', 'max:150'],
            'upi_id' => ['nullable', 'string', 'max:150'],
            'default_invoice_terms' => ['nullable', 'string', 'max:10000'],
            'default_invoice_notes' => ['nullable', 'string', 'max:10000'],
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'tds_section' => ['nullable', 'string', 'max:30'],
            'tds_default_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tan_number' => ['nullable', 'string', 'max:20'],
            'professional_tax_state' => ['nullable', 'string', 'max:120'],
            'professional_tax_registration_number' => ['nullable', 'string', 'max:100'],
            'professional_tax_enrolment_number' => ['nullable', 'string', 'max:100'],
            'professional_tax_frequency' => [
                'required',
                Rule::in(['monthly', 'quarterly', 'annual']),
            ],
            'professional_tax_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $gstEnabled = $request->boolean('gst_enabled');

        if ($validated['gst_registration_type'] === 'unregistered') {
            $gstEnabled = false;
        }

        if ($validated['gst_registration_type'] === 'composition') {
            $gstEnabled = false;
        }

        $data = array_merge($validated, [
            'gst_enabled' => $gstEnabled,
            'reverse_charge_applicable' => $request->boolean('reverse_charge_applicable'),
            'tds_enabled' => $request->boolean('tds_enabled'),
            'professional_tax_applicable' => $request->boolean('professional_tax_applicable'),
            'updated_at' => now(),
        ]);

        DB::table('invoice_settings')
            ->where('user_id', auth()->id())->updateOrInsert(
                ['firm_profile_id' => $firm->id, 'user_id' => auth()->id()],
                array_merge($data, [
                    'created_at' => DB::raw(
                        'COALESCE(created_at, CURRENT_TIMESTAMP)'
                    ),
                ])
            );

        // Guided profile setup: carry on to the next unfinished step.
        if ($request->boolean('setup')) {
            return redirect()->route('profile.setup.next', ['after' => 'billing'])->with('success', 'Bank and invoice details saved.');
        }

        return redirect()
            ->route('profile.invoice-settings.index')
            ->with('success', 'Invoice and GST settings saved successfully.');
    }
}
