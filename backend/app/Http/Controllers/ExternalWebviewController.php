<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ExternalWebviewController extends Controller
{
    public function show(Request $request): View
    {
        $url = trim((string) $request->query('url', ''));
        $parts = parse_url($url);

        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
        $isAllowed = in_array($scheme, ['http', 'https'], true)
            && $host !== ''
            && !in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        abort_unless($isAllowed, 422, 'Only public HTTP and HTTPS websites can be opened.');

        return view('external.webview', [
            'url' => $url,
            'host' => $host,
        ]);
    }
}
