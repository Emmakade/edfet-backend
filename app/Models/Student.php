<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'middle_name',
        'surname',
        'gender',
        'date_of_birth',
        'admission_number',
        'phone_number',
        'login_email',
        'photo_url',
        'number_in_class',
        'school_class_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'school_class_id' => 'integer',
        'number_in_class' => 'integer',
        'date_of_birth' => 'date',
    ];

    protected $appends = [
        'full_name',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->middle_name,
            $this->surname,
        ])));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    public function scores()
    {
        return $this->hasManyThrough(
            Score::class,
            Enrollment::class,
            'student_id',
            'enrollment_id',
            'id',
            'id'
        );
    }

    public function subjectResults()
    {
        return $this->hasManyThrough(
            SubjectResult::class,
            Enrollment::class,
            'student_id',
            'enrollment_id',
            'id',
            'id'
        );
    }

    public function studentResults()
    {
        return $this->hasManyThrough(
            StudentResult::class,
            Enrollment::class,
            'student_id',
            'enrollment_id',
            'id',
            'id'
        );
    }

    public function remarks()
    {
        return $this->hasManyThrough(
            Remark::class,
            Enrollment::class,
            'student_id',
            'enrollment_id',
            'id',
            'id'
        );
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}