<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Files for search engines: robots.txt and sitemap.xml, built with this site's own address.
 */
class SeoController extends Controller
{
    /**
     * Public pages worth listing in search results (route name => change frequency).
     *
     * @var array<string, string>
     */
    public const SITEMAP_ROUTES = [
        'dashboard' => 'weekly',
        'app.download' => 'monthly',
        'courts.index' => 'monthly',
        'courts.supreme-court' => 'monthly',
        'courts.supreme-court.court-list' => 'weekly',
        'courts.delhi-high-court.judges' => 'weekly',
        'courts.delhi-high-court' => 'monthly',
        'courts.delhi-district-courts' => 'monthly',
        'courts.display-boards' => 'monthly',
        'courts.ecourts-services' => 'monthly',
        'court-directory.index' => 'weekly',
        'court-catalogue.index' => 'monthly',
        'court-integrations.ecourts.search' => 'monthly',
        'bare-acts.index' => 'monthly',
        'new-criminal-laws.index' => 'monthly',
        'court-fee.index' => 'monthly',
        'miscellaneous-forms.index' => 'monthly',
        'police-administration.index' => 'weekly',
        'bar-associations.index' => 'monthly',
        'bar-directory.index' => 'monthly',
    ];

    /**
     * Private areas search engines should not crawl (they need a login anyway).
     *
     * @var list<string>
     */
    private const DISALLOWED = [
        '/admin', '/cases', '/case-calendar', '/case-dashboard', '/hearings', '/hearing-history', '/tasks',
        '/clients', '/client-management', '/billing', '/invoices', '/payments', '/profile', '/firm-profile',
        '/notifications', '/search', '/tds', '/professional-tax', '/dashboard-studio', '/webview', '/shared', '/api', '/register', '/login',
    ];

    public function robots(): Response
    {
        $lines = ['User-agent: *', 'Allow: /'];

        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = 'Allow: /dashboard-studio/public/';
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('seo.sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $today = now()->toDateString();
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach (self::SITEMAP_ROUTES as $route => $frequency) {
            $xml[] = '  <url><loc>'.e(route($route)).'</loc><lastmod>'.$today.'</lastmod><changefreq>'.$frequency.'</changefreq><priority>'.($route === 'dashboard' ? '1.0' : '0.7').'</priority></url>';
        }

        $xml[] = '</urlset>';

        return response(implode("\n", $xml)."\n", 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
