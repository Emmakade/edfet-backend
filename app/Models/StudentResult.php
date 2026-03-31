<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    protected $fillable = [
        'enrollment_id',
        'term_id',
        'total_score',
        'average_score',
        'overall_position'
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    // 🔥 Access student through enrollment
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
