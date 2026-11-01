<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserAccessAudit extends Model
{
    public $timestamps=false;
    protected $guarded=['id'];
    protected $casts=['before_values'=>'array','after_values'=>'array','created_at'=>'datetime'];
    protected static function booted()
    {
        $immutable=function(){throw new \LogicException('El historial de accesos no se sobrescribe.');};
        static::updating($immutable);static::deleting($immutable);
    }
    public function actor() { return $this->belongsTo(User::class,'actor_id'); }
}
