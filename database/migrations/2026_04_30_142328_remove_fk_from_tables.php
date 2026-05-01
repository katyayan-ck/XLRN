<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Fetch all foreign keys in the current schema
        $foreignKeys = DB::select("
            SELECT TABLE_NAME, CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL;
        ");

        Schema::disableForeignKeyConstraints();

        foreach ($foreignKeys as $fk) {
            Schema::table($fk->TABLE_NAME, function ($table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        Schema::enableForeignKeyConstraints();
    }
};