<?php

namespace Tests\Feature\Auth;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'Test Company',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_creates_company_and_assigns_user_as_admin(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'company_name' => 'My Company',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($user);
        $this->assertNotNull($user->company_id);
        $this->assertEquals(CompanyRole::Admin, $user->company_role);

        $company = Company::find($user->company_id);
        $this->assertNotNull($company);
        $this->assertEquals('My Company', $company->name);
        $this->assertEquals($user->id, $company->user_id);
    }

    public function test_registration_requires_company_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('company_name');
    }
}
