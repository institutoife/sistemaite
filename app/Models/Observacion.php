<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Observacion
 *
 * @property $id
 * @property $Objetivo
 * @property $activo
 * @property $inscripcione_id
 * @property $created_at
 * @property $updated_at
 * @property Inscripcione $inscripcione
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Observacion extends Model
{
    protected static function booted()
    {
        $protectCrmHistory=function (Observacion $observation) {
            if (\Illuminate\Support\Facades\Schema::hasTable('crm_actividad_detalles')&&$observation->crmDetalle()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['observacion'=>'El historial del CRM se conserva sin modificaciones. Registre una nueva actividad para aclarar o corregir la gestión.']);
            }
        };
        static::updating($protectCrmHistory);
        static::deleting($protectCrmHistory);
    }
    
    static $rules = [
		'activo' => 'required',
		'inscripcione_id' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['observacion','activo','observable_id','observable_type'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function observable()
    {
        return $this->morphTo();
    }
    public function crmDetalle() { return $this->hasOne(CrmActividadDetalle::class,'observacion_id'); }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    

}
