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
        Schema::create('corpora', function (Blueprint $table) {
            $table->id();
            $table->string('project_reference');
            $table->string('subject_language')->nullable();
            $table->string('working_language')->nullable();
            $table->string('location')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('continent')->nullable();
            $table->string('title');
            $table->string('depositor')->nullable();
            $table->string('contact')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corpora');
    }
};
