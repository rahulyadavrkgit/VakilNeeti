@extends('layouts.app')

@section('title', 'PDF Annotator & Editor')
@section('page-heading', 'PDF Annotator & Editor')

@push('styles')
<style>
        #vn-pdf-annotator { color:#172033; }
        #vn-pdf-annotator button { border:1px solid #d1d5db; border-radius:7px; padding:8px 12px; background:white; font-size:13px; }
        #vn-pdf-annotator button:hover { background:#eef2ff; }
        #vn-pdf-annotator button.active { background:#1e40af; color:white; border-color:#1e40af; }
        #vn-pdf-annotator button:disabled { opacity:.5; cursor:not-allowed; }
        #vn-pdf-annotator input { max-width:100%; }
        #vn-pdf-annotator .vn-panel { border:1px solid #e5e7eb; border-radius:12px; padding:14px; background:white; }
        #vn-pdf-annotator .vn-toolbar { display:flex; gap:7px; flex-wrap:wrap; align-items:center; }
        #vn-pdf-annotator .vn-label { font-size:12px; font-weight:600; color:#475569; }
        #vn-pdf-annotator .vn-workspace { display:grid; grid-template-columns:210px minmax(0,1fr) 230px; gap:14px; }
        #vn-pdf-annotator .vn-document { overflow:auto; background:#e5e7eb; padding:20px; min-height:500px; }
        #vn-pdf-annotator .vn-page { position:relative; margin:auto; width:max-content; box-shadow:0 2px 12px #0002; background:white; }
        #vn-pdf-annotator canvas { display:block; }
        #vn-pdf-annotator #vn-overlay { position:absolute; inset:0; touch-action:none; cursor:crosshair; }
        #vn-pdf-annotator .vn-thumb { display:block; width:100%; margin:5px 0; text-align:left; }
        #vn-pdf-annotator .vn-thumb.active { background:#dbeafe; color:#1e40af; }
        #vn-pdf-annotator .vn-list-row { display:flex; justify-content:space-between; gap:5px; align-items:center; border-bottom:1px solid #eee; padding:7px 0; font-size:12px; }
        #vn-pdf-annotator .vn-list-row button { padding:4px 7px; }
        #vn-pdf-annotator [hidden] { display:none !important; }
        @media(max-width:900px) { #vn-pdf-annotator .vn-workspace { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
<div id="vn-pdf-annotator" class="max-w-[1600px] mx-auto p-4 md:p-6">
    <header class="flex flex-wrap justify-between items-center gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">PDF Annotator & Editor</h1>
            <p class="text-sm text-slate-500">Local PDF processing · Your files are not uploaded</p>
        </div>
    </header>

    <section id="vn-start" class="vn-panel text-center py-14">
        <div class="text-4xl mb-3">📄</div>
        <h2 class="text-xl font-semibold mb-2">Open a PDF document</h2>
        <p class="text-sm text-slate-500 mb-5">Select a PDF from your device. Processing takes place in your browser.</p>
        <input id="vn-file" type="file" accept="application/pdf,.pdf" class="hidden">
        <button id="vn-open" class="!bg-blue-700 !text-white !px-6 !py-3">Choose PDF</button>
    </section>

    <section id="vn-workspace" hidden>
        <div class="vn-panel mb-3">
            <div class="vn-toolbar mb-3">
                <button data-mode="annotate" class="active">Annotate PDF</button>
                <button data-mode="edit">Edit PDF</button>
                <span class="flex-1"></span>
                <button id="vn-close">Close PDF</button>
                <button id="vn-download" class="!bg-blue-700 !text-white">Download PDF</button>
            </div>

            <div id="vn-annotate-panel">
                <div class="vn-toolbar mb-3">
                    <span class="vn-label">ANNOTATION TOOLS</span>
                    <button data-tool="select" class="active">Select / Move</button>
                    <button data-tool="highlight">Highlight</button>
                    <button data-tool="underline">Underline</button>
                    <button data-tool="strike">Strikethrough</button>
                    <button data-tool="pen">Pen</button>
                    <button data-tool="eraser">Eraser</button>
                    <button data-tool="rectangle">Rectangle</button>
                    <button data-tool="ellipse">Ellipse</button>
                    <button data-tool="arrow">Arrow</button>
                    <button data-tool="line">Line</button>
                </div>
                <div class="vn-toolbar">
                    <button id="vn-add-text">Add Text</button>
                    <button id="vn-add-note">Add Note</button>
                    <button id="vn-add-signature">Add Typed Signature</button>
                    <button id="vn-add-image">Insert Image</button>
                    <input id="vn-image-file" type="file" accept="image/png,image/jpeg" hidden>
                    <label class="vn-label">Colour <input id="vn-color" type="color" value="#ffeb3b"></label>
                    <label class="vn-label">Size <input id="vn-size" type="range" min="1" max="20" value="4"></label>
                    <label class="vn-label">Opacity <input id="vn-opacity" type="range" min="0.1" max="1" step="0.05" value="0.35"></label>
                    <button id="vn-undo">Undo</button>
                    <button id="vn-redo">Redo</button>
                    <button id="vn-delete-selected">Delete Selected</button>
                    <button id="vn-clear-page">Clear Page</button>
                </div>
            </div>

            <div id="vn-edit-panel" hidden>
                <div class="vn-toolbar mb-3">
                    <span class="vn-label">EDIT PDF</span>
                    <button data-tool="select" class="active">Select Objects</button>
                    <button id="vn-edit-add-text">Add Text</button>
                    <button id="vn-edit-add-image">Insert Image</button>
                    <button data-tool="whiteout">Whiteout Area</button>
                    <button data-tool="crop">Crop Selection</button>
                    <button id="vn-apply-crop">Apply Crop</button>
                    <button id="vn-reset-crop">Reset Crop</button>
                </div>
                <div class="vn-toolbar">
                    <button id="vn-rotate">Rotate 90°</button>
                    <button id="vn-duplicate">Duplicate Page</button>
                    <button id="vn-blank">Insert Blank Page</button>
                    <button id="vn-delete-page">Delete Page</button>
                    <button id="vn-left">Move Page Left</button>
                    <button id="vn-right">Move Page Right</button>
                </div>
                <p class="text-xs text-amber-700 mt-2">Whiteout only covers text visually. It is not secure redaction.</p>
            </div>
        </div>

        <div class="vn-workspace">
            <aside class="vn-panel">
                <h3 class="font-semibold mb-2">Pages</h3>
                <div class="vn-toolbar mb-3">
                    <button id="vn-prev">Previous</button>
                    <button id="vn-next">Next</button>
                </div>
                <div class="text-sm mb-2">Page <strong id="vn-page-number">1</strong> of <strong id="vn-page-total">1</strong></div>
                <div id="vn-thumbnails"></div>
            </aside>

            <main class="vn-panel min-w-0">
                <div class="vn-toolbar mb-3">
                    <button id="vn-zoom-out">−</button>
                    <span id="vn-zoom-value" class="text-sm">100%</span>
                    <button id="vn-zoom-in">+</button>
                    <button id="vn-fit">Reset Zoom</button>
                    <span class="flex-1"></span>
                    <span id="vn-status" class="text-xs text-slate-600">Ready</span>
                </div>
                <div class="vn-document">
                    <div class="vn-page">
                        <canvas id="vn-canvas"></canvas>
                        <canvas id="vn-overlay"></canvas>
                    </div>
                </div>
                <div class="vn-panel mt-3">
                    <h3 class="font-semibold text-sm mb-2">Search PDF text</h3>
                    <div class="vn-toolbar">
                        <input id="vn-search" class="border rounded p-2 text-sm flex-1 min-w-0" placeholder="Enter text to find">
                        <button id="vn-search-go">Find</button>
                        <button id="vn-search-prev">Previous Match</button>
                        <button id="vn-search-next">Next Match</button>
                        <button id="vn-search-clear">Clear</button>
                        <span id="vn-search-count" class="text-xs"></span>
                    </div>
                </div>
            </main>

            <aside class="vn-panel">
                <h3 class="font-semibold mb-2">Annotation Manager</h3>
                <p id="vn-annotation-count" class="text-xs text-slate-500 mb-2">0 annotations</p>
                <div id="vn-annotation-list"></div>
            </aside>
        </div>
    </section>
    <footer class="text-xs text-slate-500 mt-5">
        PDF processing is performed locally in this browser. Scanned-document OCR, true existing-text reconstruction, permanent redaction and cryptographic digital signatures require additional dedicated implementations.
    </footer>
</div>
@endsection
