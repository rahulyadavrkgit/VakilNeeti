<?php

namespace App\Http\Controllers;

use App\Services\CourtBoardEmbedCheck;
use Illuminate\Http\JsonResponse;
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
            in_array($scheme, ['http', 'https'], true) && self::isOfficialHost($host),
            422,
            'Only official government and legal websites can be opened here.'
        );

        return view('external.webview', [
            'url' => $url,
            'host' => $host,
        ]);
    }

    /**
     * Which official websites allow being shown inside VakilNiti: ?hosts[]=a.gov.in&hosts[]=b.nic.in.
     * Answers are cached for two days; hosts that are not official are left out.
     */
    public function embedCheck(Request $request, CourtBoardEmbedCheck $check): JsonResponse
    {
        $hosts = collect((array) $request->query('hosts', []))
            ->filter(fn ($host) => is_string($host))
            ->map(fn (string $host) => strtolower(trim($host)))
            ->filter(fn (string $host) => self::isOfficialHost($host))
            ->unique()
            ->take(40)
            ->values()
            ->all();

        return response()->json($check->embeddableHosts($hosts));
    }

    public static function isOfficialHost(string $host): bool
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
