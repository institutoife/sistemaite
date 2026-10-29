<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CrmActividadDetalle extends Model
{
    protected $table='crm_actividad_detalles';
    protected $primaryKey='observacion_id';
    public $incrementing=false;
    public $timestamps=false;
    protected $guarded=[];
    protected $casts=['datos'=>'array','ocurrio_at'=>'datetime','proximo_contacto_at'=>'datetime'];
    public function responsable() { return $this->belongsTo(User::class,'responsable_id'); }
}
