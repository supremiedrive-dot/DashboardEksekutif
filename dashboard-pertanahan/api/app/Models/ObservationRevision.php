<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservationRevision extends Model
{
    protected $guarded = [];
    protected function casts(): array
    {
        return ['source_as_of'=>'date', 'value_decimal'=>'decimal:6', 'value_min'=>'decimal:6',
            'value_max'=>'decimal:6'];
    }
    public function observation(): BelongsTo { return $this->belongsTo(Observation::class); }
    public function definition(): BelongsTo { return $this->belongsTo(IndicatorDefinition::class, 'indicator_definition_id'); }
    public function source(): BelongsTo { return $this->belongsTo(DataSource::class, 'data_source_id'); }
    public function importValue(): BelongsTo { return $this->belongsTo(ImportValue::class); }

    protected static function booted(): void
    {
        static::updating(function (self $revision) {
            $forbidden = array_diff(array_keys($revision->getDirty()), ['status', 'updated_at']);
            if ($forbidden) throw new \LogicException('Observation revision values are immutable.');
        });
        static::deleting(fn () => throw new \LogicException('Observation revisions cannot be deleted.'));
    }
}
