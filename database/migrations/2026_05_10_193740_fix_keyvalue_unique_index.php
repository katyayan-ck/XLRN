<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_utils_keyvalue', function (Blueprint $table) {
            // Drop old broken constraint
            if (Schema::hasIndex('xlr8_utils_keyvalue', 'keyvalue_unique')) {
                $table->dropUnique('keyvalue_unique');
            }
            // Add correct composite unique
            $table->unique(['keyword_code', 'code'], 'keyvalue_keyword_code_code_unique');
        });
    }
};