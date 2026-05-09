<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_utils_keyvalue', function (Blueprint $table) {
            if (!Schema::hasColumn('xlr8_utils_keyvalue', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('keyword_code');
            }
            if (!Schema::hasColumn('xlr8_utils_keyvalue', 'level')) {
                $table->integer('level')->default(0)->after('parent_id');
            }
            if (!Schema::hasColumn('xlr8_utils_keyvalue', 'path')) {
                $table->text('path')->nullable()->after('level');
            }
        });
    }

    public function down(): void
    {
        Schema::table('xlr8_utils_keyvalue', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'level', 'path']);
        });
    }
};