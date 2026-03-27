<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    protected $fillable = ['name','session_id','starts_at','ends_at'];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date'
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionModel::class, 'session_id');
    }
}
