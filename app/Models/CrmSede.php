<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CrmSede extends Model
{
    protected $guarded=['id'];
    protected $casts=['activa'=>'boolean'];
}
