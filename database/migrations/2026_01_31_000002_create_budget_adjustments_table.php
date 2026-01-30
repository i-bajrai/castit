<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('control_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forecast_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('previous_approved_budget', 15, 2);
            $table->decimal('new_approved_budget', 15, 2);
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_adjustments');
    }
};
