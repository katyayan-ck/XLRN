<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comm_masters', function (Blueprint $table) {
            $table->id();
            $table->morphs('entityable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('keyvalues')->onDelete('set null');
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comm_masters');
    }
};
