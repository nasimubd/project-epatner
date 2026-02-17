<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsr_staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dsr_staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('assigned_staff_id')->constrained('staff')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique assignments (prevent duplicate assignments)
            $table->unique(['dsr_staff_id', 'assigned_staff_id']);

            // Add indexes for better performance
            $table->index('dsr_staff_id');
            $table->index('assigned_staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsr_staff_assignments');
    }
};
