<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_utils_comm_master', function (Blueprint $table) {
            $table->id();
            $table->string('entityable_type');
            $table->unsignedBigInteger('entityable_id');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->unsignedBigInteger('action_id')->nullable();
            $table->json('extra_data')->nullable();

            // === 6 AUDIT COLUMNS (BaseModel enforced) ===
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['entityable_type', 'entityable_id'], 'comm_master_entity_idx');
            $table->index('status_id');
            $table->index('action_id');
        });

        Schema::create('xlr8_utils_comm_thread', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('comm_master_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('actor_id');           // Post ID (Post-as-Role)
            $table->unsignedBigInteger('action_id');
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->json('extra_data')->nullable();

            // Kalnoy NestedSet
            $table->unsignedBigInteger('_lft')->default(0);
            $table->unsignedBigInteger('_rgt')->default(0);
            $table->unsignedInteger('depth')->default(0);

            // === 6 AUDIT COLUMNS ===
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('comm_master_id')
                  ->references('id')->on('xlr8_utils_comm_master')
                  ->onDelete('cascade');

            $table->index(['comm_master_id', '_lft', '_rgt'], 'comm_thread_nested_idx');
            $table->index('action_id');
            $table->index('actor_id');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_utils_comm_thread');
        Schema::dropIfExists('xlr8_utils_comm_master');
    }
};