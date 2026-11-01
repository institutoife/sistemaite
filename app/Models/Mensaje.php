<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Mensaje extends Model
{
    use HasFactory;
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    
    public function clases(){
        return $this->morphedByMany(Clase::class,'mensajeable');
    } 
    
    public function clasecoms(){
        return $this->morphedByMany(Clasecom::class,'mensajeable');
    } 
   
    public function computaciones(){
        return $this->morphedByMany(Computacion::class,'mensajeable');
    }
    
    public function docentes(){
        return $this->morphedByMany(Docente::class,'mensajeable');
    } 

    public function inscripciones(){
        return $this->morphedByMany(Inscripcione::class,'mensajeable');
    }  
    public function licencias(){
        return $this->morphedByMany(Licencia::class,'mensajeable');
    } 
    
    public function matriculaciones(){
        return $this->morphedByMany(Matriculacion::class,'mensajeable');
    } 
    
    public function pagos(){
        return $this->morphedByMany(Pago::class,'mensajeable');
    } 

    public function personas(){
        return $this->morphedByMany(Persona::class,'mensajeable');
    }

    public function potenciales(){
        return $this->morphedByMany(Potencial::class,'mensajeable');
    } 

}
