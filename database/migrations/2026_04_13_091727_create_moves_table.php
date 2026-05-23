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
        Schema::create('moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('micro_task_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('sequence_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('participant_id')->constrained()->onDelete('cascade');
            $table->foreignId('move_level_1_id')->nullable()->constrained('move_level_1')->onDelete('set null');
            $table->foreignId('move_level_2_id')->nullable()->constrained('move_level_2')->onDelete('set null');
            $table->foreignId('move_level_3_id')->nullable()->constrained('move_level_3')->onDelete('set null');
            $table->integer('begin');
            $table->integer('end');
            $table->text('annotation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moves');
    }
};
