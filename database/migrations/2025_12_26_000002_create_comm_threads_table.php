<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comm_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comm_master_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('comm_threads')->onDelete('cascade');
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('action_id')->nullable()->constrained('keyvalues')->onDelete('set null');
            $table->string('title')->nullable();
            $table->text('message_text')->nullable();
            $table->json('extra_data')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['_lft', '_rgt', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comm_threads');
    }
};
