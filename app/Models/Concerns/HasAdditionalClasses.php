<?php
namespace App\Models\Concerns;

use App\Models\{ClaseAdicional, ClaseAsistenciaEvento, Programacion};

trait HasAdditionalClasses
{
    protected function performUpdate(\Illuminate\Database\Eloquent\Builder $query)
    {
        $historical=['fecha','estado_id','docente_id','materia_id','aula_id','hora_ini','hora_fin','horaini','horafin',
            'horas_por_clase','inscripcione_id','matriculacion_id','activo'];
        if(!$this->isDirty($historical)) return parent::performUpdate($query);
        return $this->getConnection()->transaction(function()use($query){
            // Recheck under the same row lock used when linking an original: a stale form
            // or cached relationship must not overwrite an original after compensation.
            $this->getConnection()->table($this->getTable())->where('id',$this->id)->lockForUpdate()->first();
            if(ClaseAdicional::where('original_'.$this->attendanceKey(),$this->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['programacion'=>'Esta clase ya fue consumida por una clase adicional. Conserve el original y corrija la clase efectiva.']);
            }
            return parent::performUpdate($query);
        },3);
    }

    public function attendanceKey(): string { return $this instanceof Programacion ? 'programacion_id' : 'programacioncom_id'; }
    public function adicional() { return $this->hasOne(ClaseAdicional::class, $this->attendanceKey()); }
    public function compensacion() { return $this->hasOne(ClaseAdicional::class, 'original_'.$this->attendanceKey()); }
    public function eventosAsistencia() { return $this->hasMany(ClaseAsistenciaEvento::class, $this->attendanceKey())->orderBy('id'); }
    public function scopeSinCompensar($query) { return $query->whereDoesntHave('compensacion'); }
    public function isContractedClass(): bool
    {
        // Preserve legacy extras without an original; new extras consume an existing class.
        return !$this->adicional || ($this->adicional->tipo==='extra' && !$this->adicional->consume_original);
    }
    public function scopeHorasContratadas($query)
    {
        return $query->where(function ($q) { $q->whereDoesntHave('adicional')->orWhereHas('adicional', fn($a) => $a->where('tipo','extra')->whereNull('original_programacion_id')->whereNull('original_programacioncom_id')); });
    }
}
