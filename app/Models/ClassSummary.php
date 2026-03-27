<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_class_id',
        'term_id',
        'session_id',
        'subject_id',
        'average',
        'highest',
        'lowest',
        'computed_at',
    ];

    protected $casts = [
        'average' => 'float',
        'highest' => 'float',
        'lowest' => 'float',
        'computed_at' => 'datetime',
    ];

    // Relationships
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
