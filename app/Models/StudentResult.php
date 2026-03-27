<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentResult extends Model
{
    protected $fillable = [
        'student_id',
        'school_class_id',
        'term_id',
        'session_id',
        'total_score',
        'average_score',
        'overall_position'
    ];
}
