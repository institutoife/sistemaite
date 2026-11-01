<?php

namespace App\Models;

use App\Models\BranchModel as Model;

/**
 * Class Provincia
 *
 * @property $id
 * @property $provincia
 * @property $departamento_id
 * @property $created_at
 * @property $updated_at
 *
 * @property Departamento $departamento
 * @property Municipio[] $municipios
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Provincia extends Model
{
    
    static $rules = [
		'provincia' => 'required',
		'departamento_id' => 'required',
		'pais_id' => 'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['provincia','departamento_id'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function municipios()
    {
        return $this->hasMany(Municipio::class);
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
    

}
