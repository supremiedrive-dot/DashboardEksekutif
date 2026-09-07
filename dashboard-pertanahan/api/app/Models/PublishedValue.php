<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublishedValue extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['published_at' => 'datetime']; }
}
