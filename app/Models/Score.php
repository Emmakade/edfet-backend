<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = [
        'enrollment_id',
        'subject_id',
        'term_id',
        'assessment_id',
        'score',
        'ca_score',
        'exam_score',
        'total',
        'grade',
        'remark',
        'subject_position',
        'class_average',
        'class_highest',
        'class_lowest'
    ];

    protected $casts = [
        'ca_score' => 'decimal:2',
        'exam_score' => 'decimal:2',
        'total' => 'decimal:2',
        'subject_position' => 'integer'
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    // 🔥 Access student THROUGH enrollment
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            Enrollment::class,
            'id',
            'id',
            'enrollment_id',
            'student_id'
        );
    }
}
