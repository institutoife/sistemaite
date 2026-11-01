<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Administrativo extends Model
{
    protected $dates=["fechaingreso"];
    use HasFactory;
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    // public function userable()
    // {
    //     return $this->morphOne('App\Models\Userable', 'userable');
    // }
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function periodos()
    {
        return $this->morphMany(Periodable::class,'periodable');
    }
    
}
