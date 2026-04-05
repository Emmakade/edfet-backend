<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_class_id',
        'session_id',
        'status',
    ];

    protected $casts = [
        'student_id' => 'integer',
        'school_class_id' => 'integer',
        'session_id' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function session()
    {
        return $this->belongsTo(SessionModel::class, 'session_id');
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    public function subjectResults()
    {
        return $this->hasMany(SubjectResult::class);
    }

    public function studentResult()
    {
        return $this->hasOne(StudentResult::class);
    }

    public function remarks()
    {
        return $this->hasMany(Remark::class);
    }
}