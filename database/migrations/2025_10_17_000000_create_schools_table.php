<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolsTable extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name2')->nullable();
            $table->string('address')->nullable();
            $table->string('mailbox')->nullable();
            $table->string('phone')->nullable();
            $table->string('motto')->nullable();
            $table->date('next_term_begins')->nullable();
            $table->json('extra')->nullable(); // store flexible settings
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
}
