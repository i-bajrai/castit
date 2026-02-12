<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_item_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('line_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('forecast_period_id')->constrained()->cascadeOnDelete();

            // Previous Forecast Cost at Completion
            $table->decimal('previous_qty', 12, 2)->default(0);
            $table->decimal('previous_rate', 12, 2)->default(0);
            $table->decimal('previous_amount', 15, 2)->storedAs('previous_qty * previous_rate');

            // Cost to Date
            $table->decimal('ctd_qty', 12, 2)->default(0);
            $table->decimal('ctd_rate', 12, 2)->default(0);
            $table->decimal('ctd_amount', 15, 2)->storedAs('ctd_qty * ctd_rate');

            // Cost to Complete
            $table->decimal('ctc_qty', 12, 2)->default(0);
            $table->decimal('ctc_rate', 12, 2)->default(0);
            $table->decimal('ctc_amount', 15, 2)->storedAs('ctc_qty * ctc_rate');

            // Forecast Cost at Completion
            $table->decimal('fcac_rate', 12, 2)->default(0);
            $table->decimal('fcac_amount', 15, 2)->default(0);
            $table->decimal('variance', 15, 2)->default(0);

            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['line_item_id', 'forecast_period_id']);
        });
    }
};
