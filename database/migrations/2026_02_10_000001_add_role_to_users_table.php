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
            $table->string('role', 20)->default('user')->after('email');

            $table->foreignId('company_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('company_role', 20)->nullable()->after('company_id');
            $table->timestamp('company_removed_at')->nullable()->after('company_role');
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
    }
};
