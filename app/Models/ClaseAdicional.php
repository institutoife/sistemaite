<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseAdicional extends Model
{
    protected $table = 'clases_adicionales';
    protected $guarded = ['id'];

    public function programacion() { return $this->belongsTo(Programacion::class); }
    public function programacioncom() { return $this->belongsTo(Programacioncom::class); }
    public function originalProgramacion() { return $this->belongsTo(Programacion::class, 'original_programacion_id'); }
    public function originalProgramacioncom() { return $this->belongsTo(Programacioncom::class, 'original_programacioncom_id'); }
    public function creador() { return $this->belongsTo(User::class, 'creado_por'); }
    public function tema() { return $this->belongsTo(Tema::class); }
    public function getProgramaAttribute() { return $this->programacion_id ? $this->programacion : $this->programacioncom; }
    public function getOriginalAttribute() { return $this->original_programacion_id ? $this->originalProgramacion : $this->originalProgramacioncom; }
    public function getConsumeOriginalAttribute(): bool { return $this->original_programacion_id!==null || $this->original_programacioncom_id!==null; }
    public function scopeConOriginal($query) { return $query->where(fn($q)=>$q->whereNotNull('original_programacion_id')->orWhereNotNull('original_programacioncom_id')); }
    public function getEtiquetaAttribute() { return ['recuperacion'=>'Recuperación', 'adelanto'=>'Adelanto', 'extra'=>'Extra'][$this->tipo] ?? $this->tipo; }
}
