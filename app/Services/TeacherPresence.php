<?php
namespace App\Services;

use Illuminate\Support\Facades\{DB, Schema};

class TeacherPresence
{
    /** Aggregate both circuits in one query, without loading students or their names. */
    public function snapshot(): array
    {
        $branches=Schema::hasTable('sede_registros') && Schema::hasTable('crm_sedes');
        $union=null;
        foreach (['apoyo','computacion'] as $channel) {
            $support=$channel==='apoyo';
            $query=DB::table(($support?'clases':'clasecoms').' as attendance')
                ->join(($support?'programacions':'programacioncoms').' as program','program.id','=','attendance.'.($support?'programacion_id':'programacioncom_id'))
                ->join(($support?'inscripciones':'matriculacions').' as enrollment','enrollment.id','=','program.'.($support?'inscripcione_id':'matriculacion_id'))
                ->join(($support?'estudiantes':'computacions').' as student','student.id','=','enrollment.'.($support?'estudiante_id':'computacion_id'))
                ->leftJoin('aulas as room','room.id','=','attendance.aula_id')
                ->where('attendance.estado_id',estado('PRESENTE'))
                ->where('attendance.fecha','<',now('America/La_Paz')->addDay()->toDateString())
                ->select('attendance.docente_id','attendance.aula_id','room.aula','student.persona_id');
            if ($branches) {
                $query->leftJoin('sede_registros as location',function($join) use($support) {
                    $join->on('location.registro_id','=','attendance.id')->where('location.tabla',$support?'clases':'clasecoms');
                })->leftJoin('sede_registros as room_location',function($join) {
                    $join->on('room_location.registro_id','=','attendance.aula_id')->where('room_location.tabla','aulas');
                })->leftJoin('crm_sedes as branch',function($join) {
                    $join->on('branch.id','=',DB::raw('COALESCE(location.sede_id,room_location.sede_id)'));
                })->selectRaw('COALESCE(location.sede_id,room_location.sede_id) AS sede_id, branch.nombre AS sucursal');
            } else $query->selectRaw('NULL AS sede_id, NULL AS sucursal');
            $union=$union ? $union->unionAll($query) : $query;
        }
        $rows=DB::query()->fromSub($union,'presence')->select('docente_id','aula_id','aula','sede_id','sucursal')
            ->selectRaw('COUNT(DISTINCT persona_id) AS presentes')
            ->groupBy('docente_id','aula_id','aula','sede_id','sucursal')->orderBy('aula')->get();
        return $rows->groupBy('docente_id')->map(fn($items)=>$items->map(fn($row)=>[
            'aula_id'=>$row->aula_id, 'aula'=>$row->aula ?? 'S/E', 'sede_id'=>$row->sede_id,
            'sucursal'=>$row->sucursal, 'presentes'=>(int)$row->presentes])->values()->all())->all();
    }

    public static function label(string $name, array $locations, ?int $branch): string
    {
        $locations=array_filter($locations,fn($location)=>$branch===null || $location['sede_id']===null || (int)$location['sede_id']===$branch);
        $labels=array_map(fn($location)=>$location['aula'].($branch===null && $location['sucursal']?' · '.$location['sucursal']:'').', '.$location['presentes'],$locations);
        return $name.' ('.($labels?implode(' / ',$labels):'S/E, 0').')';
    }
}
