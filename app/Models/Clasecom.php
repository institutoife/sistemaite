<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Clasecom extends Model
{
    use HasFactory;
     protected $dates = [
        'fecha',
        'horainicio',
        'horafin',
    ];

    public function programacioncom()
    {
        return $this->belongsTo(Programacioncom::class);
    }

    public function docente()
    {
        return $this->belongsTo(Docente::class);
    }
    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function estado(){
        return $this->belongsTo(Estado::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
