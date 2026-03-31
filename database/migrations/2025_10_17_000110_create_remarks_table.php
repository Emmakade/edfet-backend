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

            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();

            $table->text('class_teacher_remark')->nullable();
            $table->string('class_teacher_signature')->nullable();

            $table->text('head_teacher_remark')->nullable();
            $table->string('head_teacher_signature')->nullable();

            $table->timestamps();

            $table->unique(['enrollment_id','term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarks');
    }
}
