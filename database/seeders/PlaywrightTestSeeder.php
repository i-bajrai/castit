<?php

namespace Database\Seeders;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlaywrightTestSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'playwright@test.com',
            'password' => 'password',
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'Test Company',
        ]);

        $user->update([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Admin,
        ]);
    }
}
