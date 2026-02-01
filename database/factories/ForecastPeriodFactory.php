<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ForecastPeriod>
 */
class ForecastPeriodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'period_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-01'),
        ];
    }
}
