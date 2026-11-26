<?php

namespace Tests\Feature;

use App\Http\Controllers\ExternalWebviewController;
use Tests\TestCase;

class OfficialWebsitesTest extends TestCase
{
    public function test_only_official_legal_and_government_hosts_count_as_official(): void
    {
        $this->assertTrue(ExternalWebviewController::isOfficialHost('indiacode.gov.in'));
        $this->assertTrue(ExternalWebviewController::isOfficialHost('delhihighcourt.nic.in'));

        $this->assertFalse(ExternalWebviewController::isOfficialHost(''));
        $this->assertFalse(ExternalWebviewController::isOfficialHost('127.0.0.1'));
        $this->assertFalse(ExternalWebviewController::isOfficialHost('example.com'));
        $this->assertFalse(ExternalWebviewController::isOfficialHost('gov.in.example.com'));
        $this->assertFalse(ExternalWebviewController::isOfficialHost('notgov.in'));
    }

    public function test_responses_carry_security_headers(): void
    {
        $this->get(route('courts.index'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
