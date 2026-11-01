<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Clase extends Model
{
    use HasFactory;
    protected $fillable = [
        'fecha',
        'horainicio',
        'horafin',
        'estado',
        'pagado',
        'docente_id',
        'materia_id',
        'aula_id',
        'tema_id',
        'programacion_id',

    ];
    protected $dates = [
        'fecha',
        'horainicio',
        'horafin',

    ];
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    public function programacion()
    {
        return $this->belongsTo(Programacion::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }
    public function tema()
    {
        return $this->belongsTo(Tema::class);
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }
}