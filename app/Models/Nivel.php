<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/** 
 * Class Nivel
 *
 * @property $id
 * @property $nivel
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Nivel extends Model
{
    
    static $rules = [
		'nivel' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['nivel','created_at'];

  /* realcion de muchos a muchos inversa polimorfica*/
  public function docentes()
  {
    return $this->morphedByMany(Docente::class, 'nivelable');
  }

  /** RELACION MUCHOS A MUCHO POLIMORFICO */
  public function materias()
  {
    return $this->morphToMany("App\Models\Materia", 'materiable'); 
  }

  public function modalidades()
  {
    return $this->hasMany(Modalidad::class);
  }

  public function colegios(){
        return $this->belongsToMany(Colegio::class);
    }

  public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
  public function grados()
  {
    return $this->hasMany(Nivel::class);
  }

  public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
}
