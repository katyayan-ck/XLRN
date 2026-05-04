<?php

// database/migrations/20260502190001_create_vehicle_accessories_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('xlr8_vehicle_accessories', function (Blueprint $table) {
            $table->id();
            $table->string('part_no', 25)->unique();
            $table->string('display_name', 150)->nullable();
            $table->string('item', 150);
            $table->decimal('ndp', 12, 2)->nullable();
            $table->decimal('mrp', 12, 2)->nullable();
            $table->string('details', 250)->nullable();
            $table->boolean('bundle')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['status', 'part_no']);
        });

        Schema::create('xlr8_vehicle_accessory_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('part_no', 25);
            $table->string('segment_code', 25)->nullable(); // null = ANY
            $table->string('model_code', 50)->nullable();   // null = ANY
            $table->string('variant_code', 100)->nullable();// null = ANY
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique(['part_no', 'segment_code', 'model_code', 'variant_code'], 'uq_acc_scope');
            $table->index(['segment_code', 'model_code', 'variant_code'], 'idx_acc_scope_match');
            $table->index(['part_no', 'status'], 'idx_acc_scope_part_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_vehicle_accessory_scopes');
        Schema::dropIfExists('xlr8_vehicle_accessories');
    }
};

