<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'max_score',
        'weight',
    ];

    protected $casts = [
        'max_score' => 'integer',
        'weight' => 'float',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }
}