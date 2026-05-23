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
        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dialog_id')->constrained()->onDelete('cascade');
            $table->string('full_name')->nullable();
            $table->string('nickname')->nullable();
            $table->string('code');
            $table->integer('birth_year')->nullable();
            $table->string('gender')->nullable();
            $table->string('languages')->nullable();
            $table->text('description')->nullable();
            $table->string('contact')->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('age_range')->nullable();
            $table->string('speaker_language')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
