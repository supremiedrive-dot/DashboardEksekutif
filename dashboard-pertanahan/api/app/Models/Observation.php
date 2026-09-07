<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Observation extends Model
{
    protected $guarded = [];
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function snapshot(): BelongsTo { return $this->belongsTo(ReportingSnapshot::class, 'reporting_snapshot_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(Indicator::class); }
    public function source(): BelongsTo { return $this->belongsTo(DataSource::class, 'data_source_id'); }
    public function revisions(): HasMany { return $this->hasMany(ObservationRevision::class); }
    public function publishedValue(): HasOne { return $this->hasOne(PublishedValue::class); }
    public function latestRevision(): HasOne { return $this->hasOne(ObservationRevision::class)->latestOfMany('revision_number'); }
}
