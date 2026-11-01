<?php
namespace App\Models;

use App\Models\BranchModel as Model;

class HistorialBusqueda extends Model
{
    protected $table = 'historial_busquedas';
    protected $fillable = ['user_id', 'termino', 'ip'];
    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
