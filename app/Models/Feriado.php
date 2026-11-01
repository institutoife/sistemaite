<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Feriado
 *
 * @property $id
 * @property $fecha
 * @property $festividad
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Feriado extends Model
{
    
    static $rules = [
		'fecha' => 'required',
		'festividad' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['fecha','festividad'];

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }


}
