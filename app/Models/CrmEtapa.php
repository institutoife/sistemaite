<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CrmEtapa extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $guarded=['id'];
    protected $casts=['activa'=>'boolean','conversion'=>'boolean'];
}
