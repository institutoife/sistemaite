<?php

namespace App\Models;

use App\Models\BranchModel as Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'codigo',
        'codigo_qr',
        'codigo_barras',
        'costo',
        'precio',
        'stock',
        'stock_minimo',
        'activo',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'precio' => 'decimal:2',
        'stock_minimo' => 'integer',
        'activo' => 'boolean',
    ];

    public function detallesVenta()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}
