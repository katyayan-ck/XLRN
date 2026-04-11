<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('graph_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->json('attributes'); // e.g., {"branches": [1,2], "segments": [3]}
            $table->timestamps();
        });

        Schema::create('graph_edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_node_id')->constrained('graph_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('graph_nodes')->cascadeOnDelete();
            $table->string('type'); // e.g., 'reports_to', 'approves'
            $table->integer('level')->nullable();
            $table->json('powers')->nullable(); // e.g., {"discount_max": 5000}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('graph_edges');
        Schema::dropIfExists('graph_nodes');
    }
};
