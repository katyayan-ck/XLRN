<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Brands Table
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 5)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Segments Table
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 5);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['brand_id', 'code']);
            $table->index('brand_id');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // SubSegments Table (optional)
        Schema::create('sub_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 5);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['segment_id', 'code']);
            $table->index('segment_id');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // VehicleModels Table
        Schema::create('vehicle_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_segment_id')->nullable()->constrained('sub_segments')->cascadeOnDelete();
            $table->string('name');
            $table->string('custom_name')->nullable();
            $table->string('oem_code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('brand_id');
            $table->index('segment_id');
            $table->index('sub_segment_id');
            $table->index('oem_code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Variants Table
        Schema::create('variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_segment_id')->nullable()->constrained('sub_segments')->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('custom_name')->nullable();
            $table->string('oem_code')->nullable()->unique();
            $table->text('description')->nullable();
            $table->foreignId('permit_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->foreignId('fuel_type_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->integer('seating_capacity')->nullable();
            $table->integer('wheels')->default(4);
            $table->integer('gvw')->nullable();
            $table->string('cc_capacity')->nullable();
            $table->foreignId('body_type_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->foreignId('body_make_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->boolean('is_csd')->default(false);
            $table->string('csd_index')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('keyvalues')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('brand_id');
            $table->index('segment_id');
            $table->index('sub_segment_id');
            $table->index('vehicle_model_id');
            $table->index('oem_code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Colors Table
        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sub_segment_id')->nullable()->constrained('sub_segments')->nullOnDelete();
            $table->foreignId('vehicle_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 5)->unique();
            $table->string('hex_code')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('brand_id');
            $table->index('segment_id');
            $table->index('sub_segment_id');
            $table->index('vehicle_model_id');
            $table->index('code');
            $table->index('is_active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });

        // Variant-Color Pivot
        Schema::create('variant_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variant_colors');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('vehicle_models');
        Schema::dropIfExists('sub_segments');
        Schema::dropIfExists('segments');
        Schema::dropIfExists('brands');
    }
};
