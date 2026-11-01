<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Gestion extends Model
{
    use HasFactory;
    protected $table="estudiante_grado";
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
