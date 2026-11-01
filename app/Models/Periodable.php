<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Periodable extends Model
{
    use HasFactory;
    protected $dates=[
        'fechaini','fechafin'
    ];
    public function periodable()
    {
        return $this->morphTo();
    }
    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }
    public function observaciones()
    {
        return $this->morphMany(Observacion::class, 'observable');
    }
}
