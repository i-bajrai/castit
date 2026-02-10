<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('company_role', 20)->nullable()->after('company_id');
        });

        // Migrate existing company owners: set their company_id and make them company admins
        DB::table('companies')->get()->each(function ($company) {
            DB::table('users')
                ->where('id', $company->user_id)
                ->update([
                    'company_id' => $company->id,
                    'company_role' => 'admin',
                ]);
        });

        // Simplify system roles: project_manager, cost_controller, viewer -> user
        DB::table('users')
            ->whereIn('role', ['project_manager', 'cost_controller', 'viewer'])
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'company_role']);
        });
    }
};
