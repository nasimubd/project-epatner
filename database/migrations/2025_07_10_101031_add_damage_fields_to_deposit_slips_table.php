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
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->json('damage_lines')->nullable()->after('note_denominations');
            $table->decimal('market_short', 12, 2)->default(0)->after('damage_lines');
            $table->decimal('godown_short', 12, 2)->default(0)->after('market_short');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->dropColumn(['damage_lines', 'market_short', 'godown_short']);
        });
    }
};
