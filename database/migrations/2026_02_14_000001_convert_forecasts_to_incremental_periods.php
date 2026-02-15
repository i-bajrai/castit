<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add new incremental columns
        Schema::table('line_item_forecasts', function (Blueprint $table) {
            $table->decimal('period_qty', 12, 2)->default(0)->after('forecast_period_id');
            $table->decimal('period_rate', 12, 2)->default(0)->after('period_qty');
        });

        // 2. Migrate existing data: period_qty = ctd_qty, period_rate = ctd_rate
        DB::statement('UPDATE line_item_forecasts SET period_qty = ctd_qty, period_rate = ctd_rate');

        // 3. Drop old stored computed columns first (they depend on the raw columns)
        Schema::table('line_item_forecasts', function (Blueprint $table) {
            $table->dropColumn([
                'variance',
                'ctc_amount',
                'ctc_qty',
                'ctd_amount',
                'previous_amount',
                'fcac_amount',
            ]);
        });

        // 4. Drop remaining old raw columns
        Schema::table('line_item_forecasts', function (Blueprint $table) {
            $table->dropColumn([
                'previous_qty',
                'previous_rate',
                'ctd_qty',
                'ctd_rate',
                'ctc_rate',
            ]);
        });

        // 5. Add new stored computed columns
        Schema::table('line_item_forecasts', function (Blueprint $table) {
            $table->decimal('period_amount', 15, 2)->storedAs('period_qty * period_rate')->after('period_rate');
            $table->decimal('fcac_amount', 15, 2)->storedAs('fcac_qty * fcac_rate')->after('fcac_rate');
        });
    }
};
