<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_items', function (Blueprint $table) {
            $table->foreignId('created_in_period_id')
                ->nullable()
                ->after('sort_order')
                ->constrained('forecast_periods')
                ->nullOnDelete();
        });
    }
};
