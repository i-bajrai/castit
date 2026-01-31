<?php

namespace App\Console\Commands;

use Domain\Forecasting\Actions\SeedDemoProject as SeedDemoProjectAction;
use Illuminate\Console\Command;

class SeedDemoProject extends Command
{
    protected $signature = 'app:seed-demo-project';

    protected $description = 'Seed (or reset) the demo project for demo@castit.com';

    public function handle(SeedDemoProjectAction $action): int
    {
        $this->info('Seeding demo project...');

        $project = $action->execute();

        $this->info("Demo project \"{$project->name}\" seeded successfully.");
        $this->info('Login: demo@castit.com / password');

        return self::SUCCESS;
    }
}
