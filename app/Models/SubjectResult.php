<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'subject_id',
        'term_id',
        'total',
        'grade',
        'remark',
        'subject_position',
        'class_average',
        'class_highest',
        'class_lowest',
    ];

    protected $casts = [
        'enrollment_id' => 'integer',
        'subject_id' => 'integer',
        'term_id' => 'integer',
        'total' => 'integer',
        'subject_position' => 'integer',
        'class_average' => 'float',
        'class_highest' => 'integer',
        'class_lowest' => 'integer',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}