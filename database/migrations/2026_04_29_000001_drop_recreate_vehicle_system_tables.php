<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Drop order: most dependent first */
    private array $dropOrder = [
        'xlr8_vehicle_color',
        'xlr8_vehicle_variant',
        'xlr8_vehicle_model',
        'xlr8_vehicle_subsegment',
        'xlr8_vehicle_segment',
        'xlr8_vehicle_brand',
    ];

    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->dropOrder as $table) {
            Schema::dropIfExists($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Brand ────────────────────────────────────────────────
        Schema::create('xlr8_vehicle_brand', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index('is_active');
        });

        // ── Segment ──────────────────────────────────────────────
        Schema::create('xlr8_vehicle_segment', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code', 5)->index();
            $table->string('code', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['brand_code', 'code']);
            $table->index('is_active');
        });

        // ── SubSegment ───────────────────────────────────────────
        Schema::create('xlr8_vehicle_subsegment', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code', 5)->index();
            $table->string('segment_code', 5)->index();
            $table->string('code', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['segment_code', 'code']);
            $table->index('is_active');
        });

        // ── Vehicle Model ────────────────────────────────────────
        Schema::create('xlr8_vehicle_model', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code', 5)->index();
            $table->string('segment_code', 5)->index();
            $table->string('sub_segment_code', 5)->nullable()->index();
            $table->string('code', 10)->unique();      // e.g. BE6, XUV700
            $table->string('name');                     // OEM Model name
            $table->string('custom_name')->nullable();  // Custom/display name
            $table->string('oem_code')->nullable()->index(); // OEM internal model code if any
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_code', 'segment_code']);
            $table->index('is_active');
        });

        // ── Variant ──────────────────────────────────────────────
        Schema::create('xlr8_vehicle_variant', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code', 5)->index();
            $table->string('segment_code', 5)->index();
            $table->string('sub_segment_code', 5)->nullable()->index();
            $table->string('model_code', 10)->index();   // FK → xlr8_vehicle_model.code
            $table->string('code', 20)->unique();         // = model_code[:-2] from vehicle_info (16 chars)
            $table->string('name');                       // OEM Variant name
            $table->string('custom_name')->nullable();    // Custom Variant name
            $table->string('oem_code', 25)->nullable()->unique(); // full vehicle_info.model_code stored here
            $table->text('description')->nullable();
            // Technical specs
            $table->unsignedBigInteger('permit_id')->nullable()->index();
            $table->unsignedBigInteger('fuel_type_id')->nullable()->index();
            $table->unsignedInteger('seating_capacity')->nullable();
            $table->unsignedTinyInteger('wheels')->default(4);
            $table->unsignedInteger('gvw')->nullable();
            $table->string('cc_capacity')->nullable();
            $table->string('transmission')->nullable();   // AUTOMATIC, MANUAL
            $table->string('drivetrain')->nullable();     // RWD, AWD, FWD
            $table->unsignedBigInteger('body_type_id')->nullable()->index();
            $table->unsignedBigInteger('body_make_id')->nullable()->index();
            $table->boolean('is_csd')->default(false);
            $table->string('csd_index')->nullable();
            $table->unsignedBigInteger('status_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['model_code', 'brand_code']);
            $table->index(['brand_code', 'segment_code']);
            $table->index('is_active');
        });

        // ── Color ────────────────────────────────────────────────
        Schema::create('xlr8_vehicle_color', function (Blueprint $table) {
            $table->id();
            $table->string('brand_code', 5)->index();
            $table->string('segment_code', 5)->nullable()->index();
            $table->string('sub_segment_code', 5)->nullable()->index();
            $table->string('model_code', 10)->index();    // FK → xlr8_vehicle_model.code
            $table->string('code', 5);                    // last 2 of vehicle_info.model_code
            $table->string('name');
            $table->string('hex_code')->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->unsignedBigInteger('deleted_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['model_code', 'code']);       // one colour code per model
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->dropOrder as $table) {
            Schema::dropIfExists($table);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
