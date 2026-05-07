<?php

// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $connection = 'mysql';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    public function userChannelMap(): HasMany
    {
        return $this->hasMany(UserChannelMap::class);
    }
}
