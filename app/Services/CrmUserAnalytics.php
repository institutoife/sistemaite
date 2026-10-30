<?php
namespace App\Services;

use App\Models\{Prospecto,User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CrmUserAnalytics
{
    private function events(array $filters, Carbon $start, Carbon $end)
    {
        $query=DB::table('crm_actividad_detalles as d')->join('observacions as o','o.id','=','d.observacion_id')
            ->join('prospectos as p','p.id','=','o.observable_id')->where('o.observable_type',Prospecto::class)
            ->whereBetween('d.ocurrio_at',[$start,$end]);
        if (!empty($filters['crm_sede_id'])) { $query->where('d.crm_sede_id',$filters['crm_sede_id']); }
        if (!empty($filters['interest_id'])) {
            $id=(int)$filters['interest_id'];
            if (DB::connection()->getDriverName()==='sqlite') { $query->whereRaw('EXISTS (SELECT 1 FROM json_each(d.servicios_ids) ci WHERE ci.value = ?)',[$id]); }
            else { $query->whereJsonContains('d.servicios_ids',$id); }
        }
        return $query;
    }

    public function report(array $filters): array
    {
        $start=Carbon::parse($filters['desde']??today()->startOfMonth()->toDateString())->startOfDay();
        $end=Carbon::parse($filters['hasta']??today()->toDateString())->endOfDay();
        $events=$this->events($filters,$start,$end);
        $rows=[];
        $empty=['registered'=>0,'activities'=>0,'stage_changes'=>0,'moved_leads'=>0,'assigned'=>0,'pending'=>0,'overdue'=>0,
            'conversions_done'=>0,'conversions_owned'=>0,'deactivated'=>0,'received'=>0,'won'=>0,'rate'=>null];
        $put=function ($id,$key,$value) use (&$rows,$empty) { $id=(int)$id; if (!isset($rows[$id])) { $rows[$id]=$empty; } $rows[$id][$key]=(int)$value; };

        // Creator remains immutable. A later assignment cannot change registrations attributed to that creator.
        $catalogFilters=array_intersect_key($filters,array_flip(['crm_sede_id','interest_id']));
        $portfolio=app(CrmService::class)->query($catalogFilters,false);
        foreach ((clone $portfolio)->whereBetween('created_at',[$start,$end])->groupBy('creado_por')->selectRaw('creado_por, COUNT(*) total')->get() as $row) { $put($row->creado_por,'registered',$row->total); }
        foreach ((clone $portfolio)->where('crm_status','activo')->groupBy('responsable_id')
            ->selectRaw('responsable_id, COUNT(*) assigned, SUM(CASE WHEN proximo_contacto_at IS NOT NULL THEN 1 ELSE 0 END) pending, SUM(CASE WHEN proximo_contacto_at < ? THEN 1 ELSE 0 END) overdue',[now()])->get() as $row) {
            foreach (['assigned','pending','overdue'] as $key) { $put($row->responsable_id,$key,$row->$key); }
        }
        foreach ((clone $events)->whereIn('d.tipo',array_keys(CrmService::TYPES))->groupBy('d.actor_id')->selectRaw('d.actor_id, COUNT(*) total')->get() as $row) { $put($row->actor_id,'activities',$row->total); }
        foreach ((clone $events)->where('d.tipo','etapa')->groupBy('d.actor_id')->selectRaw('d.actor_id, COUNT(*) actions, COUNT(DISTINCT o.observable_id) leads')->get() as $row) {
            $put($row->actor_id,'stage_changes',$row->actions); $put($row->actor_id,'moved_leads',$row->leads);
        }
        foreach ((clone $events)->where('d.tipo','baja')->groupBy('d.actor_id')->selectRaw('d.actor_id, COUNT(DISTINCT o.observable_id) total')->get() as $row) { $put($row->actor_id,'deactivated',$row->total); }
        $conversions=(clone $events)->where('d.tipo','conversion')->whereNotNull('p.convertido_at')->where(function ($q) { $q->whereNotNull('p.inscripcione_id')->orWhereNotNull('p.matriculacion_id'); });
        foreach ((clone $conversions)->groupBy('d.actor_id')->selectRaw('d.actor_id, COUNT(DISTINCT o.observable_id) total')->get() as $row) { $put($row->actor_id,'conversions_done',$row->total); }
        foreach ((clone $conversions)->groupBy('d.responsable_comercial_id')->selectRaw('d.responsable_comercial_id, COUNT(DISTINCT o.observable_id) total')->get() as $row) { $put($row->responsable_comercial_id,'conversions_owned',$row->total); }

        // One prospect per recipient in the assignment cohort, even if it moves between stages or is assigned back again.
        $received=(clone $events)->whereIn('d.tipo',['alta','reasignacion'])->where('d.asignacion_comercial',true)->whereNotNull('d.responsable_comercial_id')
            ->where(function ($q) { $q->whereNull('p.convertido_at')->orWhereColumn('p.convertido_at','>=','d.ocurrio_at'); })
            ->select(['d.responsable_comercial_id as owner_id','o.observable_id as lead_id'])->distinct();
        foreach (DB::query()->fromSub($received,'c')->join('prospectos as p','p.id','=','c.lead_id')->groupBy('c.owner_id')
            ->selectRaw('c.owner_id, COUNT(*) received, SUM(CASE WHEN p.responsable_conversion_id = c.owner_id AND p.convertido_at <= ? AND (p.inscripcione_id IS NOT NULL OR p.matriculacion_id IS NOT NULL) THEN 1 ELSE 0 END) won',[$end])->get() as $row) {
            $put($row->owner_id,'received',$row->received); $put($row->owner_id,'won',$row->won);
        }
        if (!empty($filters['usuario_id'])) { $id=(int)$filters['usuario_id']; $rows=[$id=>$rows[$id]??$empty]; }
        $names=User::whereIn('id',array_keys($rows))->pluck('name','id');
        $totals=$empty;
        foreach ($rows as $id=>&$row) {
            $row['id']=$id; $row['nombre']=$id?($names[$id]??'Usuario #'.$id.' (no disponible)'):'Sin registro histórico';
            $row['rate']=$row['received']?round($row['won']*100/$row['received'],1):null;
            foreach ($empty as $key=>$value) { if ($key!=='rate') { $totals[$key]+=$row[$key]; } }
        }
        unset($row);
        $distinctMoves=(clone $events)->where('d.tipo','etapa');
        if (!empty($filters['usuario_id'])) { $distinctMoves->where('d.actor_id',$filters['usuario_id']); }
        $totals['moved_leads']=$distinctMoves->distinct()->count('o.observable_id');
        $distinctBajas=(clone $events)->where('d.tipo','baja');
        if (!empty($filters['usuario_id'])) { $distinctBajas->where('d.actor_id',$filters['usuario_id']); }
        $totals['deactivated']=$distinctBajas->distinct()->count('o.observable_id');
        return compact('start','end','totals')+['rows'=>collect($rows)->sortBy('nombre')->values()];
    }
}
