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
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();

            $table->integer('total_score')->nullable();
            $table->decimal('average_score', 6, 2)->default(0);
            $table->integer('overall_position')->nullable();

            $table->timestamps();

            $table->unique(['enrollment_id', 'term_id'], 'unique_student_term_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
