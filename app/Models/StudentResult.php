<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'term_id',
        'total_score',
        'average_score',
        'overall_position',
    ];

    protected $casts = [
        'enrollment_id' => 'integer',
        'term_id' => 'integer',
        'total_score' => 'integer',
        'average_score' => 'float',
        'overall_position' => 'integer',
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

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}