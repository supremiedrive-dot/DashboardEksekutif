<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportRow extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['errors'=>'array']; }
    public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class, 'import_batch_id'); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function values(): HasMany { return $this->hasMany(ImportValue::class); }
}
