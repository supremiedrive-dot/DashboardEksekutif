<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Indicator extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return ['is_derived' => 'boolean', 'allows_manual_input' => 'boolean',
            'is_feature' => 'boolean', 'is_active' => 'boolean'];
    }
    public function owner(): BelongsTo { return $this->belongsTo(DataOwner::class, 'data_owner_id'); }
    public function submenu(): BelongsTo { return $this->belongsTo(Submenu::class); }
    public function definitions(): HasMany { return $this->hasMany(IndicatorDefinition::class); }

    public function definitionEffectiveOn(string $date): ?IndicatorDefinition
    {
        return $this->definitions()->effectiveOn($date)->orderByDesc('version')->first();
    }
}
