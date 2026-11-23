<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExternalWebviewTest extends TestCase
{
    public function test_public_external_webview_accepts_https_url(): void
    {
        $this->get('/webview?url='.urlencode('https://indiacode.gov.in/'))
            ->assertOk()
            ->assertSee('indiacode.gov.in');
    }

    public function test_external_webview_rejects_local_and_invalid_urls(): void
    {
        $this->get('/webview?url=http%3A%2F%2F127.0.0.1%3A8000%2F')
            ->assertStatus(422);

        $this->get('/webview?url=javascript%3Aalert%281%29')
            ->assertStatus(422);
    }

    public function test_external_webview_rejects_websites_that_are_not_official(): void
    {
        $this->get('/webview?url='.urlencode('https://example.com/login'))
            ->assertStatus(422);

        $this->get('/webview?url='.urlencode('https://gov.in.example.com/'))
            ->assertStatus(422);

        $this->get('/webview?url='.urlencode('https://notgov.in/'))
            ->assertStatus(422);
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get('/webview?url='.urlencode('https://ecourts.gov.in/'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
