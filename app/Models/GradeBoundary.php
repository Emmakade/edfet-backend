<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_score',
        'max_score',
        'grade',
        'remark',
        'priority',
    ];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'priority' => 'integer',
    ];

    public static function findByScore(int $score): ?self
    {
        return static::query()
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderByDesc('priority')
            ->first();
    }
}