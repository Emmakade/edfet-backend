<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Remark extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'term_id',
        'teacher_remark',
        'principal_remark',
    ];

    protected $casts = [
        'enrollment_id' => 'integer',
        'term_id' => 'integer',
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