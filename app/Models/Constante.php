<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Constante extends Model
{
    use HasFactory;
    protected $fillable = [
        'constante', 'valor', 'cuenta', 'plataforma', 'clave', 'descripcion'
    ];
}
