<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->index('type');
            $table->index('uploaded_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_by');
        });

        Schema::table('trails', function (Blueprint $table) {
            $table->index('created_by');
            $table->index('distance_km');
            $table->index('duration_hours');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropIndex(['uploaded_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('trails', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['distance_km']);
            $table->dropIndex(['duration_hours']);
        });
    }
};
