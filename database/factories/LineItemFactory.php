<?php

namespace Database\Factories;

use App\Models\CostPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LineItem>
 */
class LineItemFactory extends Factory
{
    public function definition(): array
    {
        $qty = fake()->numberBetween(10, 200);
        $rate = fake()->randomFloat(2, 50, 500);

        return [
            'cost_package_id' => CostPackage::factory(),
            'description' => fake()->sentence(3),
            'original_qty' => $qty,
            'original_rate' => $rate,
            'original_amount' => $qty * $rate,
            'sort_order' => 0,
        ];
    }
}
