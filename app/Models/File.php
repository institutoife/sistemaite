<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class File extends Model
{
    use HasFactory;
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
