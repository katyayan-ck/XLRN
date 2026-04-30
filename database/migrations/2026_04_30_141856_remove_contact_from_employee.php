<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            $table->dropColumn(['official_mobile', 'official_email']);
        });
    }

    public function down(): void {
        Schema::table('xlr8_admin_employee', function (Blueprint $table) {
            $table->string('official_mobile', 15)->nullable();
            $table->string('official_email', 100)->nullable();
        });
    }
};