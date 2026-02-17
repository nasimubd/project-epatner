<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_common')->create('tbl_customer_ledger_audit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ledger_id');
            $table->string('action', 50); // 'created', 'updated', 'merged', 'deleted'
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('changed_by')->nullable(); // user who made the change
            $table->string('reason')->nullable(); // reason for change
            $table->timestamps();

            // Indexes
            $table->index(['ledger_id'], 'idx_audit_ledger_id');
            $table->index(['action'], 'idx_audit_action');
            $table->index(['created_at'], 'idx_audit_created_at');
            $table->index(['changed_by'], 'idx_audit_changed_by');

            // Foreign key
            $table->foreign('ledger_id', 'fk_audit_ledger_id')
                ->references('ledger_id')
                ->on('tbl_customer_ledgers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->dropIfExists('tbl_customer_ledger_audit');
    }
};
