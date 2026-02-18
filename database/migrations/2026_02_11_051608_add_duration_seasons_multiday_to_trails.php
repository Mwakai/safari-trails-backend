<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns alongside duration_hours first
        Schema::table('trails', function (Blueprint $table) {
            $table->string('duration_type', 10)->default('hours')->after('distance_km');
            $table->decimal('duration_min', 4, 1)->nullable()->after('duration_type');
            $table->decimal('duration_max', 4, 1)->nullable()->after('duration_min');

            $table->boolean('is_year_round')->default(true)->after('max_altitude_m');
            $table->text('season_notes')->nullable()->after('is_year_round');

            $table->boolean('requires_guide')->default(false)->after('season_notes');
            $table->boolean('requires_permit')->default(false)->after('requires_guide');
            $table->text('permit_info')->nullable()->after('requires_permit');
            $table->json('accommodation_types')->nullable()->after('permit_info');
        });

        // Migrate existing data: copy duration_hours → duration_min
        if (Schema::hasColumn('trails', 'duration_hours')) {
            DB::table('trails')->whereNotNull('duration_hours')->update([
                'duration_min' => DB::raw('duration_hours'),
                'duration_type' => 'hours',
            ]);

            // Drop the existing index on duration_hours first (SQLite requires this before dropping the column)
            Schema::table('trails', function (Blueprint $table) {
                $table->dropIndex(['duration_hours']);
            });

            // Drop the old column in its own call
            Schema::table('trails', function (Blueprint $table) {
                $table->dropColumn('duration_hours');
            });
        }

        // Add new index
        Schema::table('trails', function (Blueprint $table) {
            $table->index('duration_type');
        });

        // Create trail_best_months table
        Schema::create('trail_best_months', function (Blueprint $table) {
            $table->foreignId('trail_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('month');
            $table->timestamp('created_at')->nullable();

            $table->primary(['trail_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_best_months');

        Schema::table('trails', function (Blueprint $table) {
            $table->decimal('duration_hours', 4, 1)->nullable()->after('distance_km');
        });

        DB::table('trails')->whereNotNull('duration_min')->update([
            'duration_hours' => DB::raw('duration_min'),
        ]);

        Schema::table('trails', function (Blueprint $table) {
            $table->dropIndex(['duration_type']);
            $table->dropColumn([
                'duration_type',
                'duration_min',
                'duration_max',
                'is_year_round',
                'season_notes',
                'requires_guide',
                'requires_permit',
                'permit_info',
                'accommodation_types',
            ]);
        });
    }
};
