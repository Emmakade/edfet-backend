<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRemarksTable extends Migration
{
    public function up(): void
    {
        Schema::create('remarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->text('class_teacher_remark')->nullable();
            $table->string('class_teacher_signature')->nullable(); // path to signature image
            $table->text('head_teacher_remark')->nullable();
            $table->string('head_teacher_signature')->nullable();
            $table->timestamps();

            $table->unique(['student_id','term_id','session_id'], 'unique_remark_per_student_term_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
}
