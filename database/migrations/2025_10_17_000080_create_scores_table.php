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

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete(); // ✅ FIXED
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();

            // Scores
            $table->decimal('ca_score', 5, 2)->nullable();
            $table->decimal('exam_score', 5, 2)->nullable();

            // Computed fields
            $table->decimal('total', 6, 2)->default(0)->index();
            $table->string('grade')->nullable();
            $table->string('remark')->nullable();

            // Position (rename properly)
            $table->integer('subject_position')->nullable(); // ✅ FIXED

            // Analytics (for report card)
            $table->decimal('class_average', 6, 2)->nullable();
            $table->decimal('class_highest', 6, 2)->nullable();
            $table->decimal('class_lowest', 6, 2)->nullable();

            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(
                ['student_id','subject_id','school_class_id','term_id','session_id'],
                'unique_student_subject_full'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
}
