<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keyvalues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('keyword_master_id')->constrained()->cascadeOnDelete();
            $table->string('key')->nullable();
            $table->string('value');
            $table->text('details')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->integer('level')->default(0);
            $table->json('extra_data')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['keyword_master_id', 'key', 'parent_id'], 'keyvalue_unique');
            $table->index('keyword_master_id');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyvalues');
    }
};
