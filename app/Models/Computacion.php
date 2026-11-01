<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Computacion extends Model
{
    use HasFactory;

    protected $fillable=['persona_id'];
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    // public function userable()
    // {
    //     return $this->morphOne('App\Models\Userable', 'userable');
    // }
    public function carreras()
    {
        return $this->belongsToMany(Carrera::class)->withPivot('computacion_id', 'carrera_id ');;
    }
    public function asignaturas()
    {
        return $this->belongsToMany('App\Models\Asignatura');
    }

    public function matriculaciones()
    {
        return $this->hasMany(Matriculacion::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }

}
