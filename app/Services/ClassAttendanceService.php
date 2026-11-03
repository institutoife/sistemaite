<?php
namespace App\Services;

use App\Models\{Aula, Clase, Clasecom, ClaseAdicional, ClaseAsistenciaEvento, Docente, Inscripcione,
    Matriculacion, Persona, Programacion, Programacioncom, Tema, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Validator};
use Illuminate\Validation\ValidationException;

class ClassAttendanceService
{
    public function definition(string $canal): array
    {
        abort_unless(in_array($canal, ['apoyo','computacion'], true), 404);
        return $canal === 'apoyo'
            ? [Programacion::class, Inscripcione::class, Clase::class, 'inscripcione_id', 'programacion_id', 'hora_ini', 'hora_fin', 'Clases', 'Programaciones']
            : [Programacioncom::class, Matriculacion::class, Clasecom::class, 'matriculacion_id', 'programacioncom_id', 'horaini', 'horafin', 'Clasescom', 'Programacioncom'];
    }

    public function canal(Model $programa): string { return $programa instanceof Programacion ? 'apoyo' : 'computacion'; }

    public function authorize(string $canal, User $actor, string $action = 'Crear', bool $programming = false): void
    {
        $d = $this->definition($canal);
        abort_unless($actor->can($action.' '.$d[7]), 403, 'No tiene permiso para gestionar estas asistencias.');
        if ($programming) {
            $programAction = $canal === 'computacion' && $action === 'Crear' ? 'Editar' : $action;
            abort_unless($actor->can($programAction.' '.$d[8]), 403, 'No tiene permiso para gestionar esta programación.');
        }
    }

    public function enrollment(Model $programa): Model
    {
        return $programa instanceof Programacion ? $programa->inscripcione : $programa->matriculacion;
    }

    public function personId(Model $enrollment): int
    {
        return (int) ($enrollment instanceof Inscripcione ? $enrollment->estudiante->persona_id : $enrollment->computacion->persona_id);
    }

    private function active(Model $enrollment): void
    {
        $this->check((bool)$enrollment->vigente && (int)$enrollment->estado_id === (int)estado('CORRIENDO'),
            'inscripcion', 'La inscripción o matrícula debe estar activa y vigente.');
    }

    private function check(bool $condition, string $field, string $message): void
    {
        if (!$condition) { throw ValidationException::withMessages([$field=>$message]); }
    }

    public function absenceIds(): array
    {
        return \App\Models\Estado::whereIn('estado',['FALTA','FALTANOTIFICADA','LICENCIA','CONGELADO','INDEFINIDO'])->pluck('id')->map(fn($id)=>(int)$id)->all();
    }

    private function validateData(array $data, bool $additional = false): array
    {
        $rules = ['fecha'=>'required|date_format:Y-m-d', 'horainicio'=>'required|date_format:H:i',
            'horafin'=>'required|date_format:H:i|after:horainicio', 'docente_id'=>'required|integer', 'aula_id'=>'required|integer',
            'materia_id'=>'nullable|integer', 'tema_id'=>'nullable|integer', 'motivo'=>'nullable|string|max:2000'];
        if ($additional) {
            $rules += ['tipo'=>'required|in:recuperacion,adelanto,extra', 'original_id'=>'required|integer',
                'solicitud'=>'required|uuid', 'motivo'=>'required|string|min:5|max:2000', 'marcar_entrada'=>'sometimes|boolean'];
            $rules['motivo'] = 'required|string|min:5|max:2000';
        }
        // Legacy forms may send seconds. Normalize without accepting malformed times.
        foreach (['horainicio','horafin'] as $time) {
            if (isset($data[$time]) && preg_match('/^\d{2}:\d{2}:[0-5]\d$/D', $data[$time])) { $data[$time] = substr($data[$time],0,5); }
        }
        return Validator::make($data, $rules)->validate();
    }

    private function references(Model $enrollment, array $data, string $canal): void
    {
        $teacher = Docente::findOrFail($data['docente_id']);
        $this->check((int)$teacher->estado_id === (int)estado('HABILITADO'), 'docente_id', 'El docente no está habilitado.');
        Aula::findOrFail($data['aula_id']);
        if ($canal === 'apoyo') {
            $this->check(!empty($data['materia_id']) && $enrollment->modalidad->nivel->materias->contains('id',(int)$data['materia_id']),
                'materia_id', 'Seleccione una materia del nivel de esta inscripción.');
            if (!empty($data['tema_id'])) {
                $this->check(Tema::whereKey($data['tema_id'])->where('materia_id',$data['materia_id'])->exists(),
                    'tema_id', 'El tema no pertenece a la materia seleccionada.');
            }
        }
    }

    private function minutes(string $start, string $end): int
    {
        return Carbon::parse($start)->diffInMinutes(Carbon::parse($end), false);
    }

    public function create(string $canal, int $enrollmentId, array $input, User $actor): Model
    {
        $this->authorize($canal, $actor, 'Crear', true);
        $data = $this->validateData($input, true);
        [$programClass, $enrollmentClass, , $enrollmentKey, $programKey, $start, $end] = $this->definition($canal);
        return DB::transaction(function () use ($canal,$enrollmentId,$actor,$data,$programClass,$enrollmentClass,$enrollmentKey,$programKey,$start,$end) {
            $enrollment = $enrollmentClass::findOrFail($enrollmentId);
            $person = $this->personId($enrollment);
            // One student lock covers both academic circuits and separate inscriptions.
            DB::table('personas')->where('id',$person)->lockForUpdate()->first();
            $enrollment = $enrollmentClass::whereKey($enrollmentId)->lockForUpdate()->firstOrFail();
            $this->active($enrollment);
            if ($previous = ClaseAdicional::where('solicitud',$data['solicitud'])->first()) {
                $program = $programClass::findOrFail($previous->$programKey);
                $this->check((int)$program->$enrollmentKey === $enrollmentId && (int)$previous->creado_por === $actor->id,
                    'solicitud','Esta solicitud ya fue utilizada.');
                return $program;
            }
            $this->references($enrollment,$data,$canal);
            DB::table('docentes')->where('id',$data['docente_id'])->lockForUpdate()->first();
            $duration = $this->minutes($data['horainicio'],$data['horafin']);
            $original = $programClass::where($enrollmentKey,$enrollmentId)->whereKey($data['original_id'])->lockForUpdate()->firstOrFail();
            $this->check($original->isContractedClass() && !$original->compensacion,
                'original_id', 'Esta clase ya fue utilizada en una recuperación, adelanto o clase extra. Consulte su historial.');
            $this->check(!$this->hasAttendance($original), 'original_id', 'La clase original ya tiene asistencia. Requiere una corrección autorizada.');
            $this->check($duration === $this->minutes($original->$start->format('H:i'),$original->$end->format('H:i')),
                'horafin', 'Conserve la duración de la clase original.');
            if (in_array($data['tipo'],['adelanto','extra'],true)) {
                $this->check((int)$original->estado_id === (int)estado('INDEFINIDO') && $original->activo
                    && Carbon::parse($original->fecha->format('Y-m-d').' '.$original->$start->format('H:i'))
                        ->gt(Carbon::parse($data['fecha'].' '.$data['horainicio'])),
                    'original_id', 'El adelanto o clase extra debe consumir una clase pendiente futura.');
            } elseif ($data['tipo'] === 'recuperacion') {
                $originalEnd=Carbon::parse($original->fecha->format('Y-m-d').' '.$original->$end->format('H:i'));
                $this->check($originalEnd->lte(Carbon::parse($data['fecha'].' '.$data['horainicio']))
                    && in_array((int)$original->estado_id, $this->absenceIds(),true) && $originalEnd->lte(now()),
                    'original_id', 'Solo se recuperan clases pasadas con licencia, falta u otra ausencia.');
            }
            $this->check((bool)$original->habilitado, 'original_id', 'La clase original aún no está habilitada por sus pagos.');
            $this->conflicts($person,$data,[],[$canal=>[$original->id]]);
            $program = new $programClass;
            $program->forceFill(['fecha'=>$data['fecha'], $start=>$data['horainicio'], $end=>$data['horafin'],
                'horas_por_clase'=>$duration/60, 'docente_id'=>$data['docente_id'], 'aula_id'=>$data['aula_id'],
                $enrollmentKey=>$enrollmentId, 'habilitado'=>1, 'activo'=>1, 'estado_id'=>estado('INDEFINIDO')]);
            if ($canal === 'apoyo') { $program->materia_id = $data['materia_id']; }
            $program->save();
            ClaseAdicional::create([$programKey=>$program->id, 'original_'.$programKey=>$original->id,
                'tipo'=>$data['tipo'], 'solicitud'=>$data['solicitud'], 'creado_por'=>$actor->id, 'usuario_nombre'=>$actor->name, 'motivo'=>$data['motivo'],
                'tema_id'=>$canal==='apoyo'?($data['tema_id']??null):null]);
            $this->event($program,$actor,'adicional_creada',null,array_merge($program->getAttributes(),['tema_id'=>$data['tema_id']??null]),$data['motivo']);
            if (!empty($data['marcar_entrada'])) { $this->record($program,$data,$actor); }
            return $program->fresh();
        }, 3);
    }

    public function hasAttendance(Model $program): bool
    {
        $relation = $program instanceof Programacion ? 'clases' : 'clasescom';
        return in_array((int)$program->estado_id, array_map('intval',[estado('PRESENTE'),estado('FINALIZADO')]),true)
            || ($program->relationLoaded($relation) ? $program->$relation->isNotEmpty() : $program->$relation()->exists());
    }

    public function record(Model $program, array $input, User $actor): Model
    {
        $canal=$this->canal($program);
        $this->authorize($canal,$actor);
        if ($canal==='apoyo' && !array_key_exists('tema_id',$input)) { $input['tema_id']=optional($program->adicional)->tema_id; }
        $data=$this->validateData($input);
        [$programClass, $enrollmentClass, $attendanceClass, , $key, $start, $end] = $this->definition($canal);
        return DB::transaction(function () use ($program,$data,$actor,$canal,$programClass,$enrollmentClass,$attendanceClass,$key,$start,$end) {
            $enrollment=$this->enrollment($program);
            $person=$this->personId($enrollment);
            DB::table('personas')->where('id',$person)->lockForUpdate()->first();
            $enrollment=$enrollmentClass::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            $program=$programClass::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $this->active($enrollment);
            $this->check(!$program->compensacion, 'programacion', 'Esta clase ya fue consumida por una clase adicional. Marque la clase efectiva.');
            $this->check($program->activo && in_array((int)$program->estado_id,array_map('intval',[estado('INDEFINIDO'),estado('PRESENTE'),estado('FINALIZADO')]),true),
                'programacion','El estado actual no permite marcar entrada. Utilice una recuperación si corresponde.');
            $this->check($data['fecha'] === $program->fecha->format('Y-m-d') && $data['fecha'] <= now()->toDateString(),
                'fecha','Marque asistencia en la fecha programada; para cambiarla utilice una clase adicional.');
            $existing=$attendanceClass::where($key,$program->id)->orderBy('id')->first();
            if ($existing) { return $existing; } // Idempotent even for two tabs / repeated clicks.
            $this->check(!$this->hasAttendance($program),'programacion','Esta clase ya tiene asistencia registrada.');
            $this->references($enrollment,$data,$canal);
            $this->check($this->minutes($data['horainicio'],$data['horafin']) <= $this->minutes($program->$start->format('H:i'),$program->$end->format('H:i')),
                'horafin','La duración no puede superar la de la clase programada.');
            $this->check((bool)$program->habilitado,'pago','La clase no está habilitada por sus pagos. Registre primero el pago correspondiente.');
            DB::table('docentes')->where('id',$data['docente_id'])->lockForUpdate()->first();
            $this->conflicts($person,$data,[$canal=>[$program->id]]);
            $attendance=new $attendanceClass;
            $attendance->forceFill([$key=>$program->id,'fecha'=>$data['fecha'],'horainicio'=>$data['horainicio'],
                'horafin'=>$data['horafin'],'docente_id'=>$data['docente_id'],'aula_id'=>$data['aula_id'],
                'estado_id'=>estado($data['fecha'] < now()->toDateString() ? 'FINALIZADO' : 'PRESENTE')]);
            if ($canal==='apoyo') { $attendance->materia_id=$data['materia_id']; $attendance->tema_id=$data['tema_id']??null; }
            $attendance->save();
            $attendance->usuarios()->syncWithoutDetaching([$actor->id]);
            $before=['programacion'=>$program->getAttributes()];
            $program->estado_id=$attendance->estado_id;
            $program->docente_id=$attendance->docente_id;
            $program->aula_id=$attendance->aula_id;
            if($canal==='apoyo') $program->materia_id=$attendance->materia_id;
            $program->save();
            $this->event($program,$actor,'entrada',$before,$attendance->getAttributes(),$data['motivo']??null);
            return $attendance;
        },3);
    }

    public function canFinish(Model $attendance, User $actor): bool
    {
        $d=$this->definition($attendance instanceof Clase?'apoyo':'computacion');
        $own=$actor->hasRole('Docente') && $actor->persona && $actor->persona->docente
            && (int)$actor->persona->docente->id === (int)$attendance->docente_id;
        return $actor->can('Editar '.$d[7]) && ($actor->hasAnyRole(['Admin','Secretaria']) || $own);
    }

    public function finish(Model $attendance, User $actor): Model
    {
        abort_unless($this->canFinish($attendance,$actor),403);
        $program=$attendance instanceof Clase ? $attendance->programacion : $attendance->programacioncom;
        return DB::transaction(function() use ($attendance,$program,$actor) {
            DB::table('personas')->where('id',$this->personId($this->enrollment($program)))->lockForUpdate()->first();
            $program=$program->newQuery()->whereKey($program->id)->lockForUpdate()->firstOrFail();
            $attendance=$attendance->newQuery()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            if ((int)$attendance->estado_id === (int)estado('FINALIZADO')) { return $attendance; }
            $this->check((int)$attendance->estado_id === (int)estado('PRESENTE'),'estado','La clase no está en curso.');
            $this->check($attendance->fecha->format('Y-m-d') === now()->toDateString() && now()->format('H:i:s') > $attendance->horainicio->format('H:i:s'),
                'horafin','La salida debe ser posterior a la entrada y corresponder al día de la clase.');
            $before=$attendance->getAttributes();
            $attendance->estado_id=estado('FINALIZADO');
            $attendance->horafin=now()->format('H:i:s');
            $attendance->save();
            $program->estado_id=estado('FINALIZADO'); $program->save();
            $this->event($program,$actor,'salida',$before,$attendance->getAttributes());
            return $attendance;
        },3);
    }

    public function correct(Model $program, array $input, User $actor): Model
    {
        $canal=$this->canal($program); $this->authorize($canal,$actor,'Editar',true);
        // Corrections preserve the original, commercial duration and link; audit every change.
        $data=$this->validateData($input);
        $this->check(!empty($data['motivo']) && mb_strlen($data['motivo'])>=5,'motivo','Explique el motivo de la corrección.');
        [$pc,$ec,$ac,,$key,$start,$end]=$this->definition($canal);
        return DB::transaction(function() use($program,$data,$actor,$canal,$pc,$ec,$ac,$key,$start,$end) {
            $enrollment=$this->enrollment($program); $person=$this->personId($enrollment);
            DB::table('personas')->where('id',$person)->lockForUpdate()->first();
            $ec::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            $program=$pc::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $this->check((bool)$program->adicional,'programacion','Esta corrección corresponde únicamente a clases adicionales.');
            $this->check(!$program->compensacion,'programacion','Esta clase ya fue compensada; corrija la clase efectiva y conserve el original.');
            $attendance=$ac::where($key,$program->id)->lockForUpdate()->first();
            if ($canal==='apoyo' && !array_key_exists('tema_id',$data)) { $data['tema_id']=$program->adicional->tema_id ?? optional($attendance)->tema_id; }
            $this->references($enrollment,$data,$canal);
            $this->check($this->minutes($data['horainicio'],$data['horafin']) === $this->minutes($program->$start->format('H:i'),$program->$end->format('H:i')),
                'horafin','Conserve la duración contratada al corregir.');
            $original=$program->adicional->original;
            $type=$program->adicional->tipo;
            if($original && in_array($program->adicional->tipo,['adelanto','extra'],true)) {
                $effectiveDate=Carbon::parse($data['fecha'].' '.$data['horainicio']);
                $originalStart=Carbon::parse($original->fecha->format('Y-m-d').' '.$original->$start->format('H:i'));
                if(!$effectiveDate->lt($originalStart)) {
                    $originalEnd=Carbon::parse($original->fecha->format('Y-m-d').' '.$original->$end->format('H:i'));
                    $this->check(!$attendance && $originalEnd->lte(now()) && $effectiveDate->gte($originalEnd),
                        'fecha','El adelanto debe conservar una fecha anterior a la original. Si no se asistió y la fecha original ya pasó, puede reprogramarse como recuperación.');
                    $type='recuperacion';
                }
            }
            if($original && $program->adicional->tipo==='recuperacion') {
                $this->check($data['fecha'] >= $original->fecha->format('Y-m-d'),'fecha','La recuperación no puede preceder a la clase original.');
            }
            if($attendance) { $this->check($data['fecha']<=now()->toDateString(),'fecha','Una asistencia realizada no puede trasladarse al futuro.'); }
            DB::table('docentes')->where('id',$data['docente_id'])->lockForUpdate()->first();
            $this->conflicts($person,$data,[$canal=>[$program->id]],$original?[$canal=>[$original->id]]:[]);
            $before=['programacion'=>$program->getAttributes(),'asistencia'=>$attendance?$attendance->getAttributes():null,'adicional'=>$program->adicional->getAttributes()];
            $program->forceFill(['fecha'=>$data['fecha'],$start=>$data['horainicio'],$end=>$data['horafin'],
                'docente_id'=>$data['docente_id'],'aula_id'=>$data['aula_id']]);
            if(!$attendance && $program->isDirty('fecha')) { $program->estado_id=estado('INDEFINIDO'); }
            if($canal==='apoyo')$program->materia_id=$data['materia_id'];
            $program->save();
            if($type!==$program->adicional->tipo) { $program->adicional->tipo=$type;$program->adicional->save(); }
            if($canal==='apoyo') { $program->adicional->tema_id=$data['tema_id']??null;$program->adicional->save(); }
            if($attendance) {
                $attendance->forceFill(array_intersect_key($data,array_flip(['fecha','horainicio','horafin','docente_id','aula_id'])));
                if($canal==='apoyo') { $attendance->materia_id=$data['materia_id']; $attendance->tema_id=$data['tema_id']??null; }
                $attendance->save();
            }
            $this->event($program,$actor,'correccion',$before,['programacion'=>$program->getAttributes(),'asistencia'=>$attendance?$attendance->getAttributes():null,'adicional'=>$program->adicional->getAttributes()],$data['motivo']);
            return $program;
        },3);
    }

    private function conflicts(int $person, array $data, array $exclude = [], array $originals = []): void
    {
        // Global availability, including other branches; the error never reveals their records.
        foreach (['apoyo','computacion'] as $canal) {
            [$pc,,$ac,$ek,$pk,$start,$end]=$this->definition($canal);
            $studentQuery=$canal==='apoyo'
                ? DB::table('inscripciones')->join('estudiantes','estudiantes.id','=','inscripciones.estudiante_id')->where('estudiantes.persona_id',$person)->select('inscripciones.id')
                : DB::table('matriculacions')->join('computacions','computacions.id','=','matriculacions.computacion_id')->where('computacions.persona_id',$person)->select('matriculacions.id');
            $ids=array_merge($exclude[$canal]??[],$originals[$canal]??[]);
            $table=(new $pc)->getTable();
            $schedule=DB::table($table)->whereDate('fecha',$data['fecha'])->where('activo',1)
                ->whereIn('estado_id',[estado('INDEFINIDO'),estado('PRESENTE')])
                ->whereNotExists(fn($q)=>$q->selectRaw('1')->from('clases_adicionales')->whereColumn('original_'.$pk,$table.'.id'))
                ->whereNotIn('id',$ids)->whereRaw('TIME('.$start.') < ?',[$data['horafin'].':00'])->whereRaw('TIME('.$end.') > ?',[$data['horainicio'].':00']);
            $this->check(!$this->incompatibleTeaching(clone $schedule,$data,$table,$table,$pk,$canal==='apoyo'?'inscripciones':'matriculacions',$ek)->exists(),
                'docente_id', 'El docente tiene otra clase en un aula o Sucursal incompatible con ese horario.');
            $this->check(!(clone $schedule)->whereIn($ek,$studentQuery)->exists(), 'horainicio', 'El estudiante tiene otra clase en ese horario.');
            $actualTable=(new $ac)->getTable();
            $actual=DB::table($actualTable)->whereDate('fecha',$data['fecha'])->whereNotIn($pk,$ids)
                ->whereIn('estado_id',[estado('PRESENTE'),estado('FINALIZADO')])
                ->whereRaw('TIME(horainicio) < ?',[$data['horafin'].':00'])->whereRaw('TIME(horafin) > ?',[$data['horainicio'].':00']);
            $this->check(!$this->incompatibleTeaching(clone $actual,$data,$actualTable,$table,$pk,$canal==='apoyo'?'inscripciones':'matriculacions',$ek,true)->exists(),
                'docente_id','El docente tiene una asistencia en un aula o Sucursal incompatible con ese horario.');
            $this->check(!(clone $actual)->whereIn($pk,DB::table((new $pc)->getTable())->whereIn($ek,$studentQuery)->select('id'))->exists(),
                'horainicio','El estudiante tiene una asistencia que coincide con ese horario.');
        }
    }

    private function incompatibleTeaching($query,array $data,string $table,string $programTable,string $programKey,string $enrollmentTable,string $enrollmentKey,bool $actual=false)
    {
        // A shared teaching slot can contain N students, including different subjects.
        // Each enrollment keeps its own attendance and billing; no extra Sesion is created.
        $branch=app(SedeAccess::class)->active();
        return $query->where('docente_id',$data['docente_id'])->where(function($conflict)use($data,$table,$programTable,$programKey,$enrollmentTable,$enrollmentKey,$actual,$branch){
            $conflict->where('aula_id','!=',$data['aula_id'])->orWhereNull('aula_id');
            if ($branch===null) { return; }
            $conflict->orWhereExists(function($records)use($table,$programTable,$programKey,$enrollmentTable,$enrollmentKey,$actual,$branch){
                $records->selectRaw('1')->from('sede_registros as teaching_branch')->where('teaching_branch.sede_id','!=',$branch)
                    ->where(function($linked)use($table,$programTable,$programKey,$enrollmentTable,$enrollmentKey,$actual){
                        $linked->where(fn($q)=>$q->where('teaching_branch.tabla',$table)->whereColumn('teaching_branch.registro_id',$table.'.id'))
                            ->orWhere(fn($q)=>$q->where('teaching_branch.tabla','aulas')->whereColumn('teaching_branch.registro_id',$table.'.aula_id'));
                        if ($actual) {
                            $linked->orWhere(fn($q)=>$q->where('teaching_branch.tabla',$programTable)->whereColumn('teaching_branch.registro_id',$table.'.'.$programKey))
                                ->orWhere(fn($q)=>$q->where('teaching_branch.tabla',$enrollmentTable)->whereIn('teaching_branch.registro_id',function($parent)use($table,$programTable,$programKey,$enrollmentKey){
                                    $parent->select($programTable.'.'.$enrollmentKey)->from($programTable)->whereColumn($programTable.'.id',$table.'.'.$programKey);
                                }));
                        } else {
                            $linked->orWhere(fn($q)=>$q->where('teaching_branch.tabla',$enrollmentTable)->whereColumn('teaching_branch.registro_id',$table.'.'.$enrollmentKey));
                        }
                    });
            });
        });
    }

    public function event(Model $program, User $actor, string $action, ?array $before, array $after, ?string $reason = null): void
    {
        ClaseAsistenciaEvento::create([$program->attendanceKey()=>$program->id,'usuario_id'=>$actor->id,'usuario_nombre'=>$actor->name,
            'accion'=>$action,'antes'=>$before,'despues'=>$after,'motivo'=>$reason]);
    }

    public function protectHistory(Model $program): void
    {
        $this->check(!$program->adicional && !$program->compensacion, 'programacion',
            'Esta clase pertenece al historial de clases adicionales. Utilice «Corregir» en ese historial.');
    }

    public function protectRegeneration(string $canal, int $id): void
    {
        [$pc,,,$ek]=$this->definition($canal);
        $this->check(!$pc::where($ek,$id)->where(function($q){$q->whereHas('adicional')->orWhereHas('compensacion');})->exists(),
            'programacion','Esta inscripción tiene clases adicionales vinculadas. No se puede regenerar ni eliminar su programación; conserve su historial.');
    }

    public function syncCompensationPayments(string $canal, int $id): void
    {
        [$pc,,,$ek]=$this->definition($canal);
        foreach($pc::with('adicional.originalProgramacion','adicional.originalProgramacioncom')->where($ek,$id)
            ->whereHas('adicional',fn($q)=>$q->conOriginal())->get() as $program) {
            $program->habilitado=(bool)optional($program->adicional->original)->habilitado;
            $program->save();
        }
    }

    public function resolvedAbsenceIds(string $table)
    {
        $key=['programacions'=>'programacion_id','programacioncoms'=>'programacioncom_id'][$table]??null;
        abort_unless($key,422);
        return DB::table('clases_adicionales')->select('original_'.$key)->whereNotNull('original_'.$key)
            ->whereIn($key,DB::table($table)->select('id')->whereIn('estado_id',[estado('PRESENTE'),estado('FINALIZADO')]));
    }
}
