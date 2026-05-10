<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('xlr8_utils_keyword_master', function (Blueprint $table) {
            if (!Schema::hasColumn('xlr8_utils_keyword_master', 'is_recursive')) {
                $table->boolean('is_recursive')->default(false)->after('description');
            }
        });
    }
};