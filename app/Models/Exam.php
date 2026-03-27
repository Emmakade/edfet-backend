<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = ['name','type','term_id','session_id','max_score'];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function session()
    {
        return $this->belongsTo(SessionModel::class);
    }
}
