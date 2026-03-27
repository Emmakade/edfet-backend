<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, HasRoles;

    protected $fillable = [
        'name','email','password','phone'
    ];

    protected $hidden = [
        'password','remember_token'
    ];

    // optional: link to student profile
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }
}
