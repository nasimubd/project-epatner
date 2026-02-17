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
        Schema::connection('mysql_common')->create('tbl_duplicate_detection', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('primary_ledger_id'); // The main record to keep
            $table->unsignedBigInteger('duplicate_ledger_id'); // The duplicate record
            $table->decimal('similarity_score', 5, 2); // Similarity percentage (0.00 to 100.00)
            $table->json('matching_fields'); // Which fields matched
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'merged'])->default('pending');
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['primary_ledger_id'], 'idx_primary_ledger');
            $table->index(['duplicate_ledger_id'], 'idx_duplicate_ledger');
            $table->index(['status'], 'idx_status');
            $table->index(['similarity_score'], 'idx_similarity_score');
            $table->index(['created_at'], 'idx_detection_created_at');

            // Unique constraint to prevent duplicate detection records
            $table->unique(['primary_ledger_id', 'duplicate_ledger_id'], 'unique_duplicate_pair');

            // Foreign keys
            $table->foreign('primary_ledger_id', 'fk_primary_ledger')
                ->references('ledger_id')
                ->on('tbl_customer_ledgers')
                ->onDelete('cascade');

            $table->foreign('duplicate_ledger_id', 'fk_duplicate_ledger')
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
        Schema::connection('mysql_common')->dropIfExists('tbl_duplicate_detection');
    }
};
