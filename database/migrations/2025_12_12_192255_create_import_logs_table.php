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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();

            // User who performed the import
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // File information
            $table->string('filename');
            $table->enum('import_type', [
                'standard_users',
                'rules_users',
                'vehicle_definition',
                'custom'
            ])->default('standard_users');

            // Import statistics
            $table->integer('total_records')->default(0);
            $table->integer('imported_count')->default(0);
            $table->integer('skipped_count')->default(0);
            $table->integer('errors_count')->default(0);

            // Error and warning details (JSON)
            $table->json('errors')->nullable();
            $table->json('warnings')->nullable();

            // Import status
            $table->enum('status', ['pending', 'processing', 'success', 'partial', 'failed'])->default('pending');

            // Timing information
            $table->integer('duration_seconds')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();

            // Indexes for quick lookup
            $table->index('user_id');
            $table->index('import_type');
            $table->index('status');
            $table->index('created_at');
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
