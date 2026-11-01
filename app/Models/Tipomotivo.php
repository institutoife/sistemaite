<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Tipomotivo extends Model
{
    use HasFactory; 

    static $rules = [
		'tipomotivo' => 'required|min:5',
    ];

     public function motivos()
    {
        return $this->hasMany('App\Models\Motivo');
    }
   public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
