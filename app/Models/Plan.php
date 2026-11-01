<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Plan extends Model
{
    use HasFactory;
     public function convenio()
    {
        return $this->belongsTo(Convenio::class);
    }
    public function caracteristicas()
    {
        return $this->hasMany(Caracteristica::class);
    }
     public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
