<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionModel extends Model
{
    use HasFactory;

    protected $table = 'sessions';

    protected $fillable = [
        'name',
        'year_start',
        'year_end',
        'active',
    ];

    protected $casts = [
        'year_start' => 'date',
        'year_end' => 'date',
        'active' => 'boolean',
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class, 'session_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'session_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class, 'session_id');
    }
}