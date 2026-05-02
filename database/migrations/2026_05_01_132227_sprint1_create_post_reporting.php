<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_iam_post_reporting', function (Blueprint $table) {
            $table->id();
            $table->string('from_post_code', 30)
                  ->comment('Post that reports (Eloquent-only link)');
            $table->string('to_post_code',   30)
                  ->comment('Post being reported to (Eloquent-only link)');
            $table->string('topic', 50)
                  ->comment('sales | service | insurance | leaves | approvals | spare');
            $table->string('param_type',  30)->nullable()
                  ->comment('NULL=applies to all; else: segment | vehicle_model | branch...');
            $table->string('param_value', 30)->nullable()
                  ->comment('NULL=wildcard for param_type; else specific code e.g. LMM, THAR');
            $table->date('from_date');
            $table->date('to_date')->nullable()
                  ->comment('NULL = currently active');
            $table->tinyInteger('priority')->default(1)
                  ->comment('Higher = more specific, wins over lower priority matches');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            // "Who does post A report to for topic X on date D?"
            $table->index(['from_post_code','topic','to_date'],  'idx_pr_from_topic');
            // "Who reports to post B for topic X on date D?"
            $table->index(['to_post_code','topic','to_date'],    'idx_pr_to_topic');
            $table->index(['from_date','to_date'],               'idx_pr_date_range');
            $table->index(['param_type','param_value'],          'idx_pr_param');
            $table->index('priority',                            'idx_pr_priority');
            $table->index('created_by',                          'idx_pr_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlr8_iam_post_reporting');
    }
};