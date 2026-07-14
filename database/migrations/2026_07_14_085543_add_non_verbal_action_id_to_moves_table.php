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
        Schema::table('moves', function (Blueprint $table) {
            $table->foreignId('non_verbal_action_id')->nullable()->after('move_level_3_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moves', function (Blueprint $table) {
            $table->dropConstrainedForeignId('non_verbal_action_id');
        });
    }
};
