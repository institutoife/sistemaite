<?php

namespace App\Models;

use App\Models\BranchModel as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


/**
 * Class Colegio
 *
 * @property $id
 * @property $nombre
 * @property $rue
 * @property $director
 * @property $direccion
 * @property $telefono
 * @property $celular
 * @property $dependencia
 * @property $nivel
 * @property $turno
 * @property $departamento
 * @property $provincia
 * @property $municipio
 * @property $distrito
 * @property $areageografica
 * @property $coordenadax
 * @property $coordenaday
 * @property $created_at
 * @property $updated_at
 *
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Colegio extends Model
{
    use HasFactory;
    
    static $rules = [
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'rue',
        'director',
        'direccion',
        'telefono',
        'celular',
        'dependencia',
        'nivel',
        'turno',
        'departamento',
        'provincia',
        'municipio',
        'departamento_id',
        'provincia_id',
        'municipio_id',
        'distrito',
        'areageografica',
        'coordenadax',
        'coordenaday'
    ];
    
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }

    public function niveles(){
        return $this->belongsToMany(Nivel::class);
    }
    
}
