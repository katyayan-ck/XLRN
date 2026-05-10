<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlr8_admin_emp_division_pivot', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 20)->index();
            $table->string('div_code', 10)->index();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();

            $table->unique(['employee_code', 'div_code', 'to_date'], 'emp_div_pivot_unique');
        });
    }
};