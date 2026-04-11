<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_hierarchies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->integer('level'); // 1-5
            $table->string('topic'); // e.g., 'quotation'
            $table->json('combo_json')->nullable(); // Filters
            $table->json('powers_json')->nullable(); // e.g., {'discount_max': 5000}
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['topic', 'level']);
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_hierarchies');
    }
};
