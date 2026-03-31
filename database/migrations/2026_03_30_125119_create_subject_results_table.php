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
        Schema::create('subject_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();

            $table->integer('total')->default(0)->index();
            $table->string('grade')->nullable();
            $table->string('remark')->nullable();

            $table->integer('subject_position')->nullable();

            $table->decimal('class_average', 6, 2)->nullable();
            $table->unsignedInteger('class_highest')->nullable();
            $table->unsignedInteger('class_lowest')->nullable();

            $table->timestamps();

            $table->unique(
                ['enrollment_id','subject_id','term_id'],
                'unique_subject_result'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_results');
    }
};
