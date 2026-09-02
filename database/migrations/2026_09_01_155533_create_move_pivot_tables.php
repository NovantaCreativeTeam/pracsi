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
        Schema::create('move_move_level_1', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_id')->constrained()->onDelete('cascade');
            $table->foreignId('move_level_1_id')->constrained('move_level_1')->onDelete('cascade');
        });

        Schema::create('move_move_level_2', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_id')->constrained()->onDelete('cascade');
            $table->foreignId('move_level_2_id')->constrained('move_level_2')->onDelete('cascade');
        });

        Schema::create('move_move_level_3', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_id')->constrained()->onDelete('cascade');
            $table->foreignId('move_level_3_id')->constrained('move_level_3')->onDelete('cascade');
        });

        Schema::create('move_non_verbal_action', function (Blueprint $table) {
            $table->id();
            $table->foreignId('move_id')->constrained()->onDelete('cascade');
            $table->foreignId('non_verbal_action_id')->constrained()->onDelete('cascade');
        });

        Schema::table('moves', function (Blueprint $table) {
            $table->dropForeign(['move_level_1_id']);
            $table->dropForeign(['move_level_2_id']);
            $table->dropForeign(['move_level_3_id']);
            $table->dropForeign(['non_verbal_action_id']);

            $table->dropColumn(['move_level_1_id', 'move_level_2_id', 'move_level_3_id', 'non_verbal_action_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moves', function (Blueprint $table) {
            $table->foreignId('move_level_1_id')->nullable()->constrained('move_level_1')->onDelete('set null');
            $table->foreignId('move_level_2_id')->nullable()->constrained('move_level_2')->onDelete('set null');
            $table->foreignId('move_level_3_id')->nullable()->constrained('move_level_3')->onDelete('set null');
            $table->foreignId('non_verbal_action_id')->nullable()->constrained('non_verbal_actions')->onDelete('set null');
        });

        Schema::dropIfExists('move_move_level_1');
        Schema::dropIfExists('move_move_level_2');
        Schema::dropIfExists('move_move_level_3');
        Schema::dropIfExists('move_non_verbal_action');
    }
};
