<?php

namespace Webkul\Pos\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Webkul\Pos\Contracts\User as UserContract;

class User extends Authenticatable implements JWTSubject, UserContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pos_users';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are hidden.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The attributes that are appended.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * Native casts.
     *
     * @var array
     */
    protected $casts = [
        'training_mode'           => 'boolean',
        'training_print_receipt'  => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Hash::make($value),
        );
    }

    /**
     * Get image url for the user image.
     */
    public function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? Storage::url($this->image) : null,
        );
    }

    /**
     * Get the outlet that belongs to the user.
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(OutletProxy::modelClass());
    }

    /**
     * Get the drawer associated with the PosUser
     */
    public function banks()
    {
        return $this->hasMany(BankProxy::modelClass(), 'agent_id');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array['image_url'] = $this->image_url;

        return $array;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
