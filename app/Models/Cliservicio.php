<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Cliservicio extends Model
{
    use HasFactory;
    protected $fillable = ['persona_id','requerimiento'];
    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
