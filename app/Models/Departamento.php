<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Departamento
 *
 * @property $id
 * @property $departamento
 * @property $created_at
 * @property $updated_at
 *
 * @property Provincia[] $provincias
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Departamento extends Model
{
    
    static $rules = [
		'departamento' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['departamento'];
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function provincias()
    {
        return $this->hasMany(Provincia::class);
    }
    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }


}
