<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Estado extends Model
{
    use HasFactory;

    public function programaciones(){
        return $this->hasMany(Programacion::class);
    }
    public function programacioncoms(){
        return $this->hasMany(Programacioncom::class);
    }
    public function clases(){
        return $this->hasMany(Clase::class);
    }
    public function clasecoms(){
        return $this->hasMany(Clasecom::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function inscripciones()
    {
        return $this->hasMany(Inscripcione::class);
    }
    public function matriculaciones()
    {
        return $this->hasMany(Matriculacion::class);
    }
     public function docente()
    {
        return $this->hasOne(Docente::class);
    } 
}
