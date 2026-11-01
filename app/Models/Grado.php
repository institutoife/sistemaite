<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Grado
 *
 * @property $id
 * @property $grado
 * @property $nivel
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Grado extends Model
{
    
    static $rules = [
		'grado' => 'required|min:5|max:30|unique:grados',
		'nivel_id' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['grado','nivel_id','grado_id'];

  public function nivel()
  {
    return $this->belongsTo(Nivel::class);
  }
  public function usuarios()
  {
    return $this->morphToMany('App\Models\User', 'userable');
  }

  public function estudiantes()
  {
    return $this->belongsToMany(Estudiante::class);
  }
  

}
