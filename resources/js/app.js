import './location-picker.js';
import './ui-actions.js';
import './sheet.js';
import './offline.js';
import './court-picker.js';
import './push.js';

// Large page-specific modules are only downloaded on the pages that use them.
if (document.querySelector('[data-pdf-viewer]')) {
    import('./pdf-viewer.js');
}

if (document.getElementById('vn-pdf-annotator')) {
    import('./pdf-annotator.js');
}

if (document.querySelector('[data-invoice-editor]')) {
    import('./invoice-editor.js');
}

if (document.querySelector('[data-event-peek]')) {
    import('./event-peek.js');
}
