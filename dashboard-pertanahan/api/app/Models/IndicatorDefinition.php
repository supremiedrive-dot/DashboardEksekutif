<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndicatorDefinition extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date', 'valid_to' => 'date', 'validation_rules' => 'array',
            'formula_metadata' => 'array', 'is_active' => 'boolean',
        ];
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function scopeEffectiveOn($query, CarbonInterface|string $date)
    {
        return $query->where('is_active', true)
            ->whereDate('valid_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $date));
    }

    protected static function booted(): void
    {
        static::saving(function (self $definition) {
            if (! $definition->is_active) return;
            $query = self::where('indicator_id', $definition->indicator_id)->where('is_active', true)
                ->when($definition->exists, fn ($q) => $q->whereKeyNot($definition->getKey()))
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $definition->valid_from));
            if ($definition->valid_to) $query->whereDate('valid_from', '<=', $definition->valid_to);
            if ($query->exists()) throw \Illuminate\Validation\ValidationException::withMessages([
                'valid_from'=>['Masa berlaku definition bertumpang tindih.'],
            ]);
        });
    }
}
