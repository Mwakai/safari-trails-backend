<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * County-to-region mapping (47 Kenyan counties → 8 regions).
     *
     * @var array<string, string>
     */
    private array $countyToRegion = [
        // Central
        'nyeri' => 'central',
        'kirinyaga' => 'central',
        "murang'a" => 'central',
        'muranga' => 'central',
        'kiambu' => 'central',
        'nyandarua' => 'central',
        // Coast
        'mombasa' => 'coast',
        'kilifi' => 'coast',
        'kwale' => 'coast',
        'tana river' => 'coast',
        'lamu' => 'coast',
        'taita-taveta' => 'coast',
        'taita taveta' => 'coast',
        // Eastern
        'embu' => 'eastern',
        'meru' => 'eastern',
        'tharaka-nithi' => 'eastern',
        'tharaka nithi' => 'eastern',
        'isiolo' => 'eastern',
        'marsabit' => 'eastern',
        'kitui' => 'eastern',
        'makueni' => 'eastern',
        'machakos' => 'eastern',
        // Nairobi
        'nairobi' => 'nairobi',
        // North Eastern
        'garissa' => 'north-eastern',
        'wajir' => 'north-eastern',
        'mandera' => 'north-eastern',
        // Nyanza
        'kisumu' => 'nyanza',
        'siaya' => 'nyanza',
        'homa bay' => 'nyanza',
        'migori' => 'nyanza',
        'kisii' => 'nyanza',
        'nyamira' => 'nyanza',
        // Rift Valley
        'nakuru' => 'rift-valley',
        'narok' => 'rift-valley',
        'kajiado' => 'rift-valley',
        'kericho' => 'rift-valley',
        'bomet' => 'rift-valley',
        'nandi' => 'rift-valley',
        'uasin gishu' => 'rift-valley',
        'trans-nzoia' => 'rift-valley',
        'trans nzoia' => 'rift-valley',
        'elgeyo-marakwet' => 'rift-valley',
        'elgeyo marakwet' => 'rift-valley',
        'west pokot' => 'rift-valley',
        'turkana' => 'rift-valley',
        'samburu' => 'rift-valley',
        'baringo' => 'rift-valley',
        'laikipia' => 'rift-valley',
        // Western
        'kakamega' => 'western',
        'vihiga' => 'western',
        'bungoma' => 'western',
        'busia' => 'western',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Add region_id as nullable
        Schema::table('trails', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('location_name')->constrained('regions');
        });

        // Step 2: Map county values to region_id
        $this->migrateCountyToRegion();

        // Step 3: Drop index on county (separate call for SQLite)
        Schema::table('trails', function (Blueprint $table) {
            $table->dropIndex(['county']);
        });

        // Step 4: Drop the county column
        Schema::table('trails', function (Blueprint $table) {
            $table->dropColumn('county');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trails', function (Blueprint $table) {
            $table->string('county', 100)->after('location_name');
        });

        Schema::table('trails', function (Blueprint $table) {
            $table->index('county');
        });

        Schema::table('trails', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }

    /**
     * Map existing county values to region IDs.
     */
    private function migrateCountyToRegion(): void
    {
        // Build slug → id lookup from regions table
        $regionIds = DB::table('regions')->pluck('id', 'slug');

        if ($regionIds->isEmpty()) {
            return;
        }

        // Get distinct county values that still need mapping
        $trails = DB::table('trails')->whereNull('region_id')->whereNotNull('county')->get(['id', 'county']);

        foreach ($trails as $trail) {
            $countyLower = strtolower(trim($trail->county));
            $regionSlug = $this->countyToRegion[$countyLower] ?? null;

            if ($regionSlug && $regionIds->has($regionSlug)) {
                DB::table('trails')->where('id', $trail->id)->update([
                    'region_id' => $regionIds[$regionSlug],
                ]);
            }
        }

        // Assign any remaining unmapped trails to the first region as fallback
        $fallbackId = $regionIds->first();
        DB::table('trails')->whereNull('region_id')->update([
            'region_id' => $fallbackId,
        ]);
    }
};
