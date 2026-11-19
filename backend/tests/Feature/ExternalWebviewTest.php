<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExternalWebviewTest extends TestCase
{
    public function test_public_external_webview_accepts_https_url(): void
    {
        $this->get('/webview?url='.urlencode('https://www.indiacode.nic.in/'))
            ->assertOk()
            ->assertSee('indiacode.nic.in');
    }

    public function test_external_webview_rejects_local_and_invalid_urls(): void
    {
        $this->get('/webview?url=http%3A%2F%2F127.0.0.1%3A8000%2F')
            ->assertStatus(422);

        $this->get('/webview?url=javascript%3Aalert%281%29')
            ->assertStatus(422);
    }
}
