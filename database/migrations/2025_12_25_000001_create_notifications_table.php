<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // User relationship
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('sender_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Notification content
            $table->string('type'); // 'booking', 'quote', 'sale', 'message', 'system'
            $table->string('title');
            $table->text('description');

            // Reference to entity (for deep linking & context)
            $table->string('reference_type')->nullable(); // 'Booking', 'Quote', 'Sale'
            $table->unsignedBigInteger('reference_id')->nullable();

            // Status & tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent_via_fcm')->default(false);
            $table->timestamp('sent_at')->nullable();

            // Priority & categorization
            $table->string('priority')->default('normal'); // 'low', 'normal', 'high', 'critical'
            $table->string('category')->nullable();

            // Deep linking payload
            $table->json('payload')->nullable(); // Contains deep_link, action, etc.
            $table->json('metadata')->nullable();

            // Audit fields (from BaseModel)
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes for performance
            $table->index('user_id');
            $table->index('sender_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('priority');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
