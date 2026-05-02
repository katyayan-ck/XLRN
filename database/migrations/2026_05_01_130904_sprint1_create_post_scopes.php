<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Org Scopes
        Schema::create('xlr8_iam_post_org_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('post_code',   30);
            $table->string('scope_type',  20)
                  ->comment('branch | location | department | division | vertical');
            $table->string('scope_value', 20)->nullable()
                  ->comment('NULL = wildcard (all values for this type)');

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['post_code','scope_type'],           'idx_pos_post_type');
            $table->index(['scope_type','scope_value'],         'idx_pos_type_value');
            $table->index('created_by',                         'idx_pos_created_by');
            $table->unique(['post_code','scope_type','scope_value'], 'uniq_post_org_scope');
        });

        // Vehicle Scopes
        Schema::create('xlr8_iam_post_vehicle_scopes', function (Blueprint $table) {
            $table->id();
            $table->string('post_code',   30);
            $table->string('scope_type',  20)
                  ->comment('brand | segment | subsegment | vehicle_model | variant | color');
            $table->string('scope_value', 30)->nullable()
                  ->comment('NULL = wildcard');

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->index(['post_code','scope_type'],           'idx_pvs_post_type');
            $table->index(['scope_type','scope_value'],         'idx_pvs_type_value');
            $table->index('created_by',                         'idx_pvs_created_by');
            $table->unique(['post_code','scope_type','scope_value'], 'uniq_post_veh_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_iam_post_vehicle_scopes');
        Schema::dropIfExists('xlr8_iam_post_org_scopes');
    }
};
