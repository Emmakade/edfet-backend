<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remark extends Model
{
    protected $fillable = [
        'enrollment_id','term_id',
        'class_teacher_remark','class_teacher_signature',
        'head_teacher_remark','head_teacher_signature'
    ];
}
