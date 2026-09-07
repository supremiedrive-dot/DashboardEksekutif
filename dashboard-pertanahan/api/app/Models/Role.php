<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'user_roles')->withTimestamps(); }
}
