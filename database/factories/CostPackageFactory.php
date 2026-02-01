<?php

namespace Database\Factories;

use App\Models\ControlAccount;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CostPackage>
 */
class CostPackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'control_account_id' => ControlAccount::factory(),
            'name' => fake()->words(2, true),
            'sort_order' => 0,
        ];
    }
}
