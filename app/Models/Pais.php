<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Pais extends Model
{
    use HasFactory;
    protected $fillable = [
        'nombrepais'
    ];
    
    /**
     * Get the persona th owns the Pais
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function persona()
    {
        return $this->hasOne(Persona::class);
    }
    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
     public function departamentos()
    {
        return $this->hasMany(Departamento::class);
    }
}