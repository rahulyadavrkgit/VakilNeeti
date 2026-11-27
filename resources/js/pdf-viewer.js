/*
 * A PDF shown inside the page ([data-pdf-viewer data-src="…"]), drawn page by page at the width of the screen,
 * so it reads the same on phones (which do not show PDFs inside a page) and computers. Pages are drawn as they
 * come into view.
 */
import * as pdfjsLib from 'pdfjs-dist';
import pdfWorker from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker;

document.querySelectorAll('[data-pdf-viewer]').forEach(async (viewer) => {
    const status = viewer.querySelector('[data-pdf-status]');
    const pages = viewer.querySelector('[data-pdf-pages]');
    const failed = viewer.querySelector('[data-pdf-failed]');

    try {
        // The whole file first (rosters are small), then PDF.js reads it: no partial downloads to wait on.
        const response = await fetch(viewer.dataset.src, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error('PDF unavailable');
        }

        const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(await response.arrayBuffer()) }).promise;
        status.hidden = true;

        const draw = async (number, canvas) => {
            const page = await pdf.getPage(number);
            const width = pages.clientWidth || 800;
            const base = page.getViewport({ scale: 1 });
            const scale = width / base.width;
            const ratio = Math.min(window.devicePixelRatio || 1, 2);
            const viewport = page.getViewport({ scale: scale * ratio });
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.width = '100%';
            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
        };

        const observer = 'IntersectionObserver' in window
            ? new IntersectionObserver((entries) => entries.forEach((entry) => {
                if (entry.isIntersecting && !entry.target.dataset.drawn) {
                    entry.target.dataset.drawn = '1';
                    draw(Number(entry.target.dataset.page), entry.target).catch(() => {});
                }
            }), { rootMargin: '800px 0px' })
            : null;

        for (let number = 1; number <= pdf.numPages; number += 1) {
            const canvas = document.createElement('canvas');
            canvas.dataset.page = String(number);
            canvas.className = 'block w-full rounded-lg border border-slate-200 bg-white';
            canvas.setAttribute('aria-label', `Page ${number} of ${pdf.numPages}`);
            canvas.style.aspectRatio = '1 / 1.414';
            pages.append(canvas);

            if (observer) {
                observer.observe(canvas);
            } else {
                canvas.dataset.drawn = '1';
                draw(number, canvas).catch(() => {});
            }
        }
    } catch {
        status.hidden = true;
        failed.hidden = false;
    }
});
