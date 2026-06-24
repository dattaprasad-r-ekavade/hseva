<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Route;
use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\Concerns\SeedsHrWorkflow;
use Tests\TestCase;

class ApiRouteCoverageTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;
    use SeedsHrWorkflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_all_registered_api_routes_are_reachable(): void
    {
        $token = $this->superAdminToken();
        [$clientId, $headers] = $this->tenantContext($token);
        $this->seedEmployee($headers, ['id' => 'EMP100', 'baseCtc' => 25000]);
        $this->seedAttendanceSheet($headers, 6, 2026);
        $this->seedPayrollSheet($headers, 6, 2026);

        $publicChecks = [
            ['GET', '/api/health', [], 200],
            ['POST', '/api/public-enquiries', [
                'fullName' => 'Test User',
                'companyName' => 'Acme',
                'workEmail' => 'test@example.com',
                'phoneNo' => '9999999999',
                'productInterest' => 'HR Portal',
                'preferredDate' => '2026-07-01',
                'message' => 'Need HR portal demo',
            ], 201],
        ];

        foreach ($publicChecks as [$method, $uri, $payload, $expected]) {
            $response = $this->json($method, $uri, $payload);
            $this->assertContains($response->status(), [$expected, 422], "Unexpected status for {$method} {$uri}");
        }

        $tenantChecks = [
            ['GET', '/api/dashboard/summary?month=6&year=2026'],
            ['GET', '/api/employees'],
            ['GET', '/api/leaves'],
            ['GET', '/api/attendance/daily?month=6&year=2026'],
            ['GET', '/api/attendance/sheets'],
            ['GET', '/api/payroll/sheets'],
            ['GET', '/api/payroll/overrides'],
            ['GET', '/api/pf-sheet/sheets'],
            ['GET', '/api/pf-return/sheets'],
            ['GET', '/api/esic-sheet/sheets'],
            ['GET', '/api/ecr-sheet/sheets'],
            ['GET', '/api/esic-return/sheets'],
            ['GET', '/api/fnf/sheets'],
            ['GET', '/api/gratuity/sheets'],
            ['GET', '/api/bonus/sheets'],
            ['GET', '/api/incentives'],
            ['GET', '/api/payslips'],
            ['GET', '/api/compliance/tasks?month=6&year=2026'],
            ['GET', '/api/compliance/challans'],
            ['GET', '/api/overtime'],
            ['GET', '/api/advances'],
            ['GET', '/api/loans'],
            ['GET', '/api/control'],
            ['GET', '/api/profile'],
            ['GET', '/api/subscription-info'],
            ['GET', '/api/client-billing'],
            ['GET', '/api/client-invoices'],
            ['GET', '/api/client-access-template'],
            ['GET', '/api/attendance-statuses'],
            ['GET', '/api/employee-types'],
            ['GET', '/api/staff-roles'],
            ['GET', '/api/staff-users'],
            ['GET', '/api/shift/dashboard'],
            ['GET', '/api/shifts'],
            ['GET', '/api/shift-assignments'],
            ['GET', '/api/rosters'],
            ['GET', '/api/my-shifts'],
            ['GET', '/api/face-attendance/settings'],
            ['GET', '/api/face-attendance/registrations'],
            ['GET', '/api/face-attendance/sheet'],
        ];

        foreach ($tenantChecks as [$method, $uri]) {
            $response = $this->withHeaders($headers)->json($method, $uri);
            $this->assertTrue(
                $response->status() >= 200 && $response->status() < 500,
                "Server error on {$method} {$uri}: ".$response->status()
            );
        }

        $adminChecks = [
            ['GET', '/api/clients'],
            ['GET', '/api/subscriptions'],
            ['GET', '/api/subscription-plans'],
            ['GET', '/api/access-types'],
            ['GET', "/api/access-control/{$clientId}"],
            ['GET', '/api/admin-enquiries'],
            ['GET', '/api/admin-smtp-settings'],
        ];

        foreach ($adminChecks as [$method, $uri]) {
            $response = $this->withHeader('Authorization', 'Bearer '.$token)->json($method, $uri);
            $this->assertTrue(
                $response->status() >= 200 && $response->status() < 500,
                "Server error on {$method} {$uri}: ".$response->status()
            );
        }

        $routeCount = collect(Route::getRoutes())->filter(fn ($r) => str_starts_with($r->uri(), 'api/'))->count();
        $this->assertGreaterThanOrEqual(120, $routeCount, 'Expected full API surface to remain registered');
    }

    public function test_unauthenticated_protected_routes_return_401(): void
    {
        $this->getJson('/api/employees')->assertUnauthorized();
        $this->getJson('/api/clients')->assertUnauthorized();
    }
}
