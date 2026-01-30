<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\LockForecastPeriod;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockForecastPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_locks_period(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $action = new LockForecastPeriod;
        $locked = $action->execute($period);

        $this->assertTrue($locked->isLocked());
        $this->assertNotNull($locked->locked_at);
    }

    public function test_throws_when_already_locked(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => false,
            'locked_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Period is already locked.');

        $action = new LockForecastPeriod;
        $action->execute($period);
    }
}
