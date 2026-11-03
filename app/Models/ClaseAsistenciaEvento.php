<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseAsistenciaEvento extends Model
{
    protected $table = 'clase_asistencia_eventos';
    protected $guarded = ['id'];
    protected $casts = ['antes'=>'array', 'despues'=>'array'];
}
