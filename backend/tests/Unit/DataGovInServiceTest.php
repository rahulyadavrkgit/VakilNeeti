<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardStudioController;
use App\Services\DataGovInService;
use PHPUnit\Framework\TestCase;

class DataGovInServiceTest extends TestCase
{
    public function test_it_accepts_valid_uuid_and_hex_resource_ids(): void
    {
        $validIds = [
            '550E8400-E29B-41D4-A716-446655440000',
            '579b464db66ec23bdd0000013166f8dc845748015713e7d09cfd3d20',
            'abc123def4567890abcd',
        ];

        foreach ($validIds as $value) {
            $this->assertTrue(DataGovInService::isValidResourceId($value));
        }
    }

    public function test_it_rejects_invalid_resource_ids(): void
    {
        $invalidIds = [
            'resource-id',
            'abc',
            'abc!123',
            'zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz',
        ];

        foreach ($invalidIds as $value) {
            $this->assertFalse(DataGovInService::isValidResourceId($value));
        }
    }

    public function test_it_normalizes_dashboard_visibility_values(): void
    {
        $this->assertSame('private', DashboardStudioController::normalizeVisibility('private'));
        $this->assertSame('public', DashboardStudioController::normalizeVisibility('PUBLIC'));
        $this->assertSame('private', DashboardStudioController::normalizeVisibility('shared'));
        $this->assertSame('private', DashboardStudioController::normalizeVisibility(null));
    }
}
