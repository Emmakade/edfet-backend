<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->unique(
                [
                    'enrollment_id',
                    'subject_id',
                    'assessment_id',
                    'term_id',
                    'session_id',
                    'school_class_id',
                ],
                'scores_unique_entry'
            );

            $table->index(
                ['school_class_id', 'session_id', 'term_id'],
                'scores_class_session_term_idx'
            );

            $table->index(
                ['enrollment_id', 'term_id', 'session_id'],
                'scores_enrollment_term_session_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropUnique('scores_unique_entry');
            $table->dropIndex('scores_class_session_term_idx');
            $table->dropIndex('scores_enrollment_term_session_idx');
        });
    }
};