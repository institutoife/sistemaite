<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Hometext extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
