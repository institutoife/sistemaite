<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;
use App\Models\DocenteTurno;


class Docente extends Model
{
    use HasFactory;


    protected $fillable = [
        'fecha_inicio',
        'dias_prueba',
        'nombre',
        'sueldo',
        'estado',
        'estado_id',
        'persona_id',
    ];
    protected $dates = [
        'fecha_inicio',
    ];


    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }

    /** RELACION MUCHOS A MUCHO POLIMORFICO */
    public function materias(){
        return $this->morphToMany(Materia::class,'materiable');
    }
    /** RELACION MUCHOS A MUCHO POLIMORFICO */
    // public function docentes(){
    //     return $this->morphToMany(Docente::class,'nivelable');
    // }
    /** RELACION MUCHOS A MUCHO POLIMORFICO */
    public function niveles()
    {
        return $this->morphToMany("App\Models\Nivel", 'nivelable');
    }

    public function sesion() 
    {
        return $this->hasOne(Sesion::class);
    } 
    public function clases()
    {
        return $this->hasMany(Clase::class); 
    }
    public function clasescom()
    {
        return $this->hasMany(Clasecom::class); 
    }

    public function programacion()
    {
        return $this->belongsTo(Programacion::class); 
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    
    public function sesioncom()
    {
        return $this->belongsTo(Sesion::class);
    }

    public function programacioncom()
    {
        return $this->hasOne(Programacioncom::class);
    }
    public function mododocente()
    {
        return $this->belongsTo(Mododocente::class);
    } 
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    } 
    public function periodos()
    {
        return $this->morphMany(Periodable::class,'periodable');
    }

    public function turnos()
    {
        return $this->hasMany(DocenteTurno::class);
    }
   
}
