<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\User;
use Domain\Forecasting\Actions\CreateProject;
use Domain\Forecasting\DataTransferObjects\ProjectData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_project_for_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $data = new ProjectData(
            name: 'Highway Extension',
            description: 'A major highway project',
            projectNumber: 'HWY-001',
            originalBudget: 5000000,
        );

        $action = new CreateProject;
        $project = $action->execute($company, $data);

        $this->assertDatabaseHas('projects', [
            'company_id' => $company->id,
            'name' => 'Highway Extension',
            'description' => 'A major highway project',
            'project_number' => 'HWY-001',
            'original_budget' => 5000000,
        ]);

        $this->assertTrue($project->company->is($company));
    }

    public function test_creates_project_with_minimal_data(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $data = new ProjectData(
            name: 'Simple Project',
            description: null,
            projectNumber: null,
        );

        $action = new CreateProject;
        $project = $action->execute($company, $data);

        $this->assertDatabaseHas('projects', [
            'company_id' => $company->id,
            'name' => 'Simple Project',
            'original_budget' => 0,
        ]);
        $this->assertNull($project->description);
        $this->assertNull($project->project_number);
    }
}
