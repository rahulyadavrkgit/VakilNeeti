<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <title>Payment Receipt | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="mx-auto max-w-4xl p-8">

    <div class="mb-6 flex justify-between">
        <a href="{{ route('payments.index') }}" class="text-blue-600 hover:underline">
            â† Back to Payments
        </a>

        <button onclick="window.print()"
                class="rounded-lg bg-slate-800 px-5 py-2 text-white">
            Print Receipt
        </button>
    </div>

    <div class="rounded-xl bg-white p-10 shadow-sm">

        <div class="flex justify-between border-b pb-8">
            <div>
                <div class="mb-2"><img src="{{ asset('images/vakilneeti-logo.png') }}" alt="VakilNeeti" class="h-12 w-auto max-w-[220px] rounded bg-white px-2 py-1 object-contain"></div>
                <p class="mt-2 text-slate-500">Legal Practice Management</p>
            </div>

            <div class="text-right">
                <h2 class="text-2xl font-bold">PAYMENT RECEIPT</h2>
                <p class="mt-2 font-semibold">{{ $payment->receipt_number }}</p>
                <p class="text-slate-500">
                    Date: {{ $payment->payment_date->format('d-m-Y') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 border-b py-8">
            <div>
                <p class="mb-2 text-sm font-semibold uppercase text-slate-500">
                    Received From
                </p>

                <h3 class="text-lg font-bold">
                    {{ $payment->client->display_name ?: trim($payment->client->first_name.' '.$payment->client->last_name) }}
                </h3>

                <p>{{ $payment->client->address }}</p>
                <p>{{ $payment->client->mobile }}</p>
                <p>{{ $payment->client->email }}</p>
            </div>

            <div>
                <p class="mb-2 text-sm font-semibold uppercase text-slate-500">
                    Invoice Details
                </p>

                <p class="font-semibold">{{ $payment->invoice->invoice_number }}</p>

                <p class="mt-2 text-slate-600">
                    Invoice Date:
                    {{ $payment->invoice->invoice_date->format('d-m-Y') }}
                </p>

                <p class="mt-2 text-slate-600">
                    Payment Method:
                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                </p>

                @if($payment->transaction_reference)
                    <p class="mt-2 text-slate-600">
                        Transaction Reference: {{ $payment->transaction_reference }}
                    </p>
                @endif
            </div>
        </div>

        <div class="py-10">
            <p class="text-slate-500">Amount Received</p>

            <h2 class="mt-3 text-4xl font-bold text-green-700">
                â‚¹{{ number_format($payment->amount, 2) }}
            </h2>
        </div>

        <div class="space-y-3 border-t pt-6">
            <div class="flex justify-between">
                <span>Invoice Total</span>
                <span>â‚¹{{ number_format($payment->invoice->total_amount, 2) }}</span>
            </div>

            <div class="flex justify-between">
                <span>Total Received Against Invoice</span>
                <span>
                    â‚¹{{ number_format($payment->invoice->payments->sum('amount'), 2) }}
                </span>
            </div>

            <div class="flex justify-between text-lg font-bold">
                <span>Outstanding Balance</span>
                <span>
                    â‚¹{{ number_format(max(0, $payment->invoice->total_amount - $payment->invoice->payments->sum('amount')), 2) }}
                </span>
            </div>
        </div>

        @if($payment->notes)
            <div class="mt-8 border-t pt-6">
                <h3 class="font-bold">Notes</h3>
                <p class="mt-2 whitespace-pre-line text-slate-600">
                    {{ $payment->notes }}
                </p>
            </div>
        @endif

        <div class="mt-12 border-t pt-6 text-center text-sm text-slate-500">
            This receipt was generated through VakilNeeti.
        </div>

    </div>
</div>

<style>
@media print {
    body { background: white !important; }
    button, a { display: none !important; }
    .shadow-sm { box-shadow: none !important; }
}
</style>

</body>
</html>

