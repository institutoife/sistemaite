<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospecto extends Model
{
    use HasFactory;
    protected $casts=['no_contactar'=>'boolean','proximo_contacto_at'=>'datetime','etapa_desde'=>'datetime',
        'contactado_at'=>'datetime','convertido_at'=>'datetime','baja_at'=>'datetime'];
    public function etapa() { return $this->belongsTo(CrmEtapa::class,'crm_etapa_id'); }
    public function sede() { return $this->belongsTo(CrmSede::class,'crm_sede_id'); }
    public function interest() { return $this->belongsTo(Interest::class); }
    public function como() { return $this->belongsTo(Como::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function responsable() { return $this->belongsTo(User::class,'responsable_id'); }
    public function persona() { return $this->belongsTo(Persona::class); }
    public function estudiantePersona() { return $this->belongsTo(Persona::class,'estudiante_persona_id'); }
    public function getIdentificacionAttribute(): string { return trim((string)$this->nombre) ?: 'Prospecto IFE | '.$this->telefono; }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
      public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }

   
}
