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
        'class_teacher_remark',
        'head_teacher_remark',
        'class_teacher_signature',
        'head_teacher_signature',
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

    public function getTeacherRemarkAttribute(): ?string
    {
        return $this->attributes['class_teacher_remark'] ?? null;
    }

    public function setTeacherRemarkAttribute(?string $value): void
    {
        $this->attributes['class_teacher_remark'] = $value;
    }

    public function getPrincipalRemarkAttribute(): ?string
    {
        return $this->attributes['head_teacher_remark'] ?? null;
    }

    public function setPrincipalRemarkAttribute(?string $value): void
    {
        $this->attributes['head_teacher_remark'] = $value;
    }
}
