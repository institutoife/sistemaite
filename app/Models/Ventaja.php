<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Ventaja extends Model
{
    
    static $rules = [
		'descripcion' => 'required',
        'interest_id'=>'required',
    ];

    protected $perPage = 20;

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = ['descripcion'];
    /** 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */

    public function interest(){
        return $this->belongsTo("App\Models\Interest");
    }

}
