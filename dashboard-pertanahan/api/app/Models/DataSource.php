<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSource extends Model
{
    protected $guarded = [];
    public function owner(): BelongsTo { return $this->belongsTo(DataOwner::class, 'data_owner_id'); }
}
