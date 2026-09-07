<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submenu extends Model
{
    protected $guarded = [];
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function indicators(): HasMany { return $this->hasMany(Indicator::class); }
}
