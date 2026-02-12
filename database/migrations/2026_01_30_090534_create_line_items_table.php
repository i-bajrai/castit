<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_package_id')->constrained()->cascadeOnDelete();
            $table->string('item_no')->nullable();
            $table->string('description');
            $table->string('unit_of_measure')->nullable();
            $table->decimal('original_qty', 12, 2)->default(0);
            $table->decimal('original_rate', 12, 2)->default(0);
            $table->decimal('original_amount', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
