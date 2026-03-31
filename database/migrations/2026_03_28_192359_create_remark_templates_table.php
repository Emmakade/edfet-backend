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
        Schema::create('remark_templates', function (Blueprint $table) {
            $table->id();

            // Who the remark is for
            $table->enum('type', ['teacher', 'head']);

            // Performance band
            $table->integer('min_avg'); // e.g. 70
            $table->integer('max_avg'); // e.g. 100

            // Optional position filter
            $table->integer('min_position')->nullable(); // e.g. 1
            $table->integer('max_position')->nullable(); // e.g. 3

            // The actual remark
            $table->text('remark');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remark_templates');
    }
};
