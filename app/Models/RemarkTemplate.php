<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemarkTemplate extends Model
{
    protected $fillable = [
        'type',
        'min_avg',
        'max_avg',
        'min_position',
        'max_position',
        'remark'
    ];
}
