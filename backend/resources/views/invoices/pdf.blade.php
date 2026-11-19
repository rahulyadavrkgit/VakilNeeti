<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 35px 40px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .brand {
            font-size: 25px;
            font-weight: bold;
            color: #1e3a8a;
        }

        .subtitle {
            color: #64748b;
            font-size: 10px;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
            color: #1e3a8a;
        }

        .section {
            margin-top: 20px;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td, th {
            padding: 9px 8px;
            vertical-align: top;
        }

        .details td {
            width: 50%;
        }

        .label {
            color: #64748b;
            font-size: 9px;
        }

        .value {
            font-weight: bold;
        }

        .items th {
            background: #1e3a8a;
            color: white;
            text-align: left;
        }

        .items td {
            border-bottom: 1px solid #e2e8f0;
        }

        .right {
            text-align: right;
        }

        .totals {
            width: 55%;
            margin-left: auto;
            margin-top: 15px;
        }

        .totals td {
            border-bottom: 1px solid #e2e8f0;
        }

        .grand-total td {
            background: #eff6ff;
            color: #1e3a8a;
            font-size: 14px;
            font-weight: bold;
        }

        .payment-table th {
            background: #f1f5f9;
            text-align: left;
        }

        .payment-table td, .payment-table th {
            border: 1px solid #e2e8f0;
        }

        .notes {
            background: #f8fafc;
            padding: 12px;
            border: 1px solid #e2e8f0;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            text-align: center;
            color: #64748b;
            font-size: 9px;
        }

        .declaration {
            color: #334155;
            font-weight: bold;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>

@php
    $currency = $invoice->currency === 'INR' ? 'Rs. ' : ($invoice->currency . ' ');
    $client = $invoice->client;
    $clientName = $client->display_name
        ?: trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name);
@endphp

<div class="header">
    <table>
        <tr>
            <td>
                <div class="brand">{{ $firm->firm_name ?? 'Firm Name' }}</div>

                @if($firm && $firm->proprietor_name)
                    <div class="subtitle">Proprietor: {{ $firm->proprietor_name }}</div>
                @endif

                @if($firm && $firm->address)
                    <div>{{ $firm->address }}</div>
                @endif

                @if($firm)
                    <div class="subtitle">
                        {{ collect([$firm->city, $firm->district, $firm->state, $firm->pincode])->filter()->implode(', ') }}
                    </div>

                    @if($firm->gstin)
                        <div>GSTIN: {{ $firm->gstin }}</div>
                    @endif

                    @if($firm->email)
                        <div>Email: {{ $firm->email }}</div>
                    @endif

                    @if($firm->phone)
                        <div>Phone: {{ $firm->phone }}</div>
                    @endif
                @endif
            </td>
            <td class="right">
                <div class="invoice-title">INVOICE</div>
                <div><strong>{{ $invoice->invoice_number }}</strong></div>
                <div class="subtitle">Status: {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <table class="details">
        <tr>
            <td>
                <div class="section-title">BILL TO</div>
                <div class="value">{{ $clientName }}</div>

                @if($client->organisation_name)
                    <div>{{ $client->organisation_name }}</div>
                @endif

                @if($client->mobile)
                    <div>Mobile: {{ $client->mobile }}</div>
                @endif

                @if($client->email)
                    <div>Email: {{ $client->email }}</div>
                @endif

                @if($client->address)
                    <div>{{ $client->address }}</div>
                @endif

                <div>
                    {{ collect([$client->city, $client->district, $client->state, $client->pin_code])->filter()->implode(', ') }}
                </div>
            </td>
            <td>
                <div class="section-title">INVOICE DETAILS</div>
                <div>
                    <span class="label">Invoice Date:</span>
                    <span class="value">{{ $invoice->invoice_date?->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="label">Due Date:</span>
                    <span class="value">{{ $invoice->due_date?->format('d M Y') ?? 'Not specified' }}</span>
                </div>
                <div>
                    <span class="label">Currency:</span>
                    <span class="value">{{ $invoice->currency }}</span>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">SERVICE DETAILS</div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->description ?: 'Professional services' }}</td>
                <td class="right">{{ $currency }}{{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="right">{{ $currency }}{{ number_format((float) $invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="right">{{ $currency }}{{ number_format((float) $invoice->discount, 2) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="right">{{ $currency }}{{ number_format((float) $invoice->tax_amount, 2) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Amount</td>
            <td class="right">{{ $currency }}{{ number_format((float) $invoice->total_amount, 2) }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">PAYMENT HISTORY</div>

    @if($invoice->payments->isNotEmpty())
        <table class="payment-table">
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Method</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments as $payment)
                    <tr>
                        <td>{{ $payment->receipt_number ?: '—' }}</td>
                        <td>{{ $payment->payment_date?->format('d M Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method ?? '—')) }}</td>
                        <td class="right">{{ $currency }}{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div>No payments recorded against this invoice.</div>
    @endif
</div>

@if($invoice->notes)
    <div class="section">
        <div class="section-title">NOTES</div>
        <div class="notes">{{ $invoice->notes }}</div>
    </div>
@endif

<div class="footer">
    <div class="declaration">
        This is a computer-generated invoice and does not require a physical signature.
    </div>
    Generated through VakilNeeti.
</div>

</body>
</html>