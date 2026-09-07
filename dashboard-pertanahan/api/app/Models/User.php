<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function regionScopes(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'user_region_scopes')->withTimestamps();
    }

    public function hasActiveRole(string $code): bool
    {
        return $this->roles()->where('code', $code)->where('roles.is_active', true)->exists();
    }

    public function hasRegionScope(Region $region): bool
    {
        return $this->regionScopes()->where(function ($query) use ($region) {
            $query->where('regions.id', $region->id);
            if ($region->parent_id) {
                $query->orWhere('regions.id', $region->parent_id);
            }
        })->where('regions.is_active', true)->exists();
    }
}
