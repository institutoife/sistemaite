<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleContactBulkRun extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['max_ids' => 'array', 'cursors' => 'array', 'include_synced' => 'boolean', 'finished_at' => 'datetime'];
}
