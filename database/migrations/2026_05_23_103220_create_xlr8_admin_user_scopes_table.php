<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_admin_user_scopes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('FK → users.id');

            $table->string('scope_type', 50)
                  ->comment('branch, location, department, division, vertical, segment, sub_segment, model, variant');

            $table->string('scope_code', 50);

            $table->boolean('is_active')->default(true);

            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();

            // Audit columns as per your BaseModel standard
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes & Constraints
            $table->unique(['user_id', 'scope_type', 'scope_code'], 'uq_user_scope');
            $table->index(['user_id', 'is_active'], 'idx_user_active_scopes');
            $table->index(['scope_type', 'scope_code', 'is_active'], 'idx_scope_lookup');
            $table->index(['user_id', 'scope_type'], 'idx_user_scope_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_admin_user_scopes');
    }
};