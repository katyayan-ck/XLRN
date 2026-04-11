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

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();

            // User who performed the export
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // File information
            $table->string('filename');
            $table->enum('export_type', [
                'standard_users',
                'rules_users',
                'vehicle_inventory',
                'custom'
            ])->default('standard_users');

            // Export statistics
            $table->integer('total_records')->default(0);

            // Filter information (JSON)
            $table->json('filters')->nullable();

            // File details
            $table->string('file_path')->nullable();
            $table->integer('file_size')->nullable(); // Size in bytes

            // Export status
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');

            // Timing information
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Download tracking
            $table->timestamp('downloaded_at')->nullable();
            $table->integer('download_count')->default(0);

            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes for quick lookup
            $table->index('user_id');
            $table->index('export_type');
            $table->index('status');
            $table->index('created_at');
            $table->index('downloaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
