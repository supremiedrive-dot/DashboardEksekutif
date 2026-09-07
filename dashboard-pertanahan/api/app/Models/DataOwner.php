<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataOwner extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['allows_manual_input' => 'boolean', 'is_active' => 'boolean']; }
    public function indicators(): HasMany { return $this->hasMany(Indicator::class); }
    public function sources(): HasMany { return $this->hasMany(DataSource::class); }
}
