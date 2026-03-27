<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradeBoundariesTable extends Migration
{
    public function up(): void
    {
        Schema::create('grade_boundaries', function (Blueprint $table) {
            $table->id();
            $table->integer('min_score')->default(0);
            $table->integer('max_score')->default(100);
            $table->string('grade');
            $table->string('remark')->nullable();
            $table->integer('priority')->default(0); // helpful when querying
            $table->timestamps();

            $table->unique(['min_score','max_score','grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_boundaries');
    }
}
