<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Como extends Model
{
    use HasFactory;
    public function personas()
    {
        return $this->hasMany(Persona::class);
    } 
    
    public function comentario()
    {
        return $this->hasOne(Comentario::class);
    } 
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
