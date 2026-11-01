<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Programacioncom extends Model
{
    use HasFactory;
    protected $dates = ['fecha','horaini','horafin'];

        /* Get the docente associated with the Programacioncom
        *
        * @return \Illuminate\Database\Eloquent\Relations\HasOne
        */
    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
    /**
     * Get all of the matriculacion for the Programacioncom
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    /**
      * Get the user that owns the Programacioncom
      *
      * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
      */
    public function matriculacion()
    {
        return $this->belongsTo(Matriculacion::class);
    }
    public function aula()
    {
        return $this->belongsTo('App\Models\Aula');
    }
    public function clasescom() 
    {
        return $this->hasMany(Clasecom::class);
    }
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    
    
    public function licencias(){
        return $this->morphMany('App\Models\Licencia','licenciable');
    }
    public function estado(){
        return $this->belongsTo(Estado::class);
    }
    

}
