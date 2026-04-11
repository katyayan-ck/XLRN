<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Conversation participants
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('receiver_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Message content
            $table->text('message_text');
            $table->string('message_type')->default('text'); // 'text', 'image', 'file', 'voice'

            // Threading support
            $table->unsignedBigInteger('reply_to_id')->nullable(); // For message threading

            // Status & tracking
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_sent_via_fcm')->default(false);
            $table->timestamp('sent_at')->nullable();

            // Media attachments
            $table->json('attachments')->nullable(); // Array of file paths/URLs

            // Additional data
            $table->json('metadata')->nullable();

            // Audit fields
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

            // Indexes for conversation queries
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('is_read');
            $table->index(['sender_id', 'receiver_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
