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
        Schema::create('dialogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corpus_id')->constrained('corpora')->onDelete('cascade');
            $table->string('reference');
            $table->string('date')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('genre')->nullable();
            $table->string('subgenre')->nullable();
            $table->string('topic')->nullable();
            $table->string('subject_languages')->nullable();
            $table->string('working_languages')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('continent')->nullable();
            $table->string('researcher_involvement')->nullable();
            $table->string('planning_type')->nullable();
            $table->string('social_context')->nullable();
            $table->string('customer_type')->nullable();
            $table->string('customer_profile')->nullable();
            $table->integer('customer_n');
            $table->integer('speaking_customer_n');
            $table->string('speakers_features')->nullable();
            $table->string('restaurant_title')->nullable();
            $table->string('restaurant_features')->nullable();
            $table->string('menu_type')->nullable();
            $table->string('meal')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dialogs');
    }
};
