<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_data_scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('scope_type', [
                'branch',
                'location',
                'department',
                'division',
                'vertical',
                'brand',
                'segment',
                'sub_segment',
                'vehicle_model',
                'variant',
                'color'
            ]);
            $table->unsignedBigInteger('scope_value')->nullable();
            $table->unsignedSmallInteger('hierarchy_level')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'scope_type', 'status']);
            $table->unique(['user_id', 'scope_type', 'scope_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_data_scopes');
    }
};
