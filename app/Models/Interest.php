<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Interest extends Model
{
    use HasFactory;

    public function personas() {
        return $this->belongsToMany(Persona::class);
    }
    public function comentarios() {
        return $this->belongsToMany(Comentario::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
}
