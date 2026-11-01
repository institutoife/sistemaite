<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable=[
        'nombre',
        'tipo',
        'orden',
        'icono',
        'detalle',
        'ruta',
    ];
}
