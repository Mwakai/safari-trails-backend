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
        Schema::create('trail_itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->decimal('distance_km', 5, 2)->nullable();
            $table->integer('elevation_gain_m')->nullable();
            $table->string('start_point', 255)->nullable();
            $table->string('end_point', 255)->nullable();
            $table->string('accommodation', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['trail_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_itinerary_days');
    }
};
