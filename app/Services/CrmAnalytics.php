<?php
namespace App\Services;
use Carbon\Carbon;
use App\Models\{CrmEtapa,Como,Interest,CrmActividadDetalle,Observacion,Prospecto};
class CrmAnalytics
{
    public function report(array $filters): array
    {
        $start=Carbon::parse($filters['desde']??today()->startOfMonth()->toDateString())->startOfDay();
        $end=Carbon::parse($filters['hasta']??today()->toDateString())->endOfDay();
        $base=app(CrmService::class)->query(array_diff_key($filters,array_flip(['status','agenda','crm_etapa_id'])),false);
        $cohort=(clone $base)->whereBetween('created_at',[$start,$end]);
        $confirmed=function ($query) { $query->whereNotNull('inscripcione_id')->orWhereNotNull('matriculacion_id'); };
        $converted=(clone $base)->where($confirmed)->whereBetween('convertido_at',[$start,$end]);
        $created=(clone $cohort)->count();
        $cohortConverted=(clone $cohort)->where($confirmed)->whereNotNull('convertido_at')->where('convertido_at','<=',$end)->count();
        $seconds=0; $validDates=0;
        (clone $converted)->select(['id','created_at','convertido_at'])->chunkById(200,function ($rows) use (&$seconds,&$validDates) {
            foreach ($rows as $row) {
                try { $first=Carbon::parse($row->getRawOriginal('created_at')); $last=Carbon::parse($row->getRawOriginal('convertido_at')); }
                catch (\Throwable $exception) { continue; }
                if ($last->gte($first)) { $seconds+=$last->diffInSeconds($first); $validDates++; }
            }
        });
        $labels=['crm_etapa_id'=>CrmEtapa::withTrashed()->pluck('nombre','id'),'como_id'=>Como::pluck('como','id')];
        $groups=[];
        foreach ($labels as $field=>$names) {
            $groups[$field]=(clone $cohort)->selectRaw($field.', COUNT(*) AS total')->groupBy($field)->get()->map(function ($row) use ($field,$names) {
                return ['nombre'=>$names[$row->$field]??'Sin definir','total'=>(int)$row->total];
            })->sortByDesc('total')->values();
        }
        $groups['interest_id']=\Illuminate\Support\Facades\DB::table('interest_prospecto as pi')->join('interests as i','i.id','=','pi.interest_id')
            ->whereIn('pi.prospecto_id',(clone $cohort)->select('prospectos.id'))->groupBy('i.id','i.interest')
            ->selectRaw('i.interest as nombre, COUNT(DISTINCT pi.prospecto_id) as total')->orderByDesc('total')->get()
            ->map(function ($row) { return ['nombre'=>$row->nombre,'total'=>(int)$row->total]; });
        $originsConverted=(clone $converted)->selectRaw('como_id, COUNT(*) AS total')->groupBy('como_id')->get()->map(function ($row) use ($labels) {
            return ['nombre'=>$labels['como_id'][$row->como_id]??'Sin definir','total'=>(int)$row->total];
        })->sortByDesc('total')->values();
        $reasons=collect(); $deactivations=0;
        $bajaQuery=CrmActividadDetalle::where('tipo','baja')->whereBetween('ocurrio_at',[$start,$end])
            ->whereIn('observacion_id',Observacion::where('observable_type',Prospecto::class)->whereIn('observable_id',(clone $base)->select('prospectos.id'))->select('observacions.id'));
        $bajaQuery->chunkById(200,function ($rows) use (&$reasons,&$deactivations) {
            foreach ($rows as $row) { $reason=$row->datos['motivo']??'otro'; $reasons[$reason]=($reasons[$reason]??0)+1; $deactivations++; }
        },'observacion_id');
        $reasons=$reasons->map(function ($count,$reason) { return (object)['motivo_baja'=>$reason,'total'=>$count]; })->sortByDesc('total')->values();
        $tasks=(clone $base)->whereNotNull('responsable_id');
        return ['start'=>$start,'end'=>$end,'created'=>$created,'converted'=>(clone $converted)->count(),
            'active'=>(clone $base)->where('crm_status','activo')->count(),'inactive'=>(clone $base)->where('crm_status','baja')->count(),'deactivations'=>$deactivations,
            'scheduled'=>(clone $tasks)->where('crm_status','activo')->whereNotNull('proximo_contacto_at')->count(),
            'overdue'=>(clone $tasks)->where('crm_status','activo')->where('proximo_contacto_at','<',now())->count(),
            'today'=>(clone $tasks)->where('crm_status','activo')->whereDate('proximo_contacto_at',today())->count(),
            'unscheduled'=>(clone $tasks)->where('crm_status','activo')->whereNull('proximo_contacto_at')->count(),
            'unanswered'=>(clone $tasks)->where('crm_status','activo')->where('ultimo_resultado','sin_respuesta')->count(),
            'uncontacted'=>(clone $tasks)->where('crm_status','activo')->whereNull('contactado_at')->count(),
            'rate'=>$created?round($cohortConverted*100/$created,1):null,'average_days'=>$validDates?round($seconds/$validDates/86400,1):null,
            'groups'=>$groups,'originsConverted'=>$originsConverted,'reasons'=>$reasons];
    }
}
