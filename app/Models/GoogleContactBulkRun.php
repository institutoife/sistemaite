<?php

namespace App\Models;

use App\Models\BranchModel as Model;

class GoogleContactBulkRun extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['max_ids' => 'array', 'cursors' => 'array', 'include_synced' => 'boolean', 'finished_at' => 'datetime'];
}
