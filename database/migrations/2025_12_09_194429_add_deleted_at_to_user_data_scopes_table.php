<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_data_scopes', function (Blueprint $table) {
            // Soft delete timestamp
            $table->softDeletes()
                ->after('status');  // After status column

            // Audit columns
            $table->unsignedBigInteger('created_by')
                ->nullable()
                ->after('deleted_at')
                ->comment('User who created this record');

            $table->unsignedBigInteger('updated_by')
                ->nullable()
                ->after('created_by')
                ->comment('User who last updated this record');

            $table->unsignedBigInteger('deleted_by')
                ->nullable()
                ->after('updated_by')
                ->comment('User who deleted this record');

            // Foreign keys for audit columns (optional but recommended)
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_data_scopes', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['deleted_by']);

            // Drop columns
            $table->dropSoftDeletes();
            $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
        });
    }
};
