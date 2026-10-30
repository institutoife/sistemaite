<?php
namespace App\Services;

use App\Models\Prospecto;
use Illuminate\Support\Facades\DB;

class CrmHistoryBackfill
{
    public function run(): void
    {
        // Copy services once. Repeating deployment must never restore a service removed by an operator.
        DB::table('prospectos')->whereNull('crm_services_migrated_at')->orderBy('id')->chunkById(200,function ($records) {
            $personal=DB::table('interest_persona')->whereIn('persona_id',$records->pluck('legacy_persona_id')->filter())
                ->get()->groupBy('persona_id');
            $valid=DB::table('interests')->pluck('id')->flip();
            foreach ($records as $record) {
                $ids=collect([$record->interest_id])->merge(($personal[$record->legacy_persona_id]??collect())->pluck('interest_id'))
                    ->filter(function ($id) use ($valid) { return isset($valid[$id]); })->unique();
                DB::transaction(function () use ($record,$ids) {
                    $current=DB::table('prospectos')->where('id',$record->id)->lockForUpdate()->first();
                    if (!$current||$current->crm_services_migrated_at) { return; }
                    foreach ($ids as $id) { DB::table('interest_prospecto')->insertOrIgnore(['prospecto_id'=>$record->id,'interest_id'=>$id]); }
                    DB::table('prospectos')->where('id',$record->id)->update(['crm_services_migrated_at'=>now()]);
                });
            }
        });
        // Recover only facts supported by the original audit. Never guess historical commercial ownership.
        DB::table('crm_actividad_detalles')->orderBy('observacion_id')->chunkById(200,function ($records) {
            $observations=DB::table('observacions')->whereIn('id',$records->pluck('observacion_id'))->get()->keyBy('id');
            $actors=DB::table('userables')->where('userable_type',\App\Models\Observacion::class)
                ->whereIn('userable_id',$records->pluck('observacion_id'))->get()->groupBy('userable_id');
            foreach ($records as $record) {
                $observation=$observations[$record->observacion_id]??null;
                if (!$observation||$observation->observable_type!==Prospecto::class) { continue; }
                $users=($actors[$record->observacion_id]??collect())->pluck('user_id')->unique();
                $actor=$record->actor_id?:($users->count()===1?$users->first():null);
                $update=[];
                if (!$record->actor_id&&$actor) { $update['actor_id']=$actor; }
                if (!$record->registrado_at&&$observation->created_at&&substr($observation->created_at,0,4)!=='0000') { $update['registrado_at']=$observation->created_at; }
                if ($update) { DB::table('crm_actividad_detalles')->where('observacion_id',$record->observacion_id)->update($update); }
                if ($record->tipo==='alta'&&$actor) { DB::table('prospectos')->where('id',$observation->observable_id)->whereNull('creado_por')->update(['creado_por'=>$actor]); }
                if ($record->tipo==='conversion'&&$actor) { DB::table('prospectos')->where('id',$observation->observable_id)->whereNotNull('convertido_at')->whereNull('convertido_por')->update(['convertido_por'=>$actor]); }
            }
        },'observacion_id');
        DB::table('prospectos')->orderBy('id')->chunkById(200,function ($records) {
            $last=DB::table('crm_actividad_detalles as d')->join('observacions as o','o.id','=','d.observacion_id')
                ->where('o.observable_type',Prospecto::class)->whereIn('o.observable_id',$records->pluck('id'))
                ->whereIn('d.tipo',CrmService::CONTACT_TYPES)->groupBy('o.observable_id')->selectRaw('o.observable_id, MAX(d.ocurrio_at) as last_at')->pluck('last_at','observable_id');
            foreach ($records as $record) {
                if (isset($last[$record->id])&&(!$record->ultimo_seguimiento_at||$last[$record->id]>$record->ultimo_seguimiento_at)) {
                    DB::table('prospectos')->where('id',$record->id)->update(['ultimo_seguimiento_at'=>$last[$record->id]]);
                }
            }
        });
    }
}
