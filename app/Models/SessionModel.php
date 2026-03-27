<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionModel extends Model
{
    protected $table = 'sessions';

    protected $fillable = ['name','year_start','year_end','active'];

    protected $casts = [
        'year_start' => 'date',
        'year_end' => 'date',
        'active' => 'boolean'
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }
}
