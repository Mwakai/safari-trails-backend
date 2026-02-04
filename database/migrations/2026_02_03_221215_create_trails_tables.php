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
        Schema::create('trails', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('short_description', 500)->nullable();

            $table->string('difficulty');
            $table->decimal('distance_km', 6, 2);
            $table->decimal('duration_hours', 4, 1);
            $table->unsignedInteger('elevation_gain_m')->nullable();
            $table->unsignedInteger('max_altitude_m')->nullable();

            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('location_name');
            $table->string('county', 100);

            $table->string('route_a_name')->nullable();
            $table->text('route_a_description')->nullable();
            $table->decimal('route_a_latitude', 10, 8)->nullable();
            $table->decimal('route_a_longitude', 11, 8)->nullable();

            $table->boolean('route_b_enabled')->default(false);
            $table->string('route_b_name')->nullable();
            $table->text('route_b_description')->nullable();
            $table->decimal('route_b_latitude', 10, 8)->nullable();
            $table->decimal('route_b_longitude', 11, 8)->nullable();

            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('video_url', 500)->nullable();

            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('difficulty');
            $table->index('county');
            $table->index(['latitude', 'longitude']);
            $table->index('published_at');
        });

        Schema::create('trail_amenity', function (Blueprint $table) {
            $table->foreignId('trail_id')->constrained('trails')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['trail_id', 'amenity_id']);
        });

        Schema::create('trail_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained('trails')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('type');
            $table->string('caption')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['trail_id', 'type']);
            $table->index(['trail_id', 'type', 'sort_order']);
        });

        Schema::create('trail_gpx', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained('trails')->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('trail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_gpx');
        Schema::dropIfExists('trail_images');
        Schema::dropIfExists('trail_amenity');
        Schema::dropIfExists('trails');
    }
};
