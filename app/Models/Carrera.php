<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Carrera extends Model
{
    use HasFactory;


    protected $fillable = [
        'carrera'
    ];

    public function computacion()
    {
        return $this->belongsToMany(Computacion::class)->withPivot('computacion_id', 'carrera_id ');
    }

    public function asignaturas()
    {
        return $this->hasMany('App\Models\Asignatura');
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
}
