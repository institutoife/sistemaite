<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Programacion
 *
 * @property $id
 * @property $fecha
 * @property $habilitado
 * @property $estado
 * @property $hora_ini
 * @property $hora_fin
 * @property $docente_id
 * @property $materia_id
 * @property $aula_id
 * @property $inscripcion_id
 * @property $created_at
 * @property $updated_at
 *
 * @property Aula $aula
 * @property Clase[] $clases
 * @property Docente $docente
 * @property Inscripcione $inscripcione
 * @property Materia $materia
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Programacion extends Model
{
    
    static $rules = [
		'fecha' => 'required',
		'habilitado' => 'required',
		'estado' => 'required',
		'hora_ini' => 'required',
		'hora_fin' => 'required',
		'docente_id' => 'required',
		'materia_id' => 'required',
		'aula_id' => 'required',
		'inscripcion_id' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['fecha','habilitado','estado','hora_ini','hora_fin','docente_id','materia_id','aula_id','inscripcion_id'];
    protected $dates = ['fecha','hora_ini','hora_fin'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function aula()
    {
        return $this->hasOne('App\Models\Aula', 'id', 'aula_id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clases()
    {
        return $this->hasMany(Clase::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function inscripcione()
    {
        return $this->belongsTo(Inscripcione::class);
    }

    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
    public function docente()
    {
        return $this->belongsTo(Docente::class);
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
