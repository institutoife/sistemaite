<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Estudiante extends Model
{
    use HasFactory;

    protected $fillable=['persona_id'];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function grados(){
        return $this->belongsToMany(Grado::class)->withPivot('anio', 'colegio_id')->withTimestamps();
    }
    
    public function inscripciones(){
        return $this->hasMany(Inscripcione::class);
    }

    /** OBSERVACIONES */
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    // public function userable()
    // {
    //     return $this->morphOne('App\Models\Userable', 'userable');
    // }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
