<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Device information
            $table->string('device_id')->unique(); // IMEI or UUID
            $table->string('device_name'); // e.g., "Moto G80"
            $table->string('platform'); // iOS, Android, Web
            $table->string('platform_version')->nullable(); // e.g., "14.5"

            // Firebase token
            $table->text('fcm_token'); // Firebase Cloud Messaging token
            $table->boolean('is_active')->default(true);
            $table->timestamp('token_expires_at')->nullable();

            // Activity tracking
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_notification_sent_at')->nullable();
            $table->integer('notification_count')->default(0);

            // Metadata
            $table->json('metadata')->nullable(); // App version, OS version, etc.
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();

            // Audit fields
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index('user_id');
            $table->index('device_id');
            $table->index('platform');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
