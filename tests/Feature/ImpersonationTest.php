<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createRegularUserWithCompany(): User
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Test Co']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Engineer,
        ]);

        return $user;
    }

    public function test_super_admin_can_impersonate_regular_user(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createRegularUserWithCompany();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_impersonation_stores_admin_id_in_session(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createRegularUserWithCompany();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user));

        $this->assertEquals($admin->id, session('impersonating_from'));
    }

    public function test_super_admin_cannot_impersonate_themselves(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'You cannot impersonate yourself.');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_super_admin_cannot_impersonate_another_admin(): void
    {
        $admin1 = $this->createSuperAdmin();
        $admin2 = $this->createSuperAdmin();

        $response = $this->actingAs($admin1)
            ->post(route('admin.users.impersonate', $admin2));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'You cannot impersonate another admin.');
        $this->assertAuthenticatedAs($admin1);
    }

    public function test_regular_user_cannot_impersonate(): void
    {
        $user = $this->createRegularUserWithCompany();
        $otherUser = $this->createRegularUserWithCompany();

        $response = $this->actingAs($user)
            ->post(route('admin.users.impersonate', $otherUser));

        $response->assertForbidden();
    }

    public function test_stop_impersonating_returns_to_admin(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createRegularUserWithCompany();

        // Start impersonating
        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user));

        // Stop impersonating
        $response = $this->post(route('stop-impersonating'));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonating_from'));
    }

    public function test_impersonation_banner_visible_when_impersonating(): void
    {
        $admin = $this->createSuperAdmin();
        $user = $this->createRegularUserWithCompany();

        // Start impersonating
        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $user));

        // Visit a page as the impersonated user
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('You are impersonating');
        $response->assertSee($user->name);
        $response->assertSee('Stop Impersonating');
    }

    public function test_impersonation_banner_not_visible_normally(): void
    {
        $user = $this->createRegularUserWithCompany();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('You are impersonating');
        $response->assertDontSee('Stop Impersonating');
    }

    public function test_guest_cannot_access_impersonate_route(): void
    {
        $user = User::factory()->create();

        $this->post(route('admin.users.impersonate', $user))
            ->assertRedirect('/login');
    }

    public function test_guest_cannot_access_stop_impersonating_route(): void
    {
        $this->post(route('stop-impersonating'))
            ->assertRedirect('/login');
    }
}
