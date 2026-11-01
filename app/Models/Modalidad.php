<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Modalidad
 *
 * @property $id
 * @property $modalidad
 * @property $costo
 * @property $cargahoraria
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Modalidad extends Model
{
    protected $perPage = 20;
    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['modalidad','costo','cargahoraria','nivel_id'];

  public function inscripcion()
  {
    return $this->hasOne(Inscripcione::class);
  }

  public function nivel()
  {
    return $this->belongsTo(Nivel::class);
  }
  public function usuarios()
  {
      return $this->morphToMany('App\Models\User', 'userable');
  }

}
