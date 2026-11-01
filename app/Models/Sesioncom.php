<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Sesioncom extends Model
{
    use HasFactory;
    protected $dates = [
        'horainicio', 'horafin','created_at', 'updated_at',
    ];

    public function matriculacion()
    {
        return $this->belongsTo('App\Models\Matriculacion');
    }
    public function dia()
    {
        return $this->hasOne('App\Models\Dia');
    }
    public function aula()
    {
        return $this->hasOne('App\Models\Aula');
    }

    public function docente()
    {
        return $this->hasOne('App\Models\Docente');
    }

}
