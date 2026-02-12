<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_account_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forecast_period_id')->constrained()->cascadeOnDelete();

            $table->decimal('last_month_approved_budget', 15, 2)->default(0);
            $table->decimal('budget_movement', 15, 2)->default(0);
            $table->decimal('monthly_cost', 15, 2)->default(0);
            $table->decimal('cost_to_date', 15, 2)->default(0);
            $table->decimal('estimate_to_complete', 15, 2)->default(0);
            $table->decimal('estimated_final_cost', 15, 2)->default(0);
            $table->decimal('last_month_efc', 15, 2)->default(0);
            $table->decimal('efc_movement', 15, 2)->default(0);
            $table->text('monthly_comments')->nullable();

            $table->timestamps();

            $table->unique(['control_account_id', 'forecast_period_id'], 'ca_forecast_unique');
        });
    }
};
