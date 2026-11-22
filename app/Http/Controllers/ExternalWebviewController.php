<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExternalWebviewController extends Controller
{
    /**
     * Only official legal and government websites may be shown inside VakilNiti, so the page
     * cannot be used to frame arbitrary (e.g. phishing) sites under this domain.
     * A host matches when it equals an entry or is a subdomain of it.
     *
     * @var list<string>
     */
    private const ALLOWED_DOMAINS = [
        'gov.in',
        'nic.in',
        'allahabadhighcourt.in',
        'barcouncilofindia.org',
        'barcouncilofdelhi.com',
        'barcouncilofkarnataka.org',
        'barcouncilofmaharashtragoa.org',
        'barcouncilofthandp.org',
        'barcouncilofup.com',
        'dhcba.in',
        'scba.co.in',
    ];

    public function show(Request $request): View
    {
        $url = trim((string) $request->query('url', ''));
        $parts = parse_url($url);

        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';
        $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

        abort_unless(
            in_array($scheme, ['http', 'https'], true) && $this->isAllowedHost($host),
            422,
            'Only official government and legal websites can be opened here.'
        );

        return view('external.webview', [
            'url' => $url,
            'host' => $host,
        ]);
    }

    private function isAllowedHost(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        foreach (self::ALLOWED_DOMAINS as $domain) {
            if ($host === $domain || Str::endsWith($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
