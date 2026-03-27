<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeBoundary extends Model
{
    protected $fillable = ['min_score','max_score','grade','remark','priority'];

    // simple helper
    public static function findByScore(int $score)
    {
        return static::where('min_score','<=',$score)
            ->where('max_score','>=',$score)
            ->orderByDesc('priority')
            ->first();
    }
}
