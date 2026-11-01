<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Tipofile extends Model
{
    /**
     * este modelo por ahora no se esta usando ya que el tipo se esta extrayendo del archivo con el metoro extension();
     */
    use HasFactory;

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
