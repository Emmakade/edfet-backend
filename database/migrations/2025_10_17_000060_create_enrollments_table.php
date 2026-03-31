<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnrollmentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained()->cascadeOnDelete();

            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('status', [
                'active',
                'promoted',
                'repeated',
                'transferred',
                'graduated'
            ])->default('active');

            $table->date('enrolled_at')->nullable();
            $table->date('left_at')->nullable();

            $table->timestamps();

            // ✅ Allow multiple enrollments if needed (but prevent duplicates per class)
            $table->unique(['student_id', 'school_class_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
}
