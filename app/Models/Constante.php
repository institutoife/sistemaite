<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Constante extends Model
{
    use HasFactory;
    protected $fillable = [
        'constante', 'valor', 'cuenta', 'plataforma', 'clave', 'descripcion'
    ];
}
