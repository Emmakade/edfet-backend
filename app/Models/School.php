<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = [
        'name','address','mailbox','phone','motto','next_term_begins','extra'
    ];

    protected $casts = [
        'next_term_begins' => 'date',
        'extra' => 'array'
    ];

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }
}
