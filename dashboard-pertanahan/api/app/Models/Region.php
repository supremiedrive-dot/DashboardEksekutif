<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class, 'user_region_scopes')->withTimestamps(); }
}
