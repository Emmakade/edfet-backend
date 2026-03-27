<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->integer('times_school_opened')->default(0);
            $table->integer('times_present')->default(0);
            $table->timestamps();

            $table->unique(['student_id','session_id','term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
}
