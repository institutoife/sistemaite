<?php

namespace App\Models;

use App\Inscripcione;
use App\Dia;
use App\Docente;
use App\Materia;
use App\Aula;



use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Sesion extends Model
{
    use HasFactory;

    static $rules = [
        'horainicio' => 'required',
        'horafin' => 'required',
    ];
    protected $perPage = 20;
    protected $dates = [
        'horainicio', 'horafin','created_at', 'updated_at',
    ];
    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'horainicio',
        'horafin',
        'inscripcione_id',
        'dia_id',
        'docente_id',
        'materia_id',
        'aula_id',
    ];


    public function inscripcion()
    {
        return $this->belongsTo(Inscripcione::class);
    }

    public function dia()
    {
        return $this->hasOne(Dia::class);
    }
    public function materia()
    {
        return $this->belongsTo(Materia::class);
    }
    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    


    
}
