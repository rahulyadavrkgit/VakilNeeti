@extends('layouts.app')

@section('title', 'Invoice Details')
@section('page-heading', 'Invoice Details')

@push('styles')
<style>
@media print {
    body { background: white !important; }
    button, a { display: none !important; }
    .shadow-sm { box-shadow: none !important; }
}
</style>
@endpush

@section('content')

<div class="mx-auto max-w-5xl">

    <div class="mb-6 flex flex-wrap items-center gap-2">
        <button type="button" id="shareInvoiceButton"
                data-pdf-url="{{ route('invoices.pdf', $invoice) }}"
                data-share-url="{{ $shareUrl }}"
                data-invoice-number="{{ $invoice->invoice_number }}"
                data-client-name="{{ $invoice->client?->displayName() }}"
                data-whatsapp-number="{{ $invoice->client?->whatsappNumber() }}"
                class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#1f9d55] px-5 text-sm font-semibold text-white hover:bg-[#178246] disabled:opacity-60">
            <x-icon name="upload" :size="18" /> <span data-share-label>Share PDF on WhatsApp</span>
        </button>
        <a href="{{ route('invoices.pdf', $invoice) }}"
           class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-[#16776e] px-5 text-sm font-semibold text-white hover:bg-[#125f58]">
            <x-icon name="download" :size="18" /> Download PDF
        </a>
        <button type="button" data-copy="{{ $shareUrl }}"
                class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Copy link</button>
        <button type="button" data-print
                class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print</button>
        <p class="w-full text-xs text-slate-500 print:hidden">On a phone, Share sends the PDF file itself. On a computer, WhatsApp gets a secure link to the PDF that works for 30 days.</p>
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
                <h3 class="text-lg font-semibold text-slate-900">
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

        @if($invoice->legalCase || $invoice->client_pan || $invoice->client_gstin || $invoice->place_of_supply)
            <dl class="grid gap-4 border-b py-6 text-sm sm:grid-cols-2 lg:grid-cols-4">
                @if($invoice->legalCase)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Case</dt><dd class="mt-1"><a href="{{ route('cases.show', $invoice->legalCase) }}" class="font-medium text-[#16776e] hover:underline">{{ $invoice->legalCase->case_title }}</a></dd></div>
                @endif
                @if($invoice->client_pan)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Client PAN</dt><dd class="mt-1 font-medium">{{ $invoice->client_pan }}</dd></div>
                @endif
                @if($invoice->client_gstin)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Client GSTIN</dt><dd class="mt-1 font-medium">{{ $invoice->client_gstin }}</dd></div>
                @endif
                @if($invoice->place_of_supply)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Place of supply</dt><dd class="mt-1 font-medium">{{ $invoice->place_of_supply }}</dd></div>
                @endif
            </dl>
        @endif

        <div class="py-8">
            @if($invoice->items->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs uppercase text-slate-500">
                            <tr>
                                <th class="py-2 pr-3">Description</th>
                                <th class="py-2 pr-3 text-right">Qty</th>
                                <th class="py-2 pr-3 text-right">Rate</th>
                                <th class="py-2 pr-3 text-right">Taxable</th>
                                <th class="py-2 pr-3 text-right">GST</th>
                                <th class="py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($invoice->items as $item)
                                <tr>
                                    <td class="py-2 pr-3">{{ $item->description }}</td>
                                    <td class="py-2 pr-3 text-right">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                    <td class="py-2 pr-3 text-right">@money($item->rate)</td>
                                    <td class="py-2 pr-3 text-right">@money($item->taxable_amount)</td>
                                    <td class="py-2 pr-3 text-right">{{ rtrim(rtrim(number_format((float) $item->tax_rate, 2), '0'), '.') }}%{{ $item->tax_mode === 'inclusive' ? ' incl.' : '' }}</td>
                                    <td class="py-2 text-right font-medium">@money($item->line_total)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($invoice->description)
                    <p class="mt-4 whitespace-pre-line text-sm text-slate-600">{{ $invoice->description }}</p>
                @endif
            @else
                <h3 class="mb-4 text-lg font-semibold text-slate-900">Description of Services</h3>
                <p class="whitespace-pre-line text-slate-700">
                    {{ $invoice->description ?: 'Professional legal services' }}
                </p>
            @endif
        </div>

        <div class="ml-auto max-w-sm space-y-3 border-t pt-6">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span>@money($invoice->subtotal)</span>
            </div>

            <div class="flex justify-between">
                <span>Discount</span>
                <span>@money($invoice->discount)</span>
            </div>

            @if($gst['cgst'] > 0 || $gst['sgst'] > 0)
                <div class="flex justify-between"><span>CGST</span><span>@money($gst['cgst'])</span></div>
                <div class="flex justify-between"><span>SGST</span><span>@money($gst['sgst'])</span></div>
            @elseif($gst['igst'] > 0)
                <div class="flex justify-between"><span>IGST</span><span>@money($gst['igst'])</span></div>
            @else
                <div class="flex justify-between"><span>Tax</span><span>@money($invoice->tax_amount)</span></div>
            @endif

            <div class="flex justify-between border-t pt-4 text-xl font-bold">
                <span>Total</span>
                <span>@money($invoice->total_amount)</span>
            </div>

            <div class="flex justify-between font-semibold text-green-700">
                <span>Received</span>
                <span>@money($invoice->payments->sum('amount'))</span>
            </div>

            @if($invoice->isWrittenOff())
                <div class="flex justify-between font-semibold text-slate-500">
                    <span>Written off {{ $invoice->written_off_at->format('d M Y') }}</span>
                    <span>@money(max(0, $invoice->total_amount - $invoice->payments->sum('amount')))</span>
                </div>
            @else
                <div class="flex justify-between text-lg font-bold text-red-600">
                    <span>Outstanding</span>
                    <span>@money($invoice->balanceDue())</span>
                </div>
            @endif
        </div>

        <div class="mt-10 border-t pt-6">
            <h3 class="mb-4 text-lg font-semibold text-slate-900">Payment History</h3>

            @forelse($invoice->payments as $payment)
                <div class="flex justify-between border-b py-3">
                    <div>
                        <p class="font-medium">{{ $payment->receipt_number }}</p>
                        <p class="text-sm text-slate-500">
                            {{ $payment->payment_date->format('d-m-Y') }}
                            · {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        </p>
                    </div>
                    <p class="font-semibold">@money($payment->amount)</p>
                </div>
            @empty
                <p class="text-slate-500">No payments received yet.</p>
            @endforelse
        </div>

        @if($invoice->notes)
            <div class="mt-8 border-t pt-6">
                <h3 class="text-base font-semibold text-slate-900">Notes</h3>
                <p class="mt-2 whitespace-pre-line text-slate-600">{{ $invoice->notes }}</p>
            </div>
        @endif

        <div class="mt-10 border-t pt-6 text-center text-sm text-slate-500">
            This invoice was generated through VakilNiti.
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('shareInvoiceButton');

    if (!button) return;

    const label = button.querySelector('[data-share-label]');
    const { pdfUrl, shareUrl, invoiceNumber, clientName, whatsappNumber } = button.dataset;
    const message = 'Dear ' + (clientName || 'Sir/Madam') + ', please find invoice ' + invoiceNumber + ' attached.';
    let pdfFile = null;

    // Prepare the PDF as soon as the page opens. Phones only attach a file when sharing starts
    // straight from the tap, so the file must be ready before the button is pressed.
    const filePromise = fetch(pdfUrl, { credentials: 'same-origin' })
        .then(function (response) {
            if (!response.ok) throw new Error('PDF ' + response.status);
            return response.blob();
        })
        .then(function (blob) {
            pdfFile = new File([blob], 'Invoice-' + invoiceNumber.replace(/[\\/]/g, '-') + '.pdf', { type: 'application/pdf' });
            return pdfFile;
        })
        .catch(function () { return null; });

    function canShareFile(file) {
        return Boolean(file && navigator.share && navigator.canShare && navigator.canShare({ files: [file] }));
    }

    function shareLinkOnWhatsApp() {
        const text = message + '\n\nInvoice PDF: ' + shareUrl;
        const target = whatsappNumber ? 'https://wa.me/' + whatsappNumber : 'https://wa.me/';
        window.open(target + '?text=' + encodeURIComponent(text), '_blank', 'noopener');
    }

    button.addEventListener('click', function () {
        if (canShareFile(pdfFile)) {
            navigator.share({ title: 'Invoice ' + invoiceNumber, text: message, files: [pdfFile] })
                .catch(function (error) {
                    if (error.name !== 'AbortError') shareLinkOnWhatsApp();
                });
            return;
        }

        if (!pdfFile && navigator.canShare) {
            // Still preparing: wait, then ask for one more tap so the phone allows the file.
            button.disabled = true;
            label.textContent = 'Preparing PDF…';
            filePromise.then(function (file) {
                button.disabled = false;
                label.textContent = canShareFile(file) ? 'Tap again to share the PDF' : 'Share PDF on WhatsApp';
                if (!canShareFile(file)) shareLinkOnWhatsApp();
            });
            return;
        }

        shareLinkOnWhatsApp();
    });
});
</script>
@endpush
