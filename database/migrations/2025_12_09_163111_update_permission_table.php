<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->foreignId('module_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('process_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropForeign(['process_id']);
            $table->dropColumn(['module_id', 'process_id']);
        });
    }
};
