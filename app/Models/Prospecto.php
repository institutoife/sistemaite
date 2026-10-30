<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prospecto extends Model
{
    use HasFactory;
    protected $casts=['no_contactar'=>'boolean','proximo_contacto_at'=>'datetime','etapa_desde'=>'datetime',
        'contactado_at'=>'datetime','convertido_at'=>'datetime','baja_at'=>'datetime','ultimo_seguimiento_at'=>'datetime'];
    public function etapa() { return $this->belongsTo(CrmEtapa::class,'crm_etapa_id')->withTrashed(); }
    public function sede() { return $this->belongsTo(CrmSede::class,'crm_sede_id'); }
    public function interest() { return $this->belongsTo(Interest::class); }
    public function interests() { return $this->belongsToMany(Interest::class,'interest_prospecto'); }
    public function creador() { return $this->belongsTo(User::class,'creado_por'); }
    public function responsableInicial() { return $this->belongsTo(User::class,'responsable_inicial_id'); }
    public function convertidoPor() { return $this->belongsTo(User::class,'convertido_por'); }
    public function responsableConversion() { return $this->belongsTo(User::class,'responsable_conversion_id'); }
    public function getDiasSinSeguimientoAttribute(): ?int
    {
        $date=$this->ultimo_seguimiento_at;
        $raw=(string)$this->getRawOriginal('created_at');
        if (!$date&&(!preg_match('/^([1-9]\d{3})-(\d{2})-(\d{2})/',$raw,$parts)||!checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1]))) { return null; }
        try { $date=$date?:\Carbon\Carbon::parse($raw); }
        catch (\Throwable $exception) { return null; }
        return $date&&$date->lte(now())?$date->diffInDays(now()):0;
    }
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
