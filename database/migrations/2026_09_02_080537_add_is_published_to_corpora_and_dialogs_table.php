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
        Schema::table('corpora', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('description');
        });

        Schema::table('dialogs', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('eaf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corpora', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });

        Schema::table('dialogs', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
