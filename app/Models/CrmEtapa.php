<?php
namespace App\Models;
use App\Models\BranchModel as Model;
class CrmEtapa extends Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
    protected $guarded=['id'];
    protected $casts=['activa'=>'boolean','conversion'=>'boolean'];
}
