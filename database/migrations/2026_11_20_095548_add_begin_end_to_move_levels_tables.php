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
        Schema::table('move_level_1', function (Blueprint $table) {
            $table->integer('begin')->nullable()->after('name');
            $table->integer('end')->nullable()->after('begin');
        });

        Schema::table('move_level_2', function (Blueprint $table) {
            $table->integer('begin')->nullable()->after('name');
            $table->integer('end')->nullable()->after('begin');
        });

        Schema::table('move_level_3', function (Blueprint $table) {
            $table->integer('begin')->nullable()->after('name');
            $table->integer('end')->nullable()->after('begin');
        });

        Schema::table('non_verbal_actions', function (Blueprint $table) {
            $table->integer('begin')->nullable()->after('name');
            $table->integer('end')->nullable()->after('begin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('move_level_1', function (Blueprint $table) {
            $table->dropColumn(['begin', 'end']);
        });

        Schema::table('move_level_2', function (Blueprint $table) {
            $table->dropColumn(['begin', 'end']);
        });

        Schema::table('move_level_3', function (Blueprint $table) {
            $table->dropColumn(['begin', 'end']);
        });

        Schema::table('non_verbal_actions', function (Blueprint $table) {
            $table->dropColumn(['begin', 'end']);
        });
    }
};
