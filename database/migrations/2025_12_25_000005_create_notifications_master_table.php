<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications_master', function (Blueprint $table) {
            $table->id();

            // User reference
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Counters for optimization
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('unread_count')->default(0);

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

            // Indexes
            $table->index('unread_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_master');
    }
};
