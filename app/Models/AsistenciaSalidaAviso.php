<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaSalidaAviso extends Model
{
    protected $table = 'asistencia_salida_avisos';
    protected $guarded = ['id'];
    protected $casts = ['avisado_at'=>'datetime', 'pospuesto_hasta'=>'datetime'];
}
