<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Billete
 *
 * @property $id
 * @property $billete200
 * @property $billete100
 * @property $billete50
 * @property $billete20
 * @property $billete10
 * @property $moneda5
 * @property $moneda2
 * @property $moneda1
 * @property $moneda50
 * @property $moneda20
 * @property $moneda10
 * @property $dolares100
 * @property $dolares50
 * @property $dolares20
 * @property $dolares10
 * @property $dolares5
 * @property $dolares1
 * @property $pagable_id
 * @property $pagable_type
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Billete extends Model
{
    
    static $rules = [
		'pagable_id' => 'required',
		'pagable_type' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable=['corte'];

  public function pagos()
  {
    return $this->morphedByMany(Pago::class, 'billetable');
  }
  public function usuarios()
  {
      return $this->morphToMany('App\Models\User', 'userable');
  }

}
