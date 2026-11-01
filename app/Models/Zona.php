<?php

namespace App\Models;

use App\Models\BranchModel as Model;

class Zona extends Model
{
    protected $fillable=[
        'zona',
        'ciudad_id',
    ];


    /**
     * Get the persona associated with the Zona
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function persona()
    {
        return $this->hasOne(Persona::class);
    }

    /**
     * Get the ciudad that owns the Zona
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    public static function zonas($id){
        return Zona::where('ciudad_id','=',$id)->get();
    }

    public function usuarios()
    {
        return $this->morphToMany('App\Models\User', 'userable');
    }
}
