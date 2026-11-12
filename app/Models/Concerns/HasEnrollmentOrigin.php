<?php
namespace App\Models\Concerns;

use Illuminate\Support\Facades\{DB,Schema};

trait HasEnrollmentOrigin
{
    public function scopeWithSucursalOrigen($query)
    {
        $table=$this->getTable();
        if($query->getQuery()->columns===null)$query->select($table.'.*');
        if(!Schema::hasTable('sede_registros')||!Schema::hasTable('crm_sedes'))return $query->selectRaw('NULL AS sucursal_origen');
        return $query->addSelect(['sucursal_origen'=>DB::table('sede_registros as enrollment_origin')
            ->join('crm_sedes as enrollment_branch','enrollment_branch.id','=','enrollment_origin.sede_id')
            ->where('enrollment_origin.tabla',$table)->whereColumn('enrollment_origin.registro_id',$table.'.id')
            ->select('enrollment_branch.nombre')->limit(1)]);
    }
}
