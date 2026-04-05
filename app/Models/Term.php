<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'session_id',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionModel::class, 'session_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function subjectResults(): HasMany
    {
        return $this->hasMany(SubjectResult::class);
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResult::class);
    }

    public function remarks(): HasMany
    {
        return $this->hasMany(Remark::class);
    }
}