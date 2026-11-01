<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Asignatura extends Model
{
    use HasFactory;

    public function carrera()
    {
        return $this->belongsTo('App\Models\Carrera');
    }
    public function matriculacion()
    {
        return $this->hasOne(Matriculacion::class);
    }
    public function computaciones()
    {
        return $this->belongsToMany('App\Models\Computacion');
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
