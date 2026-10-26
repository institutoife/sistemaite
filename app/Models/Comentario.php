<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    use HasFactory;
    
    protected $guarded =[];

    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
    public function como(){
        return $this->belongsTo(Como::class);
    }
    public function persona(){
        return $this->belongsTo(Persona::class);
    }
    public function usuarios()
    {
        return $this->morphToMany(User::class, 'userable');
    }
    public function interests() {
        return $this->belongsToMany(Interest::class)->withTimestamps();
    }
}
