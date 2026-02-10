<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserHasCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_company_can_access_dashboard(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Test Co']);
        $owner->update(['company_id' => $company->id, 'company_role' => CompanyRole::Admin]);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_user_without_company_is_redirected_to_no_company_page(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('no-company'));
    }

    public function test_system_admin_without_company_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'company_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_removed_member_is_redirected_to_no_company_page(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Test Co']);
        $member = User::factory()->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Engineer,
            'company_removed_at' => now(),
        ]);

        $response = $this->actingAs($member)->get('/dashboard');

        $response->assertRedirect(route('no-company'));
    }

    public function test_no_company_page_renders_for_unassigned_user(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($user)->get('/no-company');

        $response->assertStatus(200);
        $response->assertSeeText('Not assigned to a company');
    }

    public function test_no_company_page_redirects_assigned_user_to_dashboard(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Test Co']);
        $owner->update(['company_id' => $company->id, 'company_role' => CompanyRole::Admin]);

        $response = $this->actingAs($owner)->get('/no-company');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_no_company_page_redirects_admin_to_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'company_id' => null,
        ]);

        $response = $this->actingAs($admin)->get('/no-company');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_company_routes_redirect_unassigned_user(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($user)->get('/company/members');

        $response->assertRedirect(route('no-company'));
    }
}
