<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

class PreserveAdditionalClassStates extends Migration
{
    public function up()
    {
        $states=[];
        foreach (['recuperacion'=>'RECUPERADA','adelanto'=>'ADELANTADA','extra'=>'EXTRA'] as $type=>$name) {
            // Keep the IDs already registered by the administrator.
            $states[$type]=DB::table('estados')->where('estado',$name)->value('id');
            if ($states[$type]===null) {
                $values=['estado'=>$name];
                foreach (['created_at','updated_at'] as $column) {
                    if (Schema::hasColumn('estados',$column)) $values[$column]=now();
                }
                $states[$type]=DB::table('estados')->insertGetId($values);
            }
        }
        $finished=DB::table('estados')->where('estado','FINALIZADO')->value('id');
        $present=DB::table('estados')->where('estado','PRESENTE')->value('id');
        if (!$finished || !Schema::hasTable('clases_adicionales')) return;

        foreach ([['programacions','clases','programacion_id'],['programacioncoms','clasecoms','programacioncom_id']] as [$table,$actual,$key]) {
            // Only effective, completed classes with a real attendance. Originals,
            // pending classes, open attendances and their real times remain intact.
            DB::table('clases_adicionales')->whereNotNull($key)->whereIn('tipo',array_keys($states))
                ->orderBy('id')->chunkById(200,function($links) use($table,$actual,$key,$states,$finished,$present) {
                    foreach ($links as $link) {
                        DB::transaction(function() use($table,$actual,$key,$states,$finished,$present,$link) {
                            $program=DB::table($table)->where('id',$link->$key)->lockForUpdate()->first();
                            if (!$program || (int)$program->estado_id!==(int)$finished) return;
                            if (DB::table('clases_adicionales')->where('original_'.$key,$program->id)->exists()) return;
                            $attendances=DB::table($actual)->where($key,$program->id)->lockForUpdate()->get(['estado_id']);
                            if (!$attendances->contains(fn($row)=>(int)$row->estado_id===(int)$finished)
                                || $attendances->contains(fn($row)=>(int)$row->estado_id===(int)$present)) return;
                            $next=(int)$states[$link->tipo];
                            DB::table($table)->where('id',$program->id)->update(['estado_id'=>$next]);
                            DB::table('clase_asistencia_eventos')->insert([
                                $key=>$program->id,'usuario_id'=>null,'usuario_nombre'=>'Sistema IFE',
                                'accion'=>'estado_adicional_actualizado',
                                'antes'=>json_encode(['estado_id'=>(int)$program->estado_id]),
                                'despues'=>json_encode(['estado_id'=>$next,'tipo'=>$link->tipo]),
                                'motivo'=>'Clasificación de la clase adicional terminada; se conservan la asistencia real y la programación original.',
                                'created_at'=>now(),'updated_at'=>now(),
                            ]);
                        },3);
                    }
                });
        }
    }

    public function down()
    {
        // Catalog IDs may predate this migration or already be referenced. Keep
        // both the classification and its audit trail when rolling back code.
    }
}
