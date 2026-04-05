<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // optional link to user (parent/student)
            $table->string('surname');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->enum('gender', ['male','female'])->nullable();
            $table->string('admission_number')->nullable()->unique();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete();
            $table->date('date_of_birth')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('photo_url')->nullable();
            $table->integer('number_in_class')->nullable(); // rank/roll no
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
}
