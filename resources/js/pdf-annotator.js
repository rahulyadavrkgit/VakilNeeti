import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';
import { PDFDocument, StandardFonts, rgb, degrees } from 'pdf-lib';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

(() => {
    const root = document.getElementById('vn-pdf-annotator');
    if (!root || root.dataset.initialized) return;
    root.dataset.initialized = 'yes';

    const $ = id => document.getElementById(id);
    const canvas = $('vn-canvas');
    const overlay = $('vn-overlay');
    const ctx = canvas.getContext('2d');
    const octx = overlay.getContext('2d');

    const state = {
        pdf: null,
        bytes: null,
        filename: 'document.pdf',
        page: 1,
        zoom: 1,
        mode: 'annotate',
        tool: 'select',
        color: '#ffeb3b',
        size: 4,
        opacity: 0.35,
        annotations: {},
        current: null,
        drawing: false,
        start: null,
        selected: -1,
        undo: [],
        redo: [],
        search: [],
        searchIndex: -1,
        crop: null,
        cropStart: null,
        cropActive: false,
        image: null,
        renderId: 0
    };

    const pageItems = () => state.annotations[state.page] ||= [];
    const status = message => $('vn-status').textContent = message;
    const clamp = (v, min, max) => Math.max(min, Math.min(max, v));

    function snapshot() {
        state.undo.push(JSON.stringify({
            annotations: state.annotations,
            page: state.page,
            crop: state.crop
        }));
        if (state.undo.length > 50) state.undo.shift();
        state.redo = [];
    }

    function restoreSnapshot(value) {
        const data = JSON.parse(value);
        state.annotations = data.annotations;
        state.crop = data.crop;
        state.page = data.page;
        state.selected = -1;
        render();
    }

    function undo() {
        if (!state.undo.length) return;
        state.redo.push(JSON.stringify({
            annotations: state.annotations,
            page: state.page,
            crop: state.crop
        }));
        restoreSnapshot(state.undo.pop());
    }

    function redo() {
        if (!state.redo.length) return;
        state.undo.push(JSON.stringify({
            annotations: state.annotations,
            page: state.page,
            crop: state.crop
        }));
        restoreSnapshot(state.redo.pop());
    }

    function dimensions() {
        return { w: overlay.width, h: overlay.height };
    }

    function bounds(item) {
        return {
            x: item.x * overlay.width,
            y: item.y * overlay.height,
            w: (item.w || 0.02) * overlay.width,
            h: (item.h || 0.02) * overlay.height
        };
    }

    function drawItem(item, context) {
        const { w, h } = dimensions();
        const x = item.x * w;
        const y = item.y * h;
        const iw = (item.w || 0) * w;
        const ih = (item.h || 0) * h;

        context.save();
        context.globalAlpha = item.opacity ?? 1;
        context.strokeStyle = item.color || '#222222';
        context.fillStyle = item.color || '#222222';
        context.lineWidth = item.size || 3;
        context.lineCap = 'round';
        context.lineJoin = 'round';

        switch (item.type) {
            case 'highlight':
                context.globalAlpha = item.opacity ?? 0.32;
                context.fillRect(x, y, iw, ih);
                break;
            case 'whiteout':
            case 'text-edit-cover':
                context.fillStyle = '#ffffff';
                context.fillRect(x, y, iw, ih);
                break;
            case 'rectangle':
                context.strokeRect(x, y, iw, ih);
                break;
            case 'ellipse':
                context.beginPath();
                context.ellipse(x + iw / 2, y + ih / 2,
                    Math.abs(iw / 2), Math.abs(ih / 2), 0, 0, Math.PI * 2);
                context.stroke();
                break;
            case 'underline':
                context.beginPath();
                context.moveTo(x, y + ih);
                context.lineTo(x + iw, y + ih);
                context.stroke();
                break;
            case 'strike':
                context.beginPath();
                context.moveTo(x, y + ih / 2);
                context.lineTo(x + iw, y + ih / 2);
                context.stroke();
                break;
            case 'arrow':
            case 'line': {
                const ex = x + iw;
                const ey = y + ih;
                context.beginPath();
                context.moveTo(x, y);
                context.lineTo(ex, ey);
                context.stroke();
                if (item.type === 'arrow') {
                    const angle = Math.atan2(ey - y, ex - x);
                    const length = 13;
                    context.beginPath();
                    context.moveTo(ex, ey);
                    context.lineTo(ex - length * Math.cos(angle - 0.5),
                        ey - length * Math.sin(angle - 0.5));
                    context.moveTo(ex, ey);
                    context.lineTo(ex - length * Math.cos(angle + 0.5),
                        ey - length * Math.sin(angle + 0.5));
                    context.stroke();
                }
                break;
            }
            case 'pen':
                if (item.points?.length) {
                    context.beginPath();
                    context.moveTo(item.points[0].x * w, item.points[0].y * h);
                    item.points.slice(1).forEach(p =>
                        context.lineTo(p.x * w, p.y * h));
                    context.stroke();
                }
                break;
            case 'text':
            case 'text-edit':
            case 'note':
            case 'signature': {
                const fontStyle = [
                    item.italic ? 'italic' : '',
                    item.bold ? 'bold' : ''
                ].filter(Boolean).join(' ');

                context.font =
                    `${fontStyle ? fontStyle + ' ' : ''}${item.fontSize || 18}px ${item.font || 'Arial'}`;

                context.textBaseline = 'top';

                const textLines = String(item.text || '').split('\n');
                const lineHeight = (item.fontSize || 18) * 1.25;
                const boxWidth = Math.max(iw, 120);
                const boxHeight = Math.max(
                    ih,
                    textLines.length * lineHeight + 12,
                    item.type === 'note' ? 65 : 0
                );

                if (item.background || item.type === 'note') {
                    context.fillStyle = item.background || '#fff2a8';
                    context.fillRect(x, y, boxWidth, boxHeight);
                }

                context.fillStyle = item.color || '#222222';
                context.textAlign = item.align || 'left';

                textLines.forEach((line, index) => {
                    let textX = x + 3;

                    if (item.align === 'center') {
                        textX = x + boxWidth / 2;
                    } else if (item.align === 'right') {
                        textX = x + boxWidth - 3;
                    }

                    context.fillText(
                        line,
                        textX,
                        y + 6 + index * lineHeight,
                        Math.max(boxWidth - 6, 20)
                    );
                });

                context.textAlign = 'left';
                break;
            }
            case 'image':
                if (item.data) {
                    const img = new Image();
                    img.onload = () => {
                        context.save();
                        context.globalAlpha = item.opacity ?? 1;
                        context.drawImage(img, x, y, iw || 120, ih || 80);
                        context.restore();
                    };
                    img.src = item.data;
                }
                break;
        }
        context.restore();
    }

    // vn-layer-manager-upgraded
    function renderManager() {
        const list = $('vn-annotation-list');
        const items = pageItems();

        list.innerHTML = '';

        items.forEach((item, index) => {
            const row = document.createElement('div');

            row.className = 'vn-list-row';
            row.style.cssText =
                'display:flex;flex-wrap:wrap;align-items:center;gap:7px;padding:9px;margin:5px 0;border:1px solid #cbd5e1;border-radius:7px;background:' +
                (state.selected === index ? '#dbeafe' : '#ffffff') +
                ';opacity:' + (item.hidden ? '0.65' : '1') + ';';

            const details = document.createElement('div');
            details.style.cssText = 'flex:1;min-width:130px;';

            const label = document.createElement('strong');
            label.textContent = (index + 1) + '. ' + (item.type || 'Annotation');
            label.style.display = 'block';

            const preview = document.createElement('small');
            preview.style.cssText = 'display:block;color:#64748b;overflow-wrap:anywhere;';

            if (item.type === 'text-edit-cover') {
                preview.textContent = 'Covered original PDF text';
            } else if (item.type === 'image') {
                preview.textContent = 'Inserted image';
            } else if (item.text) {
                preview.textContent = String(item.text).slice(0, 90);
            } else {
                preview.textContent = item.hidden ? 'Hidden' : 'Visible';
            }

            details.append(label, preview);

            const makeButton = (caption, color, action) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = caption;
                button.style.cssText =
                    'padding:6px 9px;border:1px solid ' + color +
                    ';border-radius:5px;background:white;color:' + color + ';';
                button.addEventListener('click', action);
                return button;
            };

            const select = makeButton('Select', '#2563eb', () => {
                state.selected = index;
                drawOverlay();
                syncTextProperties();
            });

            const canRename =
                ['text', 'text-edit', 'note', 'signature'].includes(item.type);

            const rename = makeButton('Rename', '#475569', () => {
                const updated = prompt(
                    'Enter the new annotation text:',
                    item.text || ''
                );

                if (updated === null) return;

                snapshot();
                item.text = updated;
                drawOverlay();
                syncTextProperties();
            });

            const visibility = makeButton(
                item.hidden ? 'Show' : 'Hide',
                '#7c3aed',
                () => {
                    snapshot();
                    item.hidden = !item.hidden;
                    drawOverlay();
                    syncTextProperties();
                }
            );

            const remove = makeButton('Delete', '#dc2626', () => {
                if (!confirm('Delete this annotation?')) return;

                snapshot();
                items.splice(index, 1);

                if (state.selected === index) {
                    state.selected = -1;
                } else if (state.selected > index) {
                    state.selected--;
                }

                drawOverlay();
                syncTextProperties();
            });

            row.append(details, select);

            if (canRename) {
                row.append(rename);
            }

            row.append(visibility, remove);
            list.appendChild(row);
        });

        $('vn-annotation-count').textContent =
            items.length + ' annotation(s) on this page (' +
            items.filter(item => !item.hidden).length + ' visible)';
    }
    function drawOverlay() {
        octx.clearRect(0, 0, overlay.width, overlay.height);
        pageItems().filter(item => !item.hidden).forEach(item => drawItem(item, octx));
        if (state.current) drawItem(state.current, octx);

        if (state.crop && state.crop.page === state.page) {
            const c = state.crop;
            octx.save();
            octx.fillStyle = 'rgba(30,30,30,.45)';
            octx.fillRect(0, 0, overlay.width, overlay.height);
            octx.clearRect(c.x * overlay.width, c.y * overlay.height,
                c.w * overlay.width, c.h * overlay.height);
            octx.strokeStyle = '#2563eb';
            octx.lineWidth = 2;
            octx.setLineDash([6, 4]);
            octx.strokeRect(c.x * overlay.width, c.y * overlay.height,
                c.w * overlay.width, c.h * overlay.height);
            octx.restore();
        }

        if (state.selected >= 0 && pageItems()[state.selected]) {
            const b = bounds(pageItems()[state.selected]);
            octx.save();
            octx.strokeStyle = '#2563eb';
            octx.lineWidth = 2;
            octx.setLineDash([5, 4]);
            octx.strokeRect(b.x, b.y, b.w, b.h);
            octx.setLineDash([]);
            [[b.x,b.y],[b.x+b.w,b.y],[b.x,b.y+b.h],[b.x+b.w,b.y+b.h]]
                .forEach(([x,y]) => {
                    octx.fillStyle = '#fff';
                    octx.strokeStyle = '#2563eb';
                    octx.fillRect(x-5,y-5,10,10);
                    octx.strokeRect(x-5,y-5,10,10);
                });
            octx.restore();
        }
        renderManager();
    }

    async function renderSelectableText(page, viewport) {
        const parent = overlay.parentElement;
        if (!parent) return;

        if (!state.textLayer) {
            state.textLayer = document.createElement('div');
            state.textLayer.id = 'vn-selectable-text-layer';
            state.textLayer.style.cssText =
                'position:absolute;overflow:hidden;pointer-events:none;z-index:4;user-select:text;';

            const parentStyle = getComputedStyle(parent);
            if (parentStyle.position === 'static') {
                parent.style.position = 'relative';
            }

            parent.insertBefore(state.textLayer, overlay);
        }

        const layer = state.textLayer;
        layer.style.left = `${overlay.offsetLeft}px`;
        layer.style.top = `${overlay.offsetTop}px`;
        layer.style.width = `${viewport.width}px`;
        layer.style.height = `${viewport.height}px`;
        layer.style.pointerEvents = state.textSelectMode ? 'auto' : 'none';
        layer.replaceChildren();

        const content = await page.getTextContent();

        for (const item of content.items) {
            if (!item.str || !item.transform) continue;

            const tx = pdfjsLib.Util.transform(
                viewport.transform,
                item.transform
            );

            const fontHeight = Math.hypot(tx[2], tx[3]);
            if (!fontHeight) continue;

            const angle = Math.atan2(tx[1], tx[0]);
            const span = document.createElement('span');

            span.textContent = item.str;
            span.dataset.vnText = 'yes';
            span.dataset.fontSize = String(fontHeight);

            span.style.cssText = [
                'position:absolute',
                'display:inline-block',
                'white-space:pre',
                'color:transparent',
                'cursor:text',
                'user-select:text',
                'transform-origin:left bottom',
                `left:${tx[4]}px`,
                `top:${tx[5] - fontHeight}px`,
                `font-size:${fontHeight}px`,
                `line-height:1`,
                `font-family:${(page.commonObjs.has(item.fontName) ? 'sans-serif' : 'sans-serif')}`,
                `width:${Math.max(item.width * viewport.scale, 1)}px`,
                `height:${fontHeight * 1.2}px`,
                `transform:rotate(${angle}rad)`
            ].join(';');

            layer.appendChild(span);
        }
    }
    async function render() {
        if (!state.pdf) return;
        const id = ++state.renderId;
        try {
            const page = await state.pdf.getPage(state.page);
            const viewport = page.getViewport({ scale: state.zoom });
            canvas.width = overlay.width = Math.ceil(viewport.width);
            canvas.height = overlay.height = Math.ceil(viewport.height);
            canvas.style.width = overlay.style.width = `${viewport.width}px`;
            canvas.style.height = overlay.style.height = `${viewport.height}px`;

            await page.render({ canvasContext: ctx, viewport }).promise;
            if (id !== state.renderId) return;
            await renderSelectableText(page, viewport);

            drawOverlay();
            $('vn-page-number').textContent = state.page;
            $('vn-page-total').textContent = state.pdf.numPages;
            $('vn-zoom-value').textContent = `${Math.round(state.zoom * 100)}%`;
            renderThumbnails();
            status(`Page ${state.page} of ${state.pdf.numPages}`);
        } catch (error) {
            if (error.name !== 'RenderingCancelledException') {
                console.error(error);
                status('Unable to render this page.');
            }
        }
    }

    async function renderThumbnails() {
        const container = $('vn-thumbnails');
        container.innerHTML = '';
        const total = state.pdf.numPages;
        for (let i = 1; i <= total; i++) {
            const button = document.createElement('button');
            button.className = 'vn-thumb' + (i === state.page ? ' active' : '');
            button.textContent = `Page ${i}`;
            button.onclick = () => {
                state.page = i;
                state.selected = -1;
                render();
            };
            container.appendChild(button);
        }
    }

    async function openFile(file) {
        if (!file || file.type !== 'application/pdf') {
            status('Please select a PDF file.');
            return;
        }
        try {
            status('Opening PDF locally...');
            state.bytes = await file.arrayBuffer();
            state.filename = file.name;
            state.pdf = await pdfjsLib.getDocument({
                data: new Uint8Array(state.bytes.slice(0))
            }).promise;
            state.page = 1;
            state.annotations = {};
            state.undo = [];
            state.redo = [];
            state.crop = null;
            $('vn-workspace').hidden = false;
            $('vn-start').hidden = true;
            await render();
            status(`Opened ${file.name} locally.`);
        } catch (error) {
            console.error(error);
            status('Unable to open this PDF. It may be encrypted or damaged.');
        }
    }

    function point(event) {
        const rect = overlay.getBoundingClientRect();
        return {
            x: clamp((event.clientX - rect.left) / rect.width, 0, 1),
            y: clamp((event.clientY - rect.top) / rect.height, 0, 1)
        };
    }

    function hitTest(p) {
        const items = pageItems();
        for (let i = items.length - 1; i >= 0; i--) {
            if (items[i].hidden) continue;
            const b = bounds(items[i]);
            const x = p.x * overlay.width;
            const y = p.y * overlay.height;
            if (x >= b.x - 8 && x <= b.x + b.w + 8 &&
                y >= b.y - 8 && y <= b.y + b.h + 8) return i;
        }
        return -1;
    }

    function detectResizeHandle(index, p) {
        if (index < 0) return null;

        const item = pageItems()[index];
        const b = bounds(item);
        const px = p.x * overlay.width;
        const py = p.y * overlay.height;
        const radius = 14;

        const handles = [
            { name: 'nw', x: b.x, y: b.y },
            { name: 'ne', x: b.x + b.w, y: b.y },
            { name: 'sw', x: b.x, y: b.y + b.h },
            { name: 'se', x: b.x + b.w, y: b.y + b.h }
        ];

        for (const handle of handles) {
            if (
                Math.abs(px - handle.x) <= radius &&
                Math.abs(py - handle.y) <= radius
            ) {
                return handle.name;
            }
        }

        return null;
    }
    overlay.addEventListener('pointerdown', event => {
        if (!state.pdf) return;
        const p = point(event);
        state.start = p;
        state.drawing = true;
        overlay.setPointerCapture(event.pointerId);

        if (state.tool === 'select') {
            state.selected = hitTest(p);
            syncTextProperties();

            if (state.selected >= 0) {
                const item = pageItems()[state.selected];
                const handle = detectResizeHandle(state.selected, p);

                state.transform = {
                    mode: handle ? 'resize' : 'move',
                    handle,
                    x: item.x,
                    y: item.y,
                    w: item.w || 0.02,
                    h: item.h || 0.02,
                    start: p
                };

                snapshot();
            } else {
                state.transform = null;
            }

            drawOverlay();
            return;
        }

        if (state.tool === 'crop') {
            state.cropActive = true;
            state.cropStart = p;
            state.crop = { page: state.page, x: p.x, y: p.y, w: 0, h: 0 };
            drawOverlay();
            return;
        }

        snapshot();
        state.current = {
            type: state.tool,
            x: p.x, y: p.y, w: 0, h: 0,
            color: state.color,
            size: state.size,
            opacity: state.opacity
        };
        if (state.tool === 'pen') state.current.points = [p];
    });

    overlay.addEventListener('pointermove', event => {
        if (!state.drawing || !state.start) return;
        const p = point(event);

        if (state.tool === 'select' && state.transform &&
            state.selected >= 0) {

            const item = pageItems()[state.selected];
            const t = state.transform;
            const dx = p.x - t.start.x;
            const dy = p.y - t.start.y;
            const minSize = 0.015;

            if (t.mode === 'move') {
                item.x = clamp(t.x + dx, 0, Math.max(0, 1 - t.w));
                item.y = clamp(t.y + dy, 0, Math.max(0, 1 - t.h));
            } else {
                let left = t.x;
                let top = t.y;
                let right = t.x + t.w;
                let bottom = t.y + t.h;

                if (t.handle.includes('w')) {
                    left = clamp(t.x + dx, 0, right - minSize);
                }

                if (t.handle.includes('e')) {
                    right = clamp(t.x + t.w + dx, left + minSize, 1);
                }

                if (t.handle.includes('n')) {
                    top = clamp(t.y + dy, 0, bottom - minSize);
                }

                if (t.handle.includes('s')) {
                    bottom = clamp(t.y + t.h + dy, top + minSize, 1);
                }

                item.x = left;
                item.y = top;
                item.w = right - left;
                item.h = bottom - top;
            }

            drawOverlay();
            return;
        }

        if (state.cropActive && state.crop) {
            state.crop.x = Math.min(state.cropStart.x, p.x);
            state.crop.y = Math.min(state.cropStart.y, p.y);
            state.crop.w = Math.abs(p.x - state.cropStart.x);
            state.crop.h = Math.abs(p.y - state.cropStart.y);
            drawOverlay();
            return;
        }

        if (!state.current) return;
        if (state.tool === 'pen') {
            state.current.points.push(p);
        } else {
            state.current.x = Math.min(state.start.x, p.x);
            state.current.y = Math.min(state.start.y, p.y);
            state.current.w = Math.abs(p.x - state.start.x);
            state.current.h = Math.abs(p.y - state.start.y);
        }
        drawOverlay();
    });

    function finishDrawing() {
        if (!state.drawing) return;
        state.drawing = false;

        if (state.cropActive) {
            state.cropActive = false;
            state.cropStart = null;
            state.start = null;
            drawOverlay();
            status('Crop preview ready. Select Apply Crop to confirm.');
            return;
        }

        if (state.current) {
            if (state.current.type === 'eraser') {
                const p = state.start;
                const index = hitTest(p);
                if (index >= 0) pageItems().splice(index, 1);
            } else {
                pageItems().push(state.current);
            }
            state.current = null;
        }
        state.transform = null;
        state.start = null;
        drawOverlay();
    }

    overlay.addEventListener('pointerup', finishDrawing);
    overlay.addEventListener('pointercancel', finishDrawing);

    function addText() {
        if (!state.pdf) return;
        const text = prompt('Enter text to add:');
        if (text === null || !text.trim()) return;
        snapshot();
        pageItems().push({
            type: 'text', x: .15, y: .15, w: .5, h: .06,
            text, color: state.color, fontSize: 18,
            font: 'Arial', opacity: 1
        });
        drawOverlay();
    }

    function addNote() {
        const text = prompt('Enter comment or note:');
        if (text === null || !text.trim()) return;
        snapshot();
        pageItems().push({
            type: 'note', x: .12, y: .15, w: .25, h: .1,
            text, color: '#222222', opacity: 1, fontSize: 14
        });
        drawOverlay();
    }

    function addSignature() {
        const text = prompt('Enter signature text:');
        if (!text) return;
        snapshot();
        pageItems().push({
            type: 'signature', x: .2, y: .75, w: .35, h: .08,
            text, color: '#173b73', font: 'cursive',
            fontSize: 30, opacity: 1
        });
        drawOverlay();
    }

    $('vn-file').addEventListener('change', e => openFile(e.target.files[0]));
    $('vn-open').onclick = () => $('vn-file').click();

    document.querySelectorAll('[data-tool]').forEach(button => {
        button.onclick = () => {
            state.tool = button.dataset.tool;
            document.querySelectorAll('[data-tool]').forEach(b =>
                b.classList.toggle('active', b === button));
            status(`Selected tool: ${state.tool}`);
        };
    });

    document.querySelectorAll('[data-mode]').forEach(button => {
        button.onclick = () => {
            state.mode = button.dataset.mode;
            state.textSelectMode = false;
            if (state.textLayer) state.textLayer.style.pointerEvents = 'none';
            overlay.style.pointerEvents = 'auto';
            if (selectionButton) selectionButton.style.background = 'white';
            $('vn-annotate-panel').hidden = state.mode !== 'annotate';
            $('vn-edit-panel').hidden = state.mode !== 'edit';
            document.querySelectorAll('[data-mode]').forEach(b =>
                b.classList.toggle('active', b === button));
        };
    });

    $('vn-color').oninput = e => state.color = e.target.value;
    $('vn-size').oninput = e => state.size = Number(e.target.value);
    $('vn-opacity').oninput = e => state.opacity = Number(e.target.value);

    $('vn-add-text').onclick = addText;
    $('vn-add-note').onclick = addNote;
    $('vn-add-signature').onclick = addSignature;
    $('vn-undo').onclick = undo;
    $('vn-redo').onclick = redo;

    $('vn-delete-selected').onclick = () => {
        if (state.selected < 0) return status('Select an annotation first.');
        snapshot();
        pageItems().splice(state.selected, 1);
        state.selected = -1;
        drawOverlay();
    };

    $('vn-clear-page').onclick = () => {
        if (!confirm('Clear all annotations on this page?')) return;
        snapshot();
        state.annotations[state.page] = [];
        state.selected = -1;
        drawOverlay();
    };

    $('vn-prev').onclick = () => {
        if (state.page > 1) {
            state.page--;
            state.selected = -1;
            render();
        }
    };
    $('vn-next').onclick = () => {
        if (state.pdf && state.page < state.pdf.numPages) {
            state.page++;
            state.selected = -1;
            render();
        }
    };
    $('vn-zoom-in').onclick = () => {
        state.zoom = Math.min(3, state.zoom + .15);
        render();
    };
    $('vn-zoom-out').onclick = () => {
        state.zoom = Math.max(.4, state.zoom - .15);
        render();
    };
    $('vn-fit').onclick = () => {
        state.zoom = 1;
        render();
    };

    $('vn-search').onkeydown = e => {
        if (e.key === 'Enter') $('vn-search-go').click();
    };

    $('vn-search-go').onclick = async () => {
        if (!state.pdf) return;
        const query = $('vn-search').value.trim().toLowerCase();
        if (!query) return status('Enter text to search.');
        state.search = [];
        state.searchIndex = -1;
        status('Searching PDF text...');
        for (let n = 1; n <= state.pdf.numPages; n++) {
            const page = await state.pdf.getPage(n);
            const content = await page.getTextContent();
            for (const item of content.items) {
                if (item.str?.toLowerCase().includes(query)) {
                    state.search.push({ page: n, text: item.str });
                }
            }
        }
        if (!state.search.length) return status('No matches. Scanned PDFs may require OCR.');
        state.searchIndex = 0;
        state.page = state.search[0].page;
        await render();
        $('vn-search-count').textContent = `1 / ${state.search.length}`;
        status(`Found ${state.search.length} matching text item(s).`);
    };

    async function moveSearch(direction) {
        if (!state.search.length) return;
        state.searchIndex = (state.searchIndex + direction + state.search.length) % state.search.length;
        state.page = state.search[state.searchIndex].page;
        await render();
        $('vn-search-count').textContent =
            `${state.searchIndex + 1} / ${state.search.length}`;
        status(`Search result ${state.searchIndex + 1}, page ${state.page}.`);
    }

    $('vn-search-prev').onclick = () => moveSearch(-1);
    $('vn-search-next').onclick = () => moveSearch(1);
    $('vn-search-clear').onclick = () => {
        state.search = [];
        state.searchIndex = -1;
        $('vn-search-count').textContent = '';
        $('vn-search').value = '';
        status('Search cleared.');
    };

    $('vn-image-file').onchange = async e => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => {
            snapshot();
            pageItems().push({
                type: 'image', x: .15, y: .15, w: .3, h: .2,
                data: reader.result, opacity: 1
            });
            drawOverlay();
        };
        reader.readAsDataURL(file);
        e.target.value = '';
    };
    $('vn-add-image').onclick = () => $('vn-image-file').click();

    async function changeDocument(operation) {
        if (!state.bytes) return;
        try {
            const doc = await PDFDocument.load(state.bytes.slice(0));
            const pages = doc.getPages();
            await flattenEditedPages(doc);
            const index = state.page - 1;
            if (operation === 'delete') {
                if (pages.length <= 1) return status('At least one page is required.');
                if (!confirm(`Delete page ${state.page}?`)) return;
                doc.removePage(index);
                state.page = Math.max(1, state.page - 1);
            } else if (operation === 'duplicate') {
                const [copy] = await doc.copyPages(doc, [index]);
                doc.insertPage(index + 1, copy);
                state.page++;
            } else if (operation === 'blank') {
                doc.insertPage(index + 1, [595, 842]);
                state.page++;
            } else if (operation === 'rotate') {
                pages[index].setRotation(degrees((pages[index].getRotation().angle + 90) % 360));
            } else if (operation === 'left' && index > 0) {
                const [copy] = await doc.copyPages(doc, [index]);
                doc.removePage(index);
                doc.insertPage(index - 1, copy);
                state.page--;
            } else if (operation === 'right' && index < pages.length - 1) {
                const [copy] = await doc.copyPages(doc, [index]);
                doc.removePage(index);
                doc.insertPage(index + 1, copy);
                state.page++;
            } else if (operation === 'crop') {
                if (!state.crop || state.crop.page !== state.page ||
                    state.crop.w < .01 || state.crop.h < .01) {
                    return status('Draw a crop area on the current page first.');
                }
                const page = pages[index];
                const { x, y, w, h } = state.crop;
                const size = page.getSize();
                const left = x * size.width;
                const top = (1 - y - h) * size.height;
                page.setCropBox(left, top, w * size.width, h * size.height);
                state.crop = null;
            } else if (operation === 'reset-crop') {
                const page = pages[index];
                const size = page.getSize();
                page.setCropBox(0, 0, size.width, size.height);
                state.crop = null;
            }

            state.bytes = await doc.save();
            state.pdf = await pdfjsLib.getDocument({
                data: new Uint8Array(state.bytes.slice(0))
            }).promise;
            await render();
            status('Page operation applied.');
        } catch (error) {
            console.error(error);
            status('Page operation failed: ' + error.message);
        }
    }

    $('vn-rotate').onclick = () => changeDocument('rotate');
    $('vn-duplicate').onclick = () => changeDocument('duplicate');
    $('vn-blank').onclick = () => changeDocument('blank');
    $('vn-delete-page').onclick = () => changeDocument('delete');
    $('vn-left').onclick = () => changeDocument('left');
    $('vn-right').onclick = () => changeDocument('right');
    $('vn-apply-crop').onclick = () => changeDocument('crop');
    $('vn-reset-crop').onclick = () => changeDocument('reset-crop');

    // Existing PDF text editing.
    state.textSelectMode = false;
    state.selectedText = null;
    state.editedPages = new Set();

    const editToolbar = document.createElement('div');
    editToolbar.id = 'vn-existing-text-editor';
    editToolbar.style.cssText =
        'display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:12px;margin:10px 0;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;';

    editToolbar.innerHTML = `
        <button type="button" id="vn-select-existing-text"
            style="padding:8px 12px;border:1px solid #64748b;border-radius:6px;background:white">
            Select Existing Text
        </button>
        <input id="vn-replacement-text" type="text"
            placeholder="Enter replacement text"
            style="min-width:190px;padding:8px;border:1px solid #94a3b8;border-radius:6px">
        <button type="button" id="vn-replace-existing-text"
            style="padding:8px 12px;border:1px solid #2563eb;border-radius:6px;background:#2563eb;color:white">
            Replace Selected
        </button>
        <button type="button" id="vn-delete-existing-text"
            style="padding:8px 12px;border:1px solid #dc2626;border-radius:6px;background:#dc2626;color:white">
            Delete Selected
        </button>
        <span id="vn-text-selection-status"
            style="font-size:13px;color:#475569">Select text to begin.</span>
    `;

    $('vn-edit-panel').insertBefore(
        editToolbar,
        $('vn-edit-panel').firstChild
    );
    // Text Properties Editor.
    const propertiesPanel = document.createElement('div');
    propertiesPanel.id = 'vn-text-properties';
    propertiesPanel.style.cssText =
        'display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:12px;margin:10px 0;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;';

    propertiesPanel.innerHTML = `
        <strong style="width:100%">Text Properties</strong>

        <label>Font
            <select id="vn-prop-font" style="padding:6px;border:1px solid #94a3b8;border-radius:5px">
                <option value="Arial">Arial</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Courier New">Courier New</option>
            </select>
        </label>

        <label>Size
            <input id="vn-prop-size" type="number" min="6" max="96" value="18"
                style="width:65px;padding:6px;border:1px solid #94a3b8;border-radius:5px">
        </label>

        <label>Text colour
            <input id="vn-prop-color" type="color" value="#222222">
        </label>

        <label><input id="vn-prop-bold" type="checkbox"> Bold</label>
        <label><input id="vn-prop-italic" type="checkbox"> Italic</label>

        <label>Alignment
            <select id="vn-prop-align" style="padding:6px;border:1px solid #94a3b8;border-radius:5px">
                <option value="left">Left</option>
                <option value="center">Centre</option>
                <option value="right">Right</option>
            </select>
        </label>

        <label>
            <input id="vn-prop-background-enabled" type="checkbox">
            Background
        </label>

        <input id="vn-prop-background" type="color" value="#fff2a8">

        <span id="vn-prop-status" style="font-size:12px;color:#475569">
            Select a text box, signature or note.
        </span>
    `;

    $('vn-edit-panel').insertBefore(
        propertiesPanel,
        $('vn-edit-panel').firstChild
    );

    // Annotation Management Controls.
    const managementPanel = document.createElement('div');
    managementPanel.id = 'vn-annotation-management';
    managementPanel.style.cssText =
        'display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px;margin:8px 0;border:1px solid #cbd5e1;border-radius:8px;background:#f1f5f9;';

    managementPanel.innerHTML = `
        <strong style="width:100%">Selected Annotation Actions</strong>
        <button type="button" id="vn-duplicate-annotation"
            style="padding:7px 12px;border:1px solid #2563eb;border-radius:6px;background:#2563eb;color:white">
            Duplicate
        </button>
        <button type="button" id="vn-delete-annotation"
            style="padding:7px 12px;border:1px solid #dc2626;border-radius:6px;background:#dc2626;color:white">
            Delete
        </button>
        <button type="button" id="vn-bring-forward"
            style="padding:7px 12px;border:1px solid #64748b;border-radius:6px;background:white">
            Bring Forward
        </button>
        <button type="button" id="vn-send-backward"
            style="padding:7px 12px;border:1px solid #64748b;border-radius:6px;background:white">
            Send Backward
        </button>
        <span id="vn-management-status" style="font-size:12px;color:#475569">
            Select an annotation first.
        </span>
    `;

    propertiesPanel.after(managementPanel);

    function getSelectedAnnotation() {
        const items = pageItems();

        if (
            state.selected < 0 ||
            state.selected >= items.length
        ) {
            return null;
        }

        return items[state.selected];
    }

    $('vn-duplicate-annotation').addEventListener('click', () => {
        const items = pageItems();
        const item = getSelectedAnnotation();

        if (!item) {
            $('vn-management-status').textContent =
                'Select an annotation first.';
            return;
        }

        snapshot();

        const copy = JSON.parse(JSON.stringify(item));

        copy.x = clamp(
            (Number(copy.x) || 0) + 0.02,
            0,
            Math.max(0, 1 - (copy.w || 0.02))
        );

        copy.y = clamp(
            (Number(copy.y) || 0) + 0.02,
            0,
            Math.max(0, 1 - (copy.h || 0.02))
        );

        items.push(copy);
        state.selected = items.length - 1;

        drawOverlay();
        syncTextProperties();

        $('vn-management-status').textContent =
            'Annotation duplicated.';
    });

    $('vn-delete-annotation').addEventListener('click', () => {
        const items = pageItems();

        if (!getSelectedAnnotation()) {
            $('vn-management-status').textContent =
                'Select an annotation first.';
            return;
        }

        snapshot();

        items.splice(state.selected, 1);
        state.selected = -1;

        drawOverlay();
        syncTextProperties();

        $('vn-management-status').textContent =
            'Annotation deleted.';
    });

    $('vn-bring-forward').addEventListener('click', () => {
        const items = pageItems();

        if (!getSelectedAnnotation()) {
            $('vn-management-status').textContent =
                'Select an annotation first.';
            return;
        }

        if (state.selected === items.length - 1) {
            $('vn-management-status').textContent =
                'Already at the front.';
            return;
        }

        snapshot();

        const selected = items.splice(state.selected, 1)[0];
        items.push(selected);
        state.selected = items.length - 1;

        drawOverlay();
        syncTextProperties();

        $('vn-management-status').textContent =
            'Annotation brought forward.';
    });

    $('vn-send-backward').addEventListener('click', () => {
        const items = pageItems();

        if (!getSelectedAnnotation()) {
            $('vn-management-status').textContent =
                'Select an annotation first.';
            return;
        }

        if (state.selected === 0) {
            $('vn-management-status').textContent =
                'Already at the back.';
            return;
        }

        snapshot();

        const selected = items.splice(state.selected, 1)[0];
        items.unshift(selected);
        state.selected = 0;

        drawOverlay();
        syncTextProperties();

        $('vn-management-status').textContent =
            'Annotation sent backward.';
    });
    function syncTextProperties() {
        const item = state.selected >= 0
            ? pageItems()[state.selected]
            : null;

        const supported = item &&
            ['text', 'text-edit', 'signature', 'note'].includes(item.type);

        $('vn-prop-status').textContent = supported
            ? 'Editing selected ' + item.type
            : 'Select a text box, signature or note.';

        if (!supported) return;

        $('vn-prop-font').value = item.font || 'Arial';
        $('vn-prop-size').value = item.fontSize || 18;
        $('vn-prop-color').value = item.color || '#222222';
        $('vn-prop-bold').checked = !!item.bold;
        $('vn-prop-italic').checked = !!item.italic;
        $('vn-prop-align').value = item.align || 'left';

        $('vn-prop-background-enabled').checked = !!item.background;
        $('vn-prop-background').value = item.background || '#fff2a8';
    }

    function applyTextProperties() {
        const item = state.selected >= 0
            ? pageItems()[state.selected]
            : null;

        if (!item ||
            !['text', 'text-edit', 'signature', 'note'].includes(item.type)) {
            $('vn-prop-status').textContent =
                'Select a text box, signature or note first.';
            return;
        }

        snapshot();

        item.font = $('vn-prop-font').value;
        item.fontSize = clamp(
            Number($('vn-prop-size').value) || 18, 6, 96
        );
        item.color = $('vn-prop-color').value;
        item.bold = $('vn-prop-bold').checked;
        item.italic = $('vn-prop-italic').checked;
        item.align = $('vn-prop-align').value;
        item.background = $('vn-prop-background-enabled').checked
            ? $('vn-prop-background').value
            : null;

        drawOverlay();
        $('vn-prop-status').textContent = 'Properties applied.';
    }

    [
        'vn-prop-font',
        'vn-prop-size',
        'vn-prop-color',
        'vn-prop-bold',
        'vn-prop-italic',
        'vn-prop-align',
        'vn-prop-background-enabled',
        'vn-prop-background'
    ].forEach(id => {
        $(id).addEventListener('change', applyTextProperties);
    });


    const selectionButton = $('vn-select-existing-text');

    selectionButton.onclick = () => {
        if (!state.pdf) return status('Open a PDF first.');

        state.textSelectMode = !state.textSelectMode;

        if (state.textLayer) {
            state.textLayer.style.pointerEvents =
                state.textSelectMode ? 'auto' : 'none';
        }

        overlay.style.pointerEvents =
            state.textSelectMode ? 'none' : 'auto';

        selectionButton.style.background =
            state.textSelectMode ? '#dbeafe' : 'white';

        $('vn-text-selection-status').textContent =
            state.textSelectMode
                ? 'Drag across existing PDF text to select it.'
                : 'Text selection disabled.';

        if (!state.textSelectMode) {
            state.selectedText = null;
            window.getSelection()?.removeAllRanges();
        }
    };

    document.addEventListener('selectionchange', () => {
        if (!state.textSelectMode || !state.textLayer) return;

        const selection = window.getSelection();
        if (!selection || selection.isCollapsed || !selection.toString().trim()) {
            return;
        }

        const range = selection.getRangeAt(0);
        const spans = [...state.textLayer.querySelectorAll('[data-vn-text="yes"]')];

        const selectedSpans = spans.filter(span => {
            try {
                return range.intersectsNode(span);
            } catch {
                return false;
            }
        });

        if (!selectedSpans.length) return;

        const rect = range.getBoundingClientRect();
        const layerRect = state.textLayer.getBoundingClientRect();

        if (!rect.width || !rect.height) return;

        state.selectedText = {
            text: selection.toString().trim(),
            x: clamp((rect.left - layerRect.left) / layerRect.width, 0, 1),
            y: clamp((rect.top - layerRect.top) / layerRect.height, 0, 1),
            w: clamp(rect.width / layerRect.width, 0.005, 1),
            h: clamp(rect.height / layerRect.height, 0.005, 1),
            fontSize: Math.max(
                8,
                Math.min(
                    48,
                    parseFloat(selectedSpans[0].dataset.fontSize) || 14
                )
            )
        };

        $('vn-text-selection-status').textContent =
            `Selected: ${state.selectedText.text.slice(0, 70)}`;
    });

    function applyExistingTextEdit(replacement, deleting = false) {
        if (!state.selectedText) {
            status('Select existing PDF text first.');
            return;
        }

        const selected = { ...state.selectedText };

        snapshot();

        pageItems().push({
            type: 'text-edit-cover',
            x: selected.x,
            y: selected.y,
            w: selected.w,
            h: selected.h,
            color: '#ffffff',
            opacity: 1,
            size: 0
        });

        if (!deleting && replacement.trim()) {
            pageItems().push({
                type: 'text-edit',
                x: selected.x,
                y: selected.y,
                w: Math.max(selected.w, 0.04),
                h: selected.h,
                text: replacement,
                color: '#222222',
                font: 'Arial',
                fontSize: selected.fontSize,
                opacity: 1
            });
        }

        state.editedPages.add(state.page);
        state.selectedText = null;
        window.getSelection()?.removeAllRanges();

        $('vn-replacement-text').value = '';
        $('vn-text-selection-status').textContent =
            deleting ? 'Selected text covered.' : 'Replacement added.';

        drawOverlay();
        status(
            deleting
                ? 'Text visually deleted. Export to flatten this page.'
                : 'Replacement added. Export to apply the edit.'
        );
    }

    $('vn-replace-existing-text').onclick = () => {
        const replacement = $('vn-replacement-text').value;

        if (!replacement.trim()) {
            return status('Enter the replacement text first.');
        }

        applyExistingTextEdit(replacement, false);
    };

    $('vn-delete-existing-text').onclick = () => {
        if (!state.selectedText) {
            return status('Select existing PDF text first.');
        }

        if (!confirm('Cover the selected text in the exported PDF?')) return;

        applyExistingTextEdit('', true);
    };

    // Flatten only pages containing existing-text edits.
    async function flattenEditedPages(doc) {
        const pages = doc.getPages();

        for (const pageNumber of state.editedPages) {
            const index = pageNumber - 1;
            if (index < 0 || index >= pages.length) continue;

            const pageAnnotations = state.annotations[pageNumber] || [];
            if (!pageAnnotations.some(item => item.type === 'text-edit-cover' && !item.hidden)) {
                continue;
            }

            status(`Flattening edited page ${pageNumber}...`);

            const pdfPage = await state.pdf.getPage(pageNumber);
            const viewport = pdfPage.getViewport({
                scale: 2,
                rotation: 0
            });

            const raster = document.createElement('canvas');
            raster.width = Math.ceil(viewport.width);
            raster.height = Math.ceil(viewport.height);

            const rasterContext = raster.getContext('2d', {
                alpha: false
            });

            await pdfPage.render({
                canvasContext: rasterContext,
                viewport
            }).promise;

            const dataUrl = raster.toDataURL('image/png');
            const base64 = dataUrl.split(',')[1];
            const pngBytes = Uint8Array.from(
                atob(base64),
                character => character.charCodeAt(0)
            );

            const image = await doc.embedPng(pngBytes);
            const dimensions = page.getSize();

            // The opaque page image covers the original text content.
            page.drawImage(image, {
                x: 0,
                y: 0,
                width: dimensions.width,
                height: dimensions.height
            });

            raster.width = 1;
            raster.height = 1;
        }
    }
    async function exportPdf() {
        if (!state.bytes) return;
        const button = $('vn-download');
        button.disabled = true;
        status('Preparing PDF locally...');
        try {
            const doc = await PDFDocument.load(state.bytes.slice(0));
            const fonts = {
                regular: await doc.embedFont(StandardFonts.Helvetica),
                bold: await doc.embedFont(StandardFonts.HelveticaBold),
                italic: await doc.embedFont(StandardFonts.HelveticaOblique),
                boldItalic: await doc.embedFont(StandardFonts.HelveticaBoldOblique),

                times: await doc.embedFont(StandardFonts.TimesRoman),
                timesBold: await doc.embedFont(StandardFonts.TimesRomanBold),
                timesItalic: await doc.embedFont(StandardFonts.TimesRomanItalic),
                timesBoldItalic: await doc.embedFont(StandardFonts.TimesRomanBoldItalic),

                courier: await doc.embedFont(StandardFonts.Courier),
                courierBold: await doc.embedFont(StandardFonts.CourierBold),
                courierItalic: await doc.embedFont(StandardFonts.CourierOblique),
                courierBoldItalic: await doc.embedFont(StandardFonts.CourierBoldOblique)
            };
            const pages = doc.getPages();
            await flattenEditedPages(doc);

            for (let i = 0; i < pages.length; i++) {
                const page = pages[i];
                const { width, height } = page.getSize();
                for (const item of state.annotations[i + 1] || []) {
                    if (item.hidden) continue;
                    const x = item.x * width;
                    const y = height - item.y * height;
                    const w = (item.w || .1) * width;
                    const h = (item.h || .03) * height;
                    const colorHex = item.color || '#222222';
                    const c = colorHex.match(/[a-f\d]{2}/gi) || ['22','22','22'];
                    const color = rgb(parseInt(c[0],16)/255,
                        parseInt(c[1],16)/255, parseInt(c[2],16)/255);
                    const opacity = item.opacity ?? 1;

                    if (item.type === 'highlight') {
                        page.drawRectangle({ x, y: y-h, width: w, height: h, color, opacity: Math.min(opacity,.4), borderWidth: 0 });
                    } else if (['whiteout','text-edit-cover'].includes(item.type)) {
                        page.drawRectangle({ x, y: y-h, width: w, height: h, color: rgb(1,1,1), borderWidth: 0 });
                    } else if (['text','text-edit','note','signature'].includes(item.type)) {
                        const family = item.font === 'Times New Roman'
                            ? 'times'
                            : item.font === 'Courier New'
                                ? 'courier'
                                : 'regular';

                        const fontKey = family === 'regular'
                            ? (item.bold
                                ? (item.italic ? 'boldItalic' : 'bold')
                                : (item.italic ? 'italic' : 'regular'))
                            : family === 'times'
                                ? (item.bold
                                    ? (item.italic ? 'timesBoldItalic' : 'timesBold')
                                    : (item.italic ? 'timesItalic' : 'times'))
                                : (item.bold
                                    ? (item.italic ? 'courierBoldItalic' : 'courierBold')
                                    : (item.italic ? 'courierItalic' : 'courier'));

                        const textFont = fonts[fontKey];
                        const textSize = item.fontSize || 18;
                        const textValue = item.text || '';
                        const textWidth = textFont.widthOfTextAtSize(
                            textValue,
                            textSize
                        );

                        let textX = x;

                        if (item.align === 'center') {
                            textX = x + (w - textWidth) / 2;
                        } else if (item.align === 'right') {
                            textX = x + w - textWidth;
                        }

                        if (item.background || item.type === 'note') {
                            const bgHex = item.background || '#fff2a8';
                            const bg = bgHex.match(/[a-f\d]{2}/gi) || ['ff','f2','a8'];

                            page.drawRectangle({
                                x,
                                y: y - h,
                                width: Math.max(w, 120),
                                height: Math.max(h, textSize * 1.5),
                                color: rgb(
                                    parseInt(bg[0], 16) / 255,
                                    parseInt(bg[1], 16) / 255,
                                    parseInt(bg[2], 16) / 255
                                ),
                                borderWidth: 0
                            });
                        }

                        page.drawText(textValue, {
                            x: textX,
                            y: y - textSize,
                            size: textSize,
                            font: textFont,
                            color,
                            opacity
                        });
                    } else if (item.type === 'rectangle') {
                        page.drawRectangle({ x, y: y-h, width: w, height: h, borderColor: color, borderWidth: item.size || 2, opacity });
                    } else if (item.type === 'ellipse') {
                        page.drawEllipse({ x: x+w/2, y: y-h/2, xScale: w/2, yScale: h/2, borderColor: color, borderWidth: item.size || 2, opacity });
                    } else if (['underline','strike','line','arrow'].includes(item.type)) {
                        const lineY = item.type === 'strike' ? y-h/2 : y-h;
                        page.drawLine({ start: { x, y: lineY }, end: { x: x+w, y: lineY }, thickness: item.size || 2, color, opacity });
                        if (item.type === 'arrow') {
                            page.drawLine({ start: { x: x+w, y: lineY }, end: { x: x+w-10, y: lineY+5 }, thickness: item.size || 2, color });
                            page.drawLine({ start: { x: x+w, y: lineY }, end: { x: x+w-10, y: lineY-5 }, thickness: item.size || 2, color });
                        }
                    } else if (item.type === 'pen' && item.points?.length > 1) {
                        for (let p = 1; p < item.points.length; p++) {
                            const a = item.points[p-1], b = item.points[p];
                            page.drawLine({
                                start: { x: a.x*width, y: height-a.y*height },
                                end: { x: b.x*width, y: height-b.y*height },
                                thickness: item.size || 2, color, opacity
                            });
                        }
                    } else if (item.type === 'image' && item.data) {
                        try {
                            const bytes = Uint8Array.from(atob(item.data.split(',')[1]), c => c.charCodeAt(0));
                            const image = item.data.startsWith('data:image/png')
                                ? await doc.embedPng(bytes) : await doc.embedJpg(bytes);
                            page.drawImage(image, { x, y: y-h, width: w, height: h, opacity });
                        } catch (error) {
                            console.warn('Image skipped:', error);
                        }
                    }
                }
            }

            const output = await doc.save();
            const blob = new Blob([output], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = state.filename.replace(/\.pdf$/i, '') + '-edited.pdf';
            a.click();
            URL.revokeObjectURL(url);
            status('PDF exported. Processing remained local to your browser.');
        } catch (error) {
            console.error(error);
            status('PDF export failed: ' + error.message);
        } finally {
            button.disabled = false;
        }
    }

    $('vn-download').onclick = exportPdf;

    $('vn-close').onclick = () => {
        state.pdf = null;
        state.bytes = null;
        state.annotations = {};
        state.crop = null;
        $('vn-workspace').hidden = true;
        $('vn-start').hidden = false;
        $('vn-file').value = '';
        status('PDF closed.');
    };

    document.addEventListener('keydown', event => {
        const target = event.target;
        if (target instanceof HTMLElement &&
            (target.isContentEditable || ['INPUT','TEXTAREA','SELECT'].includes(target.tagName))) return;
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z') {
            event.preventDefault(); undo();
        } else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'y') {
            event.preventDefault(); redo();
        } else if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'f') {
            event.preventDefault(); $('vn-search').focus();
        } else if (event.key === 'Escape') {
            state.current = null;
            state.drawing = false;
            drawOverlay();
        }
    });
})();
// Edit-panel buttons delegate to the working annotation controls.
document.addEventListener('click', function (event) {
    const target = event.target.closest('#vn-edit-add-text, #vn-edit-add-image');

    if (!target) return;

    if (target.id === 'vn-edit-add-text') {
        document.getElementById('vn-add-text')?.click();
    }

    if (target.id === 'vn-edit-add-image') {
        document.getElementById('vn-image-file')?.click();
    }
});