<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = ['name','code'];

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'subject_class', 'subject_id', 'school_class_id');
    }
}
