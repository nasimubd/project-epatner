<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->decimal('due_collection', 15, 2)->default(0)->after('total_collection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->dropColumn('due_collection');
        });
    }
};
