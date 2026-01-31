<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ControlAccount>
 */
class ControlAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'phase' => '',
            'code' => fake()->bothify('###??00'),
            'description' => fake()->sentence(3),
            'sort_order' => 0,
        ];
    }
}
