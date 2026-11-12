<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Matriculacion extends Model
{
    use HasFactory;
    use \App\Models\Concerns\HasEnrollmentOrigin;

    protected $dates = [
        'fechaini','fechafin','fecha_proximo_pago','created_at', 'updated_at',
    ];

    public function motivo()
    {
        return $this->belongsTo(Motivo::class);
    }
    public function sesionescoms()
    {
        return $this->hasMany('App\Models\Sesioncom');
    }
    public function programacionescom()
    {
        return $this->hasMany('App\Models\Programacioncom');
    }
    
    public function computacion()
    {
        return $this->belongsTo(Computacion::class);// cambiado de constante a class
    }
    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function asignatura()
    {
        return $this->belongsTo(Asignatura::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function mensajes()
    {
        return $this->morphToMany('App\Models\Mensaje', 'mensajeable');
    }
}
