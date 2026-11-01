<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Tema extends Model
{
    use HasFactory;
    public function materia(){
        return $this->belongsTo(Materia::class);
    }
    public function clases()
    {
        return $this->hasMany(Clase::class);
    }

    
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
