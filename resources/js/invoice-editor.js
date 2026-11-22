/**
 * Invoice form: line items, live GST totals (same maths as App\Services\InvoiceCalculator),
 * payment terms → due date, and adding a case's unbilled expenses / time entries.
 */
function money(symbol, value) {
    const formatted = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value);

    return ['Rs.', 'AED'].includes(symbol) ? `${symbol} ${formatted}` : `${symbol}${formatted}`;
}

function initInvoiceEditor(form) {
    const rows = form.querySelector('[data-invoice-rows]');
    const symbol = form.dataset.currency || 'Rs.';
    const terms = JSON.parse(form.dataset.terms || '{}');
    const firmState = (form.dataset.firmState || '').trim().toLowerCase();
    const dateInput = form.querySelector('[data-invoice-date]');
    const dueInput = form.querySelector('[data-invoice-due]');
    const termsSelect = form.querySelector('[data-invoice-terms]');
    const placeSelect = form.querySelector('[data-invoice-place]');
    const discountInput = form.querySelector('[data-invoice-discount]');
    const caseSelect = form.querySelector('[data-invoice-case]');
    const billablesBox = form.querySelector('[data-invoice-billables]');
    const billablesList = form.querySelector('[data-invoice-billables-list]');
    const template = rows.querySelector('[data-invoice-row]').cloneNode(true);

    function renumber() {
        rows.querySelectorAll('[data-invoice-row]').forEach((row, index) => {
            row.querySelectorAll('[name^="items["]').forEach((input) => {
                input.name = input.name.replace(/^items\[\d+\]/, `items[${index}]`);
            });
        });
    }

    function addRow(values = {}) {
        const row = template.cloneNode(true);
        row.querySelectorAll('input').forEach((input) => { input.value = ''; });
        row.querySelector('[data-field="quantity"]').value = values.quantity ?? 1;
        row.querySelector('[data-field="rate"]').value = values.rate ?? '';
        row.querySelector('[name$="[description]"]').value = values.description ?? '';
        row.querySelector('[data-field="source_type"]').value = values.source_type ?? '';
        row.querySelector('[data-field="source_id"]').value = values.source_id ?? '';

        if (values.tax_rate !== undefined) {
            row.querySelector('[data-field="tax_rate"]').value = String(values.tax_rate);
        }

        rows.append(row);
        renumber();
        recalculate();

        return row;
    }

    function recalculate() {
        let subtotal = 0;
        let lineTax = 0;

        rows.querySelectorAll('[data-invoice-row]').forEach((row) => {
            const quantity = parseFloat(row.querySelector('[data-field="quantity"]').value) || 0;
            const rate = parseFloat(row.querySelector('[data-field="rate"]').value) || 0;
            const taxRate = parseFloat(row.querySelector('[data-field="tax_rate"]').value) || 0;
            const inclusive = row.querySelector('[data-field="tax_mode"]').value === 'inclusive';
            const gross = quantity * rate;
            const taxable = inclusive ? gross / (1 + taxRate / 100) : gross;
            const tax = inclusive ? gross - taxable : gross * taxRate / 100;

            row.querySelector('[data-line-total]').textContent = money(symbol, taxable + tax);
            subtotal += taxable;
            lineTax += tax;
        });

        const discount = Math.min(Math.max(parseFloat(discountInput.value) || 0, 0), subtotal);
        const tax = subtotal > 0 ? lineTax * (subtotal - discount) / subtotal : 0;
        const place = (placeSelect.value || '').trim().toLowerCase();
        const known = firmState !== '' && place !== '';
        const sameState = known && firmState === place;

        form.querySelector('[data-total="subtotal"]').textContent = money(symbol, subtotal);
        form.querySelector('[data-total="cgst"]').textContent = money(symbol, tax / 2);
        form.querySelector('[data-total="sgst"]').textContent = money(symbol, tax / 2);
        form.querySelector('[data-total="igst"]').textContent = money(symbol, tax);
        form.querySelector('[data-total="tax"]').textContent = money(symbol, tax);
        form.querySelector('[data-tax-row="cgst"]').hidden = !sameState;
        form.querySelector('[data-tax-row="sgst"]').hidden = !sameState;
        form.querySelector('[data-tax-row="igst"]').hidden = !known || sameState;
        form.querySelector('[data-tax-row="tax"]').hidden = known;
        form.querySelector('[data-total="total"]').textContent = money(symbol, subtotal - discount + tax);
    }

    function updateDueDate() {
        const days = terms[termsSelect.value];
        const custom = days === null || days === undefined;

        dueInput.readOnly = !custom;
        dueInput.required = custom;
        dueInput.classList.toggle('bg-slate-50', !custom);

        if (!custom && dateInput.value) {
            const due = new Date(`${dateInput.value}T00:00:00`);
            due.setDate(due.getDate() + days);
            dueInput.value = `${due.getFullYear()}-${String(due.getMonth() + 1).padStart(2, '0')}-${String(due.getDate()).padStart(2, '0')}`;
        }
    }

    function alreadyAdded(type, id) {
        return [...rows.querySelectorAll('[data-invoice-row]')].some((row) => row.querySelector('[data-field="source_type"]').value === type
            && row.querySelector('[data-field="source_id"]').value === String(id));
    }

    async function loadBillables() {
        billablesList.replaceChildren();
        billablesBox.hidden = true;

        if (!caseSelect.value) {
            return;
        }

        const invoiceId = form.dataset.invoiceId;
        const url = `${form.dataset.billablesUrl}/${encodeURIComponent(caseSelect.value)}/billables${invoiceId ? `?invoice=${encodeURIComponent(invoiceId)}` : ''}`;

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const items = response.ok ? (await response.json()).data : [];

            items.filter((item) => !alreadyAdded(item.source_type, item.source_id)).forEach((item) => {
                const line = document.createElement('div');
                line.className = 'flex items-center justify-between gap-3';
                const label = document.createElement('span');
                label.className = 'min-w-0 truncate text-slate-700';
                label.textContent = `${item.description} · ${money(symbol, item.quantity * item.rate)}`;
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'shrink-0 rounded-lg border border-[#8fc9c0] bg-white px-2 py-1 text-xs font-semibold text-[#16776e] hover:bg-[#e8f4f2]';
                button.textContent = 'Add';
                button.addEventListener('click', () => {
                    addRow({ ...item, tax_rate: 0 });
                    line.remove();
                    billablesBox.hidden = billablesList.children.length === 0;
                });
                line.append(label, button);
                billablesList.append(line);
            });

            billablesBox.hidden = billablesList.children.length === 0;
        } catch (error) {
            console.error(error);
        }
    }

    form.querySelector('[data-invoice-add-row]').addEventListener('click', () => {
        addRow().querySelector('[name$="[description]"]').focus();
    });

    rows.addEventListener('click', (event) => {
        if (!event.target.closest('[data-invoice-remove-row]')) {
            return;
        }

        if (rows.querySelectorAll('[data-invoice-row]').length > 1) {
            event.target.closest('[data-invoice-row]').remove();
            renumber();
            recalculate();
            loadBillables();
        }
    });

    form.addEventListener('input', recalculate);
    form.addEventListener('change', recalculate);
    termsSelect.addEventListener('change', updateDueDate);
    dateInput.addEventListener('change', updateDueDate);
    caseSelect.addEventListener('change', loadBillables);

    updateDueDate();
    recalculate();
    loadBillables();
}

document.querySelectorAll('[data-invoice-editor]').forEach(initInvoiceEditor);
