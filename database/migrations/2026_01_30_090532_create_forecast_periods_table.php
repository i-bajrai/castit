<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forecast_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('period_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'period_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forecast_periods');
    }
};
