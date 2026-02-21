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
        Schema::create('group_hikes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->longText('description');
            $table->string('short_description', 500)->nullable();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('trail_id')->nullable()->constrained('trails')->nullOnDelete();
            $table->string('custom_location_name', 255)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->text('meeting_point')->nullable();
            $table->date('start_date');
            $table->time('start_time');
            $table->date('end_date')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('max_participants')->nullable();
            $table->string('registration_url', 500)->nullable();
            $table->date('registration_deadline')->nullable();
            $table->text('registration_notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->char('price_currency', 3)->default('KES');
            $table->string('price_notes', 500)->nullable();
            $table->string('contact_name', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_whatsapp', 50)->nullable();
            $table->string('difficulty', 50)->nullable();
            $table->foreignId('featured_image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_notes', 255)->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('start_date');
            $table->index(['organizer_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index(['trail_id', 'status']);
            $table->index(['region_id', 'status']);
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_hikes');
    }
};
