<?php

namespace App\Models;

use App\Models\BranchModel as Model;

class Venta extends Model
{
    protected $fillable = [
        'total',
        'descuento',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos()
    {
        return $this->morphMany(Pago::class, 'pagable');
    }

    public function usuarios()
    {
        return $this->morphToMany(User::class, 'userable');
    }
}

