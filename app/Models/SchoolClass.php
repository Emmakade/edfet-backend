<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SchoolClass extends Model
{
    use HasFactory;

    protected $table = 'school_classes';

    protected $fillable = [
        'name',
        'level',
        'section',
        'school_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Legacy/direct student link.
     * Keep only for backward compatibility in old UI/code.
     * Authoritative class membership should come from enrollments().
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'school_class_id');
    }

    /**
     * Authoritative class membership for the refactored system.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'school_class_id');
    }

    /**
     * Authoritative subject assignment for the refactored system.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'school_class_id');
    }

    /**
     * Subjects through class_subjects.
     * This replaces the older subject_class pivot usage.
     */
    public function assignedSubjects(): HasManyThrough
    {
        return $this->hasManyThrough(
            Subject::class,
            ClassSubject::class,
            'school_class_id',
            'id',
            'id',
            'subject_id'
        );
    }
}