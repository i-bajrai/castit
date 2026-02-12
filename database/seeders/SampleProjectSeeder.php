<?php

namespace Database\Seeders;

use Domain\Forecasting\Actions\SeedDemoProject;
use Illuminate\Database\Seeder;

class SampleProjectSeeder extends Seeder
{
    public function run(): void
    {
        app(SeedDemoProject::class)->execute();
    }
}
