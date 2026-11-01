<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Clipractico extends Model
{
    use HasFactory;
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
