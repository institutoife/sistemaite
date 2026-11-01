<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BranchModel as Model;

class Curso extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
}
