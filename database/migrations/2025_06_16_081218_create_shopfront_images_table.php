<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopfront_images', function (Blueprint $table) {
            $table->id();
            $table->string('shopfront_id');
            $table->string('image_type'); // 'hero_banner', 'category'
            $table->string('reference_id')->nullable(); // category_id for category images, null for hero banner
            // We'll add image column after table creation using raw SQL
            $table->string('image_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->integer('image_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Add indexes
            $table->index(['shopfront_id', 'image_type']);
            $table->index(['shopfront_id', 'image_type', 'reference_id']);
        });

        // Add LONGBLOB column using raw SQL (MySQL specific)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE shopfront_images ADD image LONGBLOB AFTER reference_id');
        } else {
            // For other databases, use longText
            Schema::table('shopfront_images', function (Blueprint $table) {
                $table->longText('image')->after('reference_id');
            });
        }

        // Add foreign key constraint after all columns are created
        Schema::table('shopfront_images', function (Blueprint $table) {
            $table->foreign('shopfront_id')->references('shopfront_id')->on('business_shopfronts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopfront_images');
    }
};
