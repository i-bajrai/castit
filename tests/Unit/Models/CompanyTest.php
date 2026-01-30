<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $this->assertTrue($company->user->is($user));
    }

    public function test_company_has_many_projects(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        Project::create(['company_id' => $company->id, 'name' => 'Project A', 'original_budget' => 100000]);
        Project::create(['company_id' => $company->id, 'name' => 'Project B', 'original_budget' => 200000]);

        $this->assertCount(2, $company->projects);
    }

    public function test_user_has_many_companies(): void
    {
        $user = User::factory()->create();

        Company::create(['user_id' => $user->id, 'name' => 'Company A']);
        Company::create(['user_id' => $user->id, 'name' => 'Company B']);

        $this->assertCount(2, $user->companies);
    }

    public function test_project_belongs_to_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test Project', 'original_budget' => 100000]);

        $this->assertTrue($project->company->is($company));
    }

    public function test_deleting_company_cascades_to_projects(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        Project::create(['company_id' => $company->id, 'name' => 'Doomed Project', 'original_budget' => 100000]);

        $company->delete();

        $this->assertDatabaseMissing('projects', ['name' => 'Doomed Project']);
    }

    public function test_deleting_user_cascades_to_companies(): void
    {
        $user = User::factory()->create();
        Company::create(['user_id' => $user->id, 'name' => 'Doomed Co']);

        $user->delete();

        $this->assertDatabaseMissing('companies', ['name' => 'Doomed Co']);
    }
}
