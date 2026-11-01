<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class CrmSeguimiento extends Model
{
    use HasFactory;

    protected $table = 'crm_seguimientos';

    protected $fillable = [
        'persona_id',
        'user_id',
        'estado_id',
        'accion',
        'canal',
        'motivo',
        'mensaje_sugerido',
        'proximo_contacto',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }
}

