<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['as_of_date'=>'date', 'started_at'=>'datetime', 'completed_at'=>'datetime'];
    }

    public function source(): BelongsTo { return $this->belongsTo(DataSource::class, 'data_source_id'); }
    public function rows(): HasMany { return $this->hasMany(ImportRow::class); }
    public function qualityFlags(): HasMany { return $this->hasMany(ImportQualityFlag::class); }
}
