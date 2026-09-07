<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportValue extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['normalized_value'=>'array']; }
    public function row(): BelongsTo { return $this->belongsTo(ImportRow::class, 'import_row_id'); }
    public function indicator(): BelongsTo { return $this->belongsTo(Indicator::class); }
}
