<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['salary_head_id']);
            $table->dropForeign(['role_id']);

            // Drop indexes
            $table->dropIndex(['salary_head_id']);
            $table->dropIndex(['staff_id']);
            $table->dropIndex(['status']);

            // Drop columns
            $table->dropColumn([
                'salary_head_id',
                'staff_id',
                'role_id',
                'basic_info',
                'status'
            ]);
        });
    }

    public function down(): void
    {
        // Reverse operation - same as original migration's up() method
        Schema::table('staff', function (Blueprint $table) {
            if (!Schema::hasColumn('staff', 'salary_head_id')) {
                $table->unsignedBigInteger('salary_head_id')->nullable()->after('user_id');
            }

            if (!Schema::hasColumn('staff', 'staff_id')) {
                $table->string('staff_id')->nullable()->after('salary_head_id');
            }

            if (!Schema::hasColumn('staff', 'role_id')) {
                $table->unsignedBigInteger('role_id')->nullable()->after('staff_id');
            }

            if (!Schema::hasColumn('staff', 'basic_info')) {
                $table->json('basic_info')->nullable()->after('role_id');
            }

            if (!Schema::hasColumn('staff', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('basic_info');
            }

            $table->foreign('salary_head_id')->references('id')->on('salary_heads')->onDelete('set null');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');

            $table->index(['salary_head_id']);
            $table->index(['staff_id']);
            $table->index(['status']);
        });
    }
};
