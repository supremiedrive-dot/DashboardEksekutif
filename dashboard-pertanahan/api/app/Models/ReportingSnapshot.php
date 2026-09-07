<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportingSnapshot extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['as_of_date' => 'date']; }
}
