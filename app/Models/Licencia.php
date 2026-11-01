<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Licencia extends Model
{
    
    use HasFactory;
    public function clase(){
        return $this->belongsTo(Clase::class);
    }

    public function programacion(){
        return $this->hasOne(Programacion::class);
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    public function licenciable()
    {
        return $this->morphTo();    
    }
}
