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
            $table->foreignId('dialog_id')->nullable()->constrained()->onDelete('cascade')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('moves', function (Blueprint $table) {
            $table->dropForeign(['dialog_id']);
            $table->dropColumn('dialog_id');
        });
    }
};
