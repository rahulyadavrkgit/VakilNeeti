<?php

namespace Tests\Feature;

use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    public function test_unknown_url_shows_the_branded_not_found_page_with_ways_back(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Go to Home')
            ->assertSee('id="vn-404-back"', false)
            ->assertSee(route('cases.index'), false);
    }

    public function test_not_found_page_script_carries_the_content_security_policy_nonce(): void
    {
        $response = $this->get('/this-page-does-not-exist')->assertNotFound();

        preg_match("/'nonce-([A-Za-z0-9]+)'/", (string) $response->headers->get('Content-Security-Policy'), $nonce);

        $this->assertNotEmpty($nonce[1] ?? null);
        $this->assertStringContainsString('<script nonce="'.$nonce[1].'">', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<[^>]+\son[a-z]+\s*=\s*"/i', $response->getContent());
    }
}
