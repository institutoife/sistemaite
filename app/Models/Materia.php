<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Materia extends Model
{
    use HasFactory;
    
    public function temas() 
    {
        return $this->hasMany(Tema::class);
    }

    /* realcion de muchos a muchos inversa polimorfica*/
    public function niveles(){
        return $this->morphedByMany(Nivel::class,'materiable');
    }

    /* realcion de muchos a muchos inversa polimorfica*/
    public function docentes()
    {
        return $this->morphedByMany(Docente::class, 'materiable');
    }
    public function sesion()
    {
        return $this->hasOne(Sesion::class);
    }
    public function programacion()
    {
        return $this->hasOne(Programacion::class);
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
