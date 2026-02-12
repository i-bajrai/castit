<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\SyncForecastPeriods;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncForecastPeriodsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(array $attributes = []): Project
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        return Project::create(array_merge([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'original_budget' => 100000,
        ], $attributes));
    }

    public function test_it_generates_periods_from_start_date_to_end_date(): void
    {
        $project = $this->createProject([
            'start_date' => '2024-01-15',
            'end_date' => '2024-04-20',
        ]);

        app(SyncForecastPeriods::class)->execute($project);

        $periods = ForecastPeriod::where('project_id', $project->id)
            ->orderBy('period_date')
            ->get();

        $this->assertCount(4, $periods);
        $this->assertEquals('2024-01-01', $periods[0]->period_date->toDateString());
        $this->assertEquals('2024-02-01', $periods[1]->period_date->toDateString());
        $this->assertEquals('2024-03-01', $periods[2]->period_date->toDateString());
        $this->assertEquals('2024-04-01', $periods[3]->period_date->toDateString());
    }

    public function test_it_does_nothing_when_dates_are_null(): void
    {
        $project = $this->createProject([
            'start_date' => null,
            'end_date' => null,
        ]);

        app(SyncForecastPeriods::class)->execute($project);

        $this->assertDatabaseCount('forecast_periods', 0);
    }

    public function test_it_does_not_duplicate_existing_periods(): void
    {
        $project = $this->createProject([
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
        ]);

        // Run the action twice to verify idempotency
        $action = app(SyncForecastPeriods::class);
        $action->execute($project);
        $action->execute($project);

        $periods = ForecastPeriod::where('project_id', $project->id)->get();

        $this->assertCount(3, $periods);
    }
}
