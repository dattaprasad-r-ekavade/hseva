<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithHrApi;
use Tests\Concerns\ResetsHrDatabases;
use Tests\Concerns\SeedsHrWorkflow;
use Tests\TestCase;

class CrudModulesParityTest extends TestCase
{
    use InteractsWithHrApi;
    use ResetsHrDatabases;
    use SeedsHrWorkflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resetHrDatabases();
    }

    public function test_attendance_status_crud(): void
    {
        $token = $this->superAdminToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/attendance-statuses')
            ->assertOk()
            ->assertJsonStructure(['rows']);

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/attendance-statuses', [
                'code' => 'HD',
                'shortLabel' => 'HD',
                'fullLabel' => 'Half Day',
                'buttonClass' => 'btn-outline-info',
                'sortOrder' => 65,
                'isActive' => true,
                'noteRequired' => false,
                'isPaid' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('row.code', 'HD');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/attendance-statuses/HD', [
                'fullLabel' => 'Half Day Updated',
            ])
            ->assertOk()
            ->assertJsonPath('row.fullLabel', 'Half Day Updated');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/attendance-statuses/HD')
            ->assertOk()
            ->assertJsonPath('status', 'deleted');
    }

    public function test_employee_type_crud(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);

        $this->withHeaders($headers)
            ->getJson('/api/employee-types')
            ->assertOk()
            ->assertJsonStructure(['rows']);

        $this->withHeaders($headers)
            ->postJson('/api/employee-types', [
                'code' => 'CONTRACTOR',
                'label' => 'Contractor',
                'sortOrder' => 70,
                'isActive' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('row.code', 'CONTRACTOR');

        $this->withHeaders($headers)
            ->putJson('/api/employee-types/CONTRACTOR', [
                'label' => 'External Contractor',
            ])
            ->assertOk()
            ->assertJsonPath('row.label', 'External Contractor');

        $this->withHeaders($headers)
            ->deleteJson('/api/employee-types/CONTRACTOR')
            ->assertOk()
            ->assertJsonPath('status', 'deleted');
    }

    public function test_incentive_crud(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers);

        $create = $this->withHeaders($headers)
            ->postJson('/api/incentives', [
                'empId' => $empId,
                'incentiveDate' => '2026-06-15',
                'amount' => 1500,
                'remarks' => 'Performance bonus',
            ])
            ->assertCreated()
            ->assertJsonPath('row.empId', $empId)
            ->assertJsonPath('row.amount', 1500);

        $id = (string) $create->json('row.id');

        $this->withHeaders($headers)
            ->getJson('/api/incentives/'.$id)
            ->assertOk()
            ->assertJsonPath('row.remarks', 'Performance bonus');

        $this->withHeaders($headers)
            ->deleteJson('/api/incentives/'.$id)
            ->assertOk()
            ->assertJsonPath('status', 'deleted');
    }

    public function test_overtime_create_and_delete(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);
        $empId = $this->seedEmployee($headers);

        $create = $this->withHeaders($headers)
            ->postJson('/api/overtime', [
                'empId' => $empId,
                'otDate' => '2026-06-20',
                'startTime' => '18:00',
                'endTime' => '21:00',
                'rate' => 200,
                'notes' => 'Release support',
            ])
            ->assertCreated()
            ->assertJsonPath('row.empId', $empId)
            ->assertJsonPath('row.totalHours', 3)
            ->assertJsonPath('row.amount', 600);

        $id = (string) $create->json('row.id');

        $this->withHeaders($headers)
            ->getJson('/api/overtime')
            ->assertOk()
            ->assertJsonFragment(['id' => $id, 'empId' => $empId]);

        $this->withHeaders($headers)
            ->deleteJson('/api/overtime/'.urlencode($id))
            ->assertOk()
            ->assertJsonPath('status', 'deleted');
    }

    public function test_crud_repositories_are_bound_in_container(): void
    {
        $repos = [
            \App\Services\MasterData\MasterDataRepository::class,
            \App\Services\Incentives\IncentiveRepository::class,
            \App\Services\Overtime\OvertimeRepository::class,
            \App\Services\Compliance\ComplianceRepository::class,
            \App\Services\Employees\EmployeeRepository::class,
            \App\Services\Leaves\LeaveRepository::class,
            \App\Services\Enquiries\EnquiryRepository::class,
            \App\Services\Clients\ClientRepository::class,
            \App\Services\Subscriptions\SubscriptionRepository::class,
            \App\Services\Access\AccessRepository::class,
            \App\Services\Billing\BillingRepository::class,
            \App\Services\Loans\LoanRepository::class,
            \App\Services\Advances\AdvanceRepository::class,
            \App\Services\FaceAttendance\FaceAttendanceRepository::class,
            \App\Services\Auth\AuthLoginRepository::class,
        ];
        foreach ($repos as $repo) {
            $this->assertTrue(app()->bound($repo), $repo.' should be bound');
        }
    }

    public function test_employee_and_leave_crud(): void
    {
        $token = $this->superAdminToken();
        [, $headers] = $this->tenantContext($token);

        $this->withHeaders($headers)
            ->postJson('/api/employees', [
                'id' => 'EMP200',
                'name' => 'Leave Test Employee',
                'status' => 'Active',
                'dept' => 'HR',
                'desig' => 'Exec',
                'type' => 'Full-time',
                'mobile' => '9999999998',
                'email' => 'emp200@example.com',
                'doj' => '2024-06-01',
                'pf' => 'Yes',
                'uan' => '100000000200',
                'esi' => 'Yes',
                'esiNo' => '1234567891',
                'baseCtc' => 25000,
            ])
            ->assertCreated();

        $leave = $this->withHeaders($headers)
            ->postJson('/api/leaves', [
                'empId' => 'EMP200',
                'empName' => 'Leave Test Employee',
                'fromDate' => '2026-06-10',
                'toDate' => '2026-06-11',
                'days' => 2,
                'leaveType' => 'CL',
                'reason' => 'Personal',
                'status' => 'Approved',
            ])
            ->assertCreated()
            ->json('row');

        $this->withHeaders($headers)
            ->getJson('/api/leaves/summary?month=6&year=2026')
            ->assertOk()
            ->assertJsonFragment(['empId' => 'EMP200', 'clDays' => 2]);
    }
}
