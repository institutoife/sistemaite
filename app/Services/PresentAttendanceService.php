<?php
namespace App\Services;

use App\Models\{AsistenciaSalidaAviso, Clase, Clasecom, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;

class PresentAttendanceService
{
    public const TIMEZONE = 'America/La_Paz';
    private $ready;

    public function ready(): bool
    {
        return $this->ready ?? ($this->ready = Schema::hasTable('asistencia_salida_avisos'));
    }

    public function canList(?User $actor, ?string $channel = null): bool
    {
        if (!$actor || app(AccountSecurity::class)->family($actor)) return false;
        if ($channel === null) return $this->canList($actor, 'apoyo') || $this->canList($actor, 'computacion');
        return $actor->can('Listar '.($channel === 'apoyo' ? 'Clases' : 'Clasescom'));
    }

    public function widget(): bool
    {
        return auth()->check() && !request()->routeIs('security.*') && $this->canList(auth()->user()) && $this->ready();
    }

    public function instant(Model $attendance, string $field): Carbon
    {
        return Carbon::parse($attendance->fecha->format('Y-m-d').' '.$attendance->$field->format('H:i:s'), self::TIMEZONE);
    }

    private function restrictTeacher($query, User $actor): void
    {
        if ($actor->hasRole('Docente') && !$actor->hasAnyRole(['Admin','Secretaria'])) {
            $query->where('docente_id', optional(optional($actor->persona)->docente)->id ?? 0);
        }
    }

    public function rows(User $actor, ?string $onlyChannel = null): Collection
    {
        abort_unless($this->canList($actor, $onlyChannel), 403);
        $rows=collect();
        foreach (['apoyo','computacion'] as $channel) {
            if (($onlyChannel && $onlyChannel !== $channel) || !$this->canList($actor, $channel)) continue;
            $support=$channel === 'apoyo';
            $class=$support ? Clase::class : Clasecom::class;
            $relation=$support ? 'programacion' : 'programacioncom';
            $enrollmentPath=$relation.($support ? '.inscripcione' : '.matriculacion');
            $query=$class::with([$enrollmentPath.($support ? '.estudiante.persona' : '.computacion.persona'),
                $enrollmentPath.($support ? '.modalidad' : '.asignatura'), $relation.'.adicional', 'docente','aula','usuarios']);
            if ($support) $query->with('materia','tema');
            $query->where('estado_id',estado('PRESENTE'))->whereDate('fecha','<=',now(self::TIMEZONE)->toDateString());
            $this->restrictTeacher($query,$actor);
            // Scoped Eloquent relations retain migrated rows without a userable or optional topic.
            foreach ($query->orderBy('fecha')->orderBy('horafin')->get() as $attendance) {
                $program=$attendance->$relation;
                $enrollment=$program ? ($support ? $program->inscripcione : $program->matriculacion) : null;
                if (!$enrollment) continue; // A parent outside the authorized branch must never leak.
                $person=$support ? optional($enrollment->estudiante)->persona : optional($enrollment->computacion)->persona;
                if (!$person) continue;
                $rows->push(['attendance'=>$attendance,'program'=>$program,'enrollment'=>$enrollment,'person'=>$person,'channel'=>$channel]);
            }
        }
        $notices=collect();
        if ($this->ready() && $rows->isNotEmpty()) {
            $notices=AsistenciaSalidaAviso::where(function($q) use($rows) {
                foreach ($rows->groupBy('channel') as $channel=>$items) {
                    $q->orWhere(fn($p)=>$p->where('canal',$channel)->whereIn('asistencia_id',$items->pluck('attendance.id')));
                }
            })->orderBy('avisado_at')->get()->groupBy(fn($n)=>$n->canal.':'.$n->asistencia_id);
        }
        $branches=$this->branches($rows);
        return $rows->map(function($row) use($actor,$notices,$branches) {
            extract($row);
            $key=$channel.':'.$attendance->id;
            $states=$notices->get($key,collect());
            $own=$states->firstWhere('usuario_id',$actor->id);
            $latest=$states->filter(fn($n)=>$n->avisado_at)->last();
            $start=$this->instant($attendance,'horainicio'); $end=$this->instant($attendance,'horafin');
            $support=$channel==='apoyo';
            $history=route('asistencia.historial',[$channel,$enrollment->id]);
            $canFinish=app(ClassAttendanceService::class)->canFinish($attendance,$actor);
            $editUrl=!$program->adicional && $actor->can('Editar '.($support?'Clases':'Clasescom'))
                ? route($support?'clases.edit':'clasescom.edit',$attendance->id) : null;
            $branch=$branches->get($attendance->getTable().':'.$attendance->id)
                ?? $branches->get($program->getTable().':'.$program->id)
                ?? $branches->get($enrollment->getTable().':'.$enrollment->id) ?? 'Sin sucursal registrada';
            return ['key'=>$key,'id'=>$attendance->id,'canal'=>$channel,'codigo'=>$person->id,
                'estudiante'=>trim(implode(' ',array_filter([$person->nombre,$person->apellidop,$person->apellidom]))),
                'docente'=>optional($attendance->docente)->nombrecorto ?? 'Sin docente registrado',
                'materia'=>$support ? optional($attendance->materia)->materia : optional($enrollment->asignatura)->asignatura,
                'aula'=>optional($attendance->aula)->aula,'sucursal'=>$branch,
                'inicio'=>$start->toIso8601String(),'fin'=>$end->toIso8601String(),
                'tipo'=>optional($program->adicional)->etiqueta ?? ($support?'Inscripción':'Matriculación'),
                'inscripcion_id'=>$enrollment->id,'programacion_id'=>$program->id,
                'registrado_por'=>$attendance->usuarios->pluck('name')->join(', ') ?: 'Registro anterior',
                'avisado_at'=>$latest && $latest->avisado_at ? $latest->avisado_at->toIso8601String() : null,
                'avisado_por'=>$latest ? $latest->usuario_id : null,
                'pospuesto_hasta'=>$own && $own->pospuesto_hasta ? $own->pospuesto_hasta->toIso8601String() : null,
                'can_finish'=>$canFinish,
                'history_url'=>$history,'persona_url'=>route('personas.show',$person->id),
                'edit_url'=>$editUrl,
                'finish_url'=>route($support?'clases.finalizar':'clasecom.finalizar'),
                'notify_url'=>route('presentes.aviso',[$channel,$attendance->id]),
                'snooze_url'=>route('presentes.posponer',[$channel,$attendance->id]),
                // Preserve the old DataTables endpoints for existing consumers.
                'name'=>trim(implode(' ',array_filter([$person->nombre,$person->apellidop,$person->apellidom]))),
                'horainicio'=>$start->toIso8601String(),'horafin'=>$end->toIso8601String(),
                'nombrecorto'=>optional($attendance->docente)->nombrecorto,'nombre'=>optional($attendance->docente)->nombrecorto,
                'asignatura'=>$support?null:optional($enrollment->asignatura)->asignatura,
                'tema'=>$support?optional($attendance->tema)->tema:null,'foto'=>$person->foto,
                'user'=>$attendance->usuarios->pluck('name')->join(', '),
                'programacioncom_id'=>$support?null:$program->id,
                'btn'=>view('clase.asistencia.present-actions',['history'=>$history,'label'=>optional($program->adicional)->etiqueta,
                    'canFinish'=>$canFinish,'editUrl'=>$editUrl])->render()];
        })->sortBy('fin')->values();
    }

    private function branches(Collection $rows): Collection
    {
        if ($rows->isEmpty() || !Schema::hasTable('sede_registros')) return collect();
        $records=collect();
        foreach($rows as $row) foreach(['attendance','program','enrollment'] as $type) $records->push($row[$type]);
        return DB::table('sede_registros as r')->join('crm_sedes as s','s.id','=','r.sede_id')
            ->where(function($q) use($records) {
                foreach($records->groupBy(fn($m)=>$m->getTable()) as $table=>$items) {
                    $q->orWhere(fn($p)=>$p->where('r.tabla',$table)->whereIn('r.registro_id',$items->pluck('id')->unique()));
                }
            })->get(['r.tabla','r.registro_id','s.nombre'])->mapWithKeys(fn($r)=>[$r->tabla.':'.$r->registro_id=>$r->nombre]);
    }

    public function act(string $channel, int $id, User $actor, ?int $minutes = null): void
    {
        abort_unless(in_array($channel,['apoyo','computacion'],true),404);
        abort_unless($this->canList($actor,$channel),403);
        abort_unless($this->ready(),503,'Falta aplicar la migración de avisos de salida.');
        $class=$channel==='apoyo' ? Clase::class : Clasecom::class;
        DB::transaction(function() use($channel,$id,$actor,$minutes,$class) {
            // Same person -> program -> attendance lock order as finish(), including both circuits.
            $attendance=$class::findOrFail($id);
            $service=app(ClassAttendanceService::class);
            abort_unless($service->canFinish($attendance,$actor),403);
            $program=$channel==='apoyo'?$attendance->programacion:$attendance->programacioncom;
            abort_unless($program && $service->enrollment($program),404);
            DB::table('personas')->where('id',$service->personId($service->enrollment($program)))->lockForUpdate()->first();
            $program=$program->newQuery()->whereKey($program->id)->lockForUpdate()->firstOrFail();
            $attendance=$class::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($service->canFinish($attendance,$actor),403);
            if ((int)$attendance->estado_id !== (int)estado('PRESENTE') || $this->instant($attendance,'horafin')->gt(now(self::TIMEZONE))) {
                throw ValidationException::withMessages(['asistencia'=>'El aviso corresponde a una asistencia presente cuyo horario ya terminó.']);
            }
            $notice=AsistenciaSalidaAviso::firstOrNew(['canal'=>$channel,'asistencia_id'=>$id,'usuario_id'=>$actor->id]);
            if ($minutes===null && $notice->avisado_at) return; // Repeated click never duplicates the audit.
            if ($minutes===null) { $notice->avisado_at=now(self::TIMEZONE); }
            else { $notice->pospuesto_hasta=now(self::TIMEZONE)->addMinutes($minutes); }
            $notice->save();
            $service->event($program,$actor,$minutes===null?'aviso_salida':'salida_pospuesta',null,
                ['asistencia_id'=>$id,'canal'=>$channel,'avisado_at'=>optional($notice->avisado_at)->toIso8601String(),
                    'pospuesto_hasta'=>optional($notice->pospuesto_hasta)->toIso8601String()],
                $minutes===null?'Se avisó al estudiante; la salida sigue pendiente.':'Aviso pospuesto '.$minutes.' minutos para este usuario.');
        },3);
    }
}
