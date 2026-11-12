<?php

namespace App\Models;

use App\Models\Sesion;

use App\Models\BranchModel as Model;

/**
 * Class Inscripcione
 *
 * @property $id
 * @property $horainicio
 * @property $horafin
 * @property $fechaini
 * @property $fechafin
 * @property $totalhoras
 * @property $horasxclase
 * @property $vigente
 * @property $condonado
 * @property $Objetivo
 * @property $lunes
 * @property $martes
 * @property $miercoles
 * @property $jueves
 * @property $viernes
 * @property $sabado
 * @property $estudiante_id
 * @property $modalidad_id
 * @property $created_at
 * @property $updated_at
 *
 * @property AulaInscripcione[] $aulaInscripciones
 * @property DocenteInscripcione[] $docenteInscripciones
 * @property Estudiante $estudiante
 * @property Modalidad $modalidad
 * @property Programacion[] $programacions
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Inscripcione extends Model
{
    use \App\Models\Concerns\HasEnrollmentOrigin;
    
    static $rules = [
		'fechaini' => 'required',
		'totalhoras' => 'required',
        'costo' => 'required',
		'objetivo' => 'required|min:10',
		'estudiante_id' => 'required',
		'modalidad_id' => 'required',
		'objetivo' => 'required|min:10|max:255',
        'motivo_id' => 'required',
    ];
    protected $perPage = 20;
    protected $dates = [
        'horainicio','fecha_proximo_pago','horafin','fechaini','fechafin', 'created_at', 'updated_at',
    ];
    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
                    'horainicio',
                    'horafin',
                    'fechaini',
                    'fechafin',
                    'totalhoras',
                    'vigente',
                    'costo',
                    'condonado',
                    'objetivo',
                    'estudiante_id',
                    'modalidad_id',
                    'motivo_id'];

    
    
    
    
    public function mensajes()
    {
        return $this->morphToMany('App\Models\Mensaje', 'mensajeable');
    }

    public function estudiante()
    {
        return $this->belongsTo(Estudiante::class);
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class);
    }
    
    public function motivo()
    {
        return $this->belongsTo(Motivo::class);
    }
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    

    //*** copiar esto a todos los modelos que tengan observaciones  */
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }

    //*** copiar esto a todos los modelos que tengan observaciones  */
    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function sesiones()
    {
        return $this->hasMany(Sesion::class);
    }
    public function programaciones()
    {
        return $this->hasMany(Programacion::class);
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
