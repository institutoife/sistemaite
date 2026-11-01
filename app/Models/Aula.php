<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Aula extends Model
{
    use HasFactory;

    public function sesion()
    {
        return $this->hasOne(Sesion::class);
    }
  
    public function clases()
    {
        return $this->hasMany(Clase::class);
    }
    public function clasescom()
    {
        return $this->hasMany(Clasecom::class);
    }
    public function sesioncom()
    {
        return $this->belongsTo(Sesion::class);
    }
    public function programacioncom()
    {
        return $this->hasOne(Programacioncom::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }

}
