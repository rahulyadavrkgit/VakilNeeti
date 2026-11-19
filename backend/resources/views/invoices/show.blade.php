<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Details | VakilNeeti</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-100 text-slate-900">

<div class="mx-auto max-w-5xl p-8">

    <div class="mb-6 flex justify-between">
        <a href="{{ route('invoices.index') }}" class="text-blue-600 hover:underline">
            Ã¢â€ Â Back to Invoices
        </a>

        <div class="flex flex-wrap gap-2">
    <a href="{{ route('invoices.pdf', $invoice) }}"
       target="_blank"
       class="rounded-lg bg-blue-700 px-5 py-2 text-white hover:bg-blue-800">
        Download PDF
    </a>

    <button type="button"
            id="shareInvoiceButton"
            class="rounded-lg bg-green-600 px-5 py-2 text-white hover:bg-green-700">
        Share Invoice
    </button>

    <button onclick="window.print()"
            class="rounded-lg bg-slate-800 px-5 py-2 text-white">
        Print Invoice
    </button>
</div>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-lg bg-green-100 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-xl bg-white p-10 shadow-sm">

        <div class="flex justify-between border-b pb-8">
            <div>
                <h1 class="text-3xl font-bold text-blue-700">
    {{ $firm->firm_name ?? 'Firm Name' }}
</h1>

@if($firm)
    @if($firm->proprietor_name)
        <p class="mt-2 text-slate-600">Proprietor: {{ $firm->proprietor_name }}</p>
    @endif
    @if($firm->address)
        <p class="text-slate-600">{{ $firm->address }}</p>
    @endif
    <p class="text-slate-600">
        {{ collect([$firm->city, $firm->district, $firm->state, $firm->pincode])->filter()->implode(', ') }}
    </p>
    @if($firm->gstin)
        <p class="text-slate-600">GSTIN: {{ $firm->gstin }}</p>
    @endif
    @if($firm->email)
        <p class="text-slate-600">Email: {{ $firm->email }}</p>
    @endif
    @if($firm->phone)
        <p class="text-slate-600">Phone: {{ $firm->phone }}</p>
    @endif
@endif
            </div>

            <div class="text-right">
                <h2 class="text-2xl font-bold">INVOICE</h2>
                <p class="mt-2 font-semibold">{{ $invoice->invoice_number }}</p>
                <p class="text-slate-500">
                    Date: {{ $invoice->invoice_date->format('d-m-Y') }}
                </p>
                <p class="text-slate-500">
                    Due: {{ $invoice->due_date?->format('d-m-Y') ?? 'Not specified' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8 border-b py-8">
            <div>
                <p class="mb-2 text-sm font-semibold uppercase text-slate-500">Bill To</p>
                <h3 class="text-lg font-bold">
                    {{ $invoice->client->display_name ?: trim($invoice->client->first_name.' '.$invoice->client->last_name) }}
                </h3>
                <p>{{ $invoice->client->address }}</p>
                <p>
                    {{ collect([$invoice->client->city, $invoice->client->state, $invoice->client->pin_code])->filter()->implode(', ') }}
                </p>
                <p>{{ $invoice->client->mobile }}</p>
                <p>{{ $invoice->client->email }}</p>
            </div>

            <div class="text-right">
                <p class="mb-2 text-sm font-semibold uppercase text-slate-500">Invoice Status</p>
                <span class="inline-block rounded-full bg-blue-100 px-4 py-2 font-semibold text-blue-700">
                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                </span>
            </div>
        </div>

        <div class="py-8">
            <h3 class="mb-4 text-lg font-bold">Description of Services</h3>
            <p class="whitespace-pre-line text-slate-700">
                {{ $invoice->description ?: 'Professional legal services' }}
            </p>
        </div>

        <div class="ml-auto max-w-sm space-y-3 border-t pt-6">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span>Ã¢â€šÂ¹{{ number_format($invoice->subtotal, 2) }}</span>
            </div>

            <div class="flex justify-between">
                <span>Discount</span>
                <span>Ã¢â€šÂ¹{{ number_format($invoice->discount, 2) }}</span>
            </div>

            <div class="flex justify-between">
                <span>Tax</span>
                <span>Ã¢â€šÂ¹{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>

            <div class="flex justify-between border-t pt-4 text-xl font-bold">
                <span>Total</span>
                <span>Ã¢â€šÂ¹{{ number_format($invoice->total_amount, 2) }}</span>
            </div>

            <div class="flex justify-between font-semibold text-green-700">
                <span>Received</span>
                <span>Ã¢â€šÂ¹{{ number_format($invoice->payments->sum('amount'), 2) }}</span>
            </div>

            <div class="flex justify-between text-lg font-bold text-red-600">
                <span>Outstanding</span>
                <span>Ã¢â€šÂ¹{{ number_format(max(0, $invoice->total_amount - $invoice->payments->sum('amount')), 2) }}</span>
            </div>
        </div>

        <div class="mt-10 border-t pt-6">
            <h3 class="mb-4 text-lg font-bold">Payment History</h3>

            @forelse($invoice->payments as $payment)
                <div class="flex justify-between border-b py-3">
                    <div>
                        <p class="font-medium">{{ $payment->receipt_number }}</p>
                        <p class="text-sm text-slate-500">
                            {{ $payment->payment_date->format('d-m-Y') }}
                            Ã‚Â· {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        </p>
                    </div>
                    <p class="font-semibold">Ã¢â€šÂ¹{{ number_format($payment->amount, 2) }}</p>
                </div>
            @empty
                <p class="text-slate-500">No payments received yet.</p>
            @endforelse
        </div>

        @if($invoice->notes)
            <div class="mt-8 border-t pt-6">
                <h3 class="font-bold">Notes</h3>
                <p class="mt-2 whitespace-pre-line text-slate-600">{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="mt-10 border-t pt-6 text-center text-sm text-slate-500">
            This invoice was generated through VakilNeeti.
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const shareButton = document.getElementById('shareInvoiceButton');

    if (!shareButton) return;

    const pdfUrl = @json(route('invoices.pdf', $invoice));
    const invoiceNumber = @json($invoice->invoice_number);

    shareButton.addEventListener('click', async function () {
        const originalText = shareButton.textContent;

        // Open the WhatsApp tab immediately to avoid popup blocking.
        const whatsappTab = window.open('about:blank', '_blank');

        shareButton.disabled = true;
        shareButton.textContent = 'Preparing PDF...';

        try {
            const response = await fetch(pdfUrl, {
                credentials: 'same-origin'
            });

            if (!response.ok) {
                throw new Error('Unable to generate invoice PDF.');
            }

            const blob = await response.blob();

            if (blob.type !== 'application/pdf') {
                throw new Error('The server did not return a valid PDF.');
            }

            const file = new File(
                [blob],
                'Invoice-' + invoiceNumber + '.pdf',
                { type: 'application/pdf' }
            );

            const message = 'Please find invoice ' + invoiceNumber + ' attached.';

            if (
                navigator.share &&
                navigator.canShare &&
                navigator.canShare({ files: [file] })
            ) {
                try {
                    await navigator.share({
                        title: 'Invoice ' + invoiceNumber,
                        text: message,
                        files: [file]
                    });

                    if (whatsappTab) whatsappTab.close();
                    return;

                } catch (error) {
                    if (error.name === 'AbortError') {
                        if (whatsappTab) whatsappTab.close();
                        return;
                    }
                }
            }

            // Fallback: download the PDF.
            const objectUrl = URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');

            downloadLink.href = objectUrl;
            downloadLink.download = 'Invoice-' + invoiceNumber + '.pdf';

            document.body.appendChild(downloadLink);
            downloadLink.click();
            downloadLink.remove();

            setTimeout(function () {
                URL.revokeObjectURL(objectUrl);
            }, 60000);

            // Redirect the already-opened tab to WhatsApp.
            const whatsappUrl =
                'https://wa.me/?text=' + encodeURIComponent(message);

            if (whatsappTab) {
                whatsappTab.location.href = whatsappUrl;
            }

            alert(
                'The invoice PDF has been downloaded. ' +
                'Attach the downloaded PDF in WhatsApp before sending.'
            );

        } catch (error) {
            console.error(error);

            if (whatsappTab) whatsappTab.close();

            alert('Unable to prepare the invoice PDF. Please try Download PDF.');

        } finally {
            shareButton.disabled = false;
            shareButton.textContent = originalText;
        }
    });
});
</script>
</body>
</html>