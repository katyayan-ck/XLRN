<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otp_tokens', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Foreign keys
            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // OTP data
            $table->string('mobile', 10)->index(); // 10-digit mobile number
            $table->text('otp_hash'); // Hashed OTP for security
            $table->timestamp('expires_at')->index(); // Expiration time (human-readable ISO 8601)
            $table->timestamp('used_at')->nullable()->index(); // ✅ USED AT - Mark when OTP was consumed

            // Audit fields (automatically managed by BaseModel)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps(); // created_at, updated_at (human-readable ISO 8601)
            $table->softDeletes(); // deleted_at (human-readable ISO 8601)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_tokens');
    }
};
