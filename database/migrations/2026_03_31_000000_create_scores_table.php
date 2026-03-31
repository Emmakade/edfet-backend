<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoresTable extends Migration
{
    public function up(): void
    {
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->integer('score');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
}
