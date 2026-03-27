<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Score extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'school_class_id',
        'term_id',
        'session_id',
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
        'position' => 'integer'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function session()
    {
        return $this->belongsTo(SessionModel::class);
    }
}
