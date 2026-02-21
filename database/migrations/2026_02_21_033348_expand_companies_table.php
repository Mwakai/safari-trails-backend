<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->foreignId('logo_id')->nullable()->after('description')->constrained('media')->nullOnDelete();
            $table->foreignId('cover_image_id')->nullable()->after('logo_id')->constrained('media')->nullOnDelete();
            $table->string('website', 500)->nullable()->after('cover_image_id');
            $table->string('email', 255)->nullable()->after('website');
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('whatsapp', 50)->nullable()->after('phone');
            $table->string('instagram', 255)->nullable()->after('whatsapp');
            $table->string('facebook', 255)->nullable()->after('instagram');
            $table->boolean('is_verified')->default(false)->after('facebook');
            $table->boolean('is_active')->default(true)->after('is_verified');
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('active')->after('slug');
            $table->dropColumn([
                'description',
                'logo_id',
                'cover_image_id',
                'website',
                'email',
                'phone',
                'whatsapp',
                'instagram',
                'facebook',
                'is_verified',
                'is_active',
            ]);
        });
    }
};
