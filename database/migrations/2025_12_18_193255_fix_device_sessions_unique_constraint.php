<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix device_sessions table unique constraint
     * Change from GLOBAL unique (device_id) to COMPOSITE unique (user_id, device_id)
     * This allows multiple users to have different devices, and one user to have multiple devices
     */
    public function up(): void
    {
        Schema::table('device_sessions', function (Blueprint $table) {
            // Drop the old GLOBAL unique constraint on device_id
            // This constraint is preventing users from registering multiple devices
            $table->dropUnique('device_sessions_device_id_unique');
        });

        Schema::table('device_sessions', function (Blueprint $table) {
            // Add composite unique constraint: user can have multiple devices, 
            // but each device_id must be unique per user
            $table->unique(['user_id', 'device_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_sessions', function (Blueprint $table) {
            // Revert to old constraint if migration is rolled back
            $table->dropUnique('device_sessions_user_id_device_id_unique');
            $table->unique('device_id');
        });
    }
};
