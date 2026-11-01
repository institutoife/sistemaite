<?php

namespace App\Models;

use App\Models\BranchModel as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ciudad extends Model
{
    use HasFactory;
    protected $fillable=[
        'ciudad',
        'pais_id',
    ];
    /**
     * Get the persona associated with the Ciudad
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function persona()
    {
        return $this->hasOne(Persona::class);
    }
    /**
     * Get all of the zonas for the Ciudad
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function zonas()
    {
        return $this->hasMany(Zona::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }

}
