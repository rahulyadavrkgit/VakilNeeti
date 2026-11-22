import './location-picker.js';
import './ui-actions.js';

// Large page-specific modules are only downloaded on the pages that use them.
if (document.getElementById('vn-pdf-annotator')) {
    import('./pdf-annotator.js');
}

if (document.querySelector('[data-invoice-editor]')) {
    import('./invoice-editor.js');
}
