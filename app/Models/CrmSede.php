<?php
namespace App\Models;
use App\Models\BranchModel as Model;
class CrmSede extends Model
{
    protected $guarded=['id'];
    protected $casts=['activa'=>'boolean'];
}
