<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'student_id','session_id','term_id','times_school_opened','times_present'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(SessionModel::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
