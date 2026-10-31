<?php
namespace App\Services;

use App\Models\{Prospecto,Persona,Estado,CrmEtapa,CrmActividadDetalle,Observacion,Interest,Como,CrmSede,Grado,User};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CrmService
{
    public const TYPES=['llamada'=>'Llamada realizada','no_contestada'=>'Llamada no contestada','whatsapp'=>'Mensaje de WhatsApp',
        'informacion'=>'Información enviada','visita'=>'Visita presencial','cotizacion'=>'Cotización enviada',
        'programado'=>'Seguimiento programado','observacion'=>'Observación'];
    public const REASONS=['sin_interes'=>'No está interesado','precio'=>'Precio','sin_respuesta'=>'No responde','otra_institucion'=>'Eligió otra institución',
        'sin_necesidad'=>'No necesita el servicio','numero_incorrecto'=>'Número incorrecto','duplicado'=>'Duplicado','no_mensajes'=>'Solicitó no recibir mensajes','otro'=>'Otro'];
    public const CONTACT_TYPES=['llamada','no_contestada','whatsapp','informacion','visita','cotizacion'];
    public const RELATIONS=['etapa','sede','interests','como','grado','responsable','persona','estudiantePersona','creador','responsableInicial','convertidoPor','responsableConversion'];
    public function ready(): bool { return Schema::hasTable('interest_prospecto')&&Schema::hasColumn('prospectos','creado_por'); }
    public function catalogs(): array
    {
        return ['etapas'=>CrmEtapa::orderBy('orden')->orderBy('id')->get(),'sedes'=>CrmSede::orderBy('nombre')->get(),
            'interests'=>Interest::orderBy('interest')->get(),'comos'=>Como::orderBy('como')->get(),
            'grados'=>Grado::orderBy('grado')->get(),'responsables'=>$this->responsibleUsers()->orderBy('name')->get(['id','name'])];
    }
    public function responsibleUsers()
    {
        return User::where(function ($query) {
            $query->role('Admin')->orWhere(function ($allowed) { $allowed->permission(['Crear Prospectos','Editar Prospectos']); });
        });
    }
    public function initial(): CrmEtapa
    {
        $stage=CrmEtapa::whereNotNull('inicial')->where('activa',true)->where('conversion',false)->first();
        if (!$stage) { throw ValidationException::withMessages(['crm'=>'Configure una etapa inicial activa en el CRM.']); }
        return $stage;
    }
    public function prepareLegacy(?int $personId=null, ?int $actor=null): int
    {
        if ($personId&&!$actor) { return 0; }
        $initial=$this->initial();
        $states=Estado::pluck('estado','id');
        $stageMap=['ESPERANUEVO'=>'nuevo','PROSPECTO'=>'nuevo','SEGUIMIENTO'=>'seguimiento','ESPERAREINSCRIPCION'=>'inscripcion',
            'ESPERAREMATRICULACION'=>'inscripcion','ESPERARETOMA'=>'seguimiento','CANCELADO'=>'seguimiento','PERDIDO'=>'seguimiento','PERDID'=>'seguimiento'];
        $stages=CrmEtapa::pluck('id','codigo');
        if (!$personId) { DB::table('prospectos')->whereNull('crm_etapa_id')->orderBy('id')->chunkById(200,function ($rows) use ($initial,$states,$stageMap,$stages) {
            foreach ($rows as $row) {
                $state=$states[$row->estado_id]??'';
                DB::table('prospectos')->where('id',$row->id)->whereNull('crm_etapa_id')->update([
                    'crm_etapa_id'=>$stages[$stageMap[$state]??'nuevo']??$initial->id,'etapa_desde'=>now(),
                    'crm_status'=>in_array($state,['CANCELADO','PERDIDO','PERDID'],true)?'baja':'activo',
                    'telefono_normalizado'=>app(TelefonoService::class)->normalize($row->telefono)]);
            }
        }); }
        $count=0;
        Persona::with('interests')->whereIn('habilitado',$states->filter(function ($name) use ($stageMap) { return isset($stageMap[$name]); })->keys())
            ->when($personId,function ($query) use ($personId) { $query->whereKey($personId); })
            ->whereNotExists(function ($query) { $query->selectRaw('1')->from('prospectos')->whereColumn('prospectos.persona_id','personas.id'); })
            ->orderBy('id')->chunkById(100,function ($people) use (&$count,$initial,$stages,$states,$stageMap,$actor,$personId) {
                foreach ($people as $person) {
                    $state=$states[$person->habilitado]??'';
                    $interest=$person->interests->first();
                    $rawDate=trim((string)$person->getRawOriginal('vuelvefecha'));
                    $next=preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$rawDate,$parts)&&checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1])?$rawDate.' 00:00:00':null;
                    $record=new Prospecto;
                    $record->forceFill(['telefono'=>(string)$person->telefono,'telefono_normalizado'=>app(TelefonoService::class)->normalize($person->telefono),
                        'nombre'=>trim($person->nombre.' '.$person->apellidop.' '.$person->apellidom),'estado_id'=>$person->habilitado,
                        'persona_id'=>$person->id,'legacy_persona_id'=>$person->id,'interest_id'=>$interest->id??null,'como_id'=>$person->como_id,
                        'no_contactar'=>$person->votos!==null&&(int)$person->votos===0,'crm_status'=>in_array($state,['CANCELADO','PERDIDO','PERDID'],true)?'baja':'activo',
                        'crm_etapa_id'=>$stages[$stageMap[$state]??'nuevo']??$initial->id,'etapa_desde'=>now(),'proximo_contacto_at'=>$next,
                        'responsable_id'=>$personId?$actor:null,'creado_por'=>$personId?$actor:null,'responsable_inicial_id'=>$personId?$actor:null,
                        'crm_services_migrated_at'=>now(),
                        // Import date is the CRM registration date; historical Persona dates remain in the linked record.
                        'observacion'=>'Potencial incorporado desde Persona #'.$person->id]);
                    Prospecto::withoutEvents(function () use ($record) { $record->save(); });
                    $record->interests()->sync($person->interests->modelKeys());
                    if ($personId&&$actor) { $this->log($record,$actor,'alta','Potencial incorporado desde Persona #'.$person->id); }
                    $count++;
                }
            });
        return $count;
    }
    public function duplicates(string $phone, ?int $except=null): array
    {
        $normal=app(TelefonoService::class)->normalize($phone);
        if (!$normal) { return []; }
        $digits=preg_replace('/\D/','',$normal); $local=substr($digits,-8);
        $matches=[];
        foreach (['prospecto'=>Prospecto::class,'persona'=>Persona::class] as $kind=>$class) {
            $query=$class::query()->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono,' ',''),'+',''),'-',''),'(',''),')',''),'.','') IN (?, ?, ?)",[$local,$digits,'00'.$digits]);
            if ($kind==='prospecto'&&$except) { $query->where('id','<>',$except); }
            foreach ($query->limit(30)->get() as $row) {
                if (app(TelefonoService::class)->normalize($row->telefono)!==$normal) { continue; }
                if ($kind==='prospecto'&&!$row->responsable_id&&!(auth()->user()&&auth()->user()->can('Configurar CRM'))) {
                    $matches[]=['id'=>null,'tipo'=>'prospecto','nombre'=>'Registro histórico sin responsable; consulte al administrador.','url'=>null]; continue;
                }
                $matches[]=['id'=>$row->id,'tipo'=>$kind,'nombre'=>$kind==='persona'?trim($row->nombre.' '.$row->apellidop.' '.$row->apellidom):$row->identificacion,
                    'url'=>route($kind==='persona'?'personas.show':'prospecto.view',$row->id)];
            }
        }
        return $matches;
    }
    public function query(array $filters, bool $active=true)
    {
        $query=Prospecto::query();
        $assignment=$filters['asignacion']??'asignados';
        if ($assignment!=='asignados') { abort_unless(auth()->user()&&auth()->user()->can('Configurar CRM'),403); }
        if ($assignment==='sin_responsable') { $query->whereNull('responsable_id'); }
        elseif ($assignment!=='todos') { $query->whereNotNull('responsable_id'); }
        foreach (['crm_sede_id','responsable_id','como_id','crm_etapa_id'] as $field) { if (!empty($filters[$field])) { $query->where($field,$filters[$field]); } }
        if (!empty($filters['interest_id'])) { $query->whereHas('interests',function ($where) use ($filters) { $where->where('interests.id',$filters['interest_id']); }); }
        if (!empty($filters['q'])) { $q=$filters['q']; $query->where(function ($where) use ($q) { $where->where('nombre','like','%'.$q.'%')->orWhere('telefono','like','%'.$q.'%')->orWhere('estudiante_nombre','like','%'.$q.'%'); }); }
        if (($filters['origen_tipo']??'')==='potencial') { $query->whereNotNull('legacy_persona_id'); }
        if (($filters['origen_tipo']??'')==='telefonico') { $query->whereNull('legacy_persona_id'); }
        if ($active) {
            ($filters['status']??'activo')==='operativo'?$query->whereIn('crm_status',['activo','convertido']):$query->where('crm_status',$filters['status']??'activo');
        }
        if ($active&&!empty($filters['desde'])) { $query->whereDate('created_at','>=',$filters['desde']); }
        if ($active&&!empty($filters['hasta'])) { $query->whereDate('created_at','<=',$filters['hasta']); }
        switch ($filters['agenda']??'') {
            case 'hoy': $query->whereDate('proximo_contacto_at',today()); break;
            case 'vencidos': $query->where('proximo_contacto_at','<',now()); break;
            case 'sin_seguimiento': $query->whereNull('proximo_contacto_at'); break;
            case 'sin_respuesta': $query->where('ultimo_resultado','sin_respuesta'); break;
            case 'nuevos': $query->whereNull('contactado_at'); break;
        }
        return $query;
    }
    public function board(array $filters): array
    {
        $filters+=['status'=>'operativo'];
        $stages=CrmEtapa::orderBy('orden')->orderBy('id')->get(); $ids=[];
        $counts=$this->query($filters)->selectRaw('crm_etapa_id, COUNT(*) total')->groupBy('crm_etapa_id')->pluck('total','crm_etapa_id');
        foreach ($stages as $stage) {
            $ids=array_merge($ids,$this->query($filters)->where('crm_etapa_id',$stage->id)->orderByRaw('CASE WHEN proximo_contacto_at IS NULL THEN 1 ELSE 0 END')->orderBy('proximo_contacto_at')->orderByDesc('id')->limit(50)->pluck('id')->all());
        }
        $cards=Prospecto::with(self::RELATIONS)->whereIn('id',$ids)->orderBy('proximo_contacto_at')->orderByDesc('id')->get()->groupBy('crm_etapa_id');
        return compact('stages','counts','cards');
    }
    public function lock(Prospecto $record, int $version): Prospecto
    {
        $current=Prospecto::whereKey($record->id)->lockForUpdate()->firstOrFail();
        if ((int)$current->crm_version!==$version) { throw new ConflictHttpException('Otro usuario modificó esta ficha. Actualice la página y vuelva a intentarlo.'); }
        return $current;
    }
    public function log(Prospecto $record, int $actor, string $type, string $note, array $data=[]): void
    {
        $observation=$record->observaciones()->create(['observacion'=>mb_substr($note,0,400),'activo'=>1]);
        $observation->usuarios()->attach($actor);
        CrmActividadDetalle::create(['observacion_id'=>$observation->id,'tipo'=>$type,'ocurrio_at'=>$data['ocurrio_at']??now(),'registrado_at'=>now(),
            'actor_id'=>$actor,'responsable_id'=>$actor,'responsable_comercial_id'=>$record->responsable_id,'crm_sede_id'=>$record->crm_sede_id,
            'asignacion_comercial'=>in_array($type,['alta','reasignacion'],true)&&!$record->convertido_at,
            'servicios_ids'=>$record->interests()->orderBy('interests.id')->pluck('interests.id')->map(function ($id) { return (int)$id; })->all(),
            'resultado'=>$data['resultado']??null,
            'proxima_accion'=>$data['proxima_accion']??null,'proximo_contacto_at'=>$data['proximo_contacto_at']??null,'datos'=>$data['datos']??null]);
    }
    public function save(?Prospecto $record, array $data, int $actor): Prospecto
    {
        return DB::transaction(function () use ($record,$data,$actor) {
            $current=$record?$this->lock($record,(int)$data['version']):new Prospecto;
            $previousNote=$current->observacion;
            if ($record&&$current->persona_id&&((array_key_exists('nombre',$data)&&($data['nombre']??'')!==($current->nombre??''))
                ||(array_key_exists('telefono',$data)&&app(TelefonoService::class)->normalize($data['telefono'])!==app(TelefonoService::class)->normalize($current->telefono)))) {
                throw ValidationException::withMessages(['crm'=>'Actualice el nombre y teléfono desde la Persona vinculada.']);
            }
            if ($record&&$current->crm_status==='convertido') { throw ValidationException::withMessages(['crm'=>'La ficha convertida conserva su historial. Actualice los datos personales desde Persona.']); }
            foreach (['nombre','telefono','como_id','grado_id','crm_sede_id','estudiante_nombre','observacion','proximo_contacto_at'] as $field) {
                if (array_key_exists($field,$data)) { $current->$field=$data[$field]; }
            }
            $normal=app(TelefonoService::class)->normalize($current->telefono);
            $current->telefono_normalizado=$normal;
            if ($normal) { $current->telefono=strpos($normal,'+591')===0?substr($normal,4):$normal; }
            if (!$record) {
                $current->crm_etapa_id=$this->initial()->id; $current->etapa_desde=now();
                $current->responsable_id=$actor; $current->creado_por=$actor; $current->responsable_inicial_id=$actor;
                $current->estado_id=$data['estado_id']??Estado::where('estado','PROSPECTO')->value('id');
                if (!$current->estado_id) { throw ValidationException::withMessages(['crm'=>'No se encontró el estado histórico PROSPECTO. Revise el catálogo de Estados.']); }
            }
            $services=array_key_exists('interests',$data)?array_values(array_unique(array_map('intval',$data['interests']))):null;
            if ($services!==null) { $current->interest_id=$services[0]??null; }
            $current->crm_services_migrated_at=now();
            if (!$current->crm_etapa_id) { $current->crm_etapa_id=$this->initial()->id; $current->etapa_desde=now(); }
            $current->crm_version=(int)$current->crm_version+1; $current->save();
            if ($services!==null) { $current->interests()->sync($services); }
            $current->usuarios()->attach($actor);
            $this->log($current,$actor,$record?'edicion':'alta',($record?'Datos comerciales actualizados.':'Prospecto registrado.').' '.($current->observacion??''),
                ['datos'=>['campos'=>array_keys($data),'observacion_anterior'=>$previousNote,'observacion_actual'=>$current->observacion]]);
            return $current;
        });
    }
    public function move(Prospecto $record, int $stageId, int $version, int $actor): Prospecto
    {
        return DB::transaction(function () use ($record,$stageId,$version,$actor) {
            $current=$this->lock($record,$version); $stage=CrmEtapa::whereKey($stageId)->lockForUpdate()->firstOrFail();
            if ($current->crm_status!=='activo'||!$stage->activa||$stage->conversion) { throw ValidationException::withMessages(['etapa'=>'Seleccione una etapa activa. Para convertir, vincule una inscripción real desde el detalle.']); }
            if ((int)$current->crm_etapa_id===$stageId) { return $current; }
            $old=$current->crm_etapa_id; $current->crm_etapa_id=$stageId; $current->etapa_desde=now(); $current->crm_version++; $current->save();
            $this->log($current,$actor,'etapa','Etapa cambiada a '.$stage->nombre,['datos'=>['anterior'=>$old,'nueva'=>$stageId]]);
            return $current;
        });
    }
    public function activity(Prospecto $record, array $data, int $actor): void
    {
        DB::transaction(function () use ($record,$data,$actor) {
            $current=$this->lock($record,(int)$data['version']);
            if ($current->crm_status==='convertido'&&$data['tipo']!=='observacion') { throw ValidationException::withMessages(['crm'=>'Registre las gestiones del cliente en su ficha personal.']); }
            if (!$this->mayContact($current)&&in_array($data['tipo'],['llamada','no_contestada','whatsapp','informacion','cotizacion'],true)) { throw ValidationException::withMessages(['crm'=>'Esta ficha tiene restringido el contacto comercial. Registre una visita u observación interna.']); }
            if (!empty($data['cerrar_seguimiento'])) { $current->proximo_contacto_at=null; }
            if (!empty($data['proximo_contacto_at'])) { $current->proximo_contacto_at=$data['proximo_contacto_at']; }
            $data['ocurrio_at']=now();
            if (in_array($data['tipo'],self::CONTACT_TYPES,true)) { $current->contactado_at=$current->contactado_at?:now(); $current->ultimo_seguimiento_at=now(); }
            $current->ultimo_resultado=$data['tipo']==='no_contestada'?'sin_respuesta':($data['resultado']??null);
            $current->crm_version++; $current->save();
            $this->log($current,$actor,$data['tipo'],$data['observacion']??self::TYPES[$data['tipo']],$data);
        });
    }
    public function reassign(Prospecto $record, array $data, int $actor): void
    {
        DB::transaction(function () use ($record,$data,$actor) {
            $current=$this->lock($record,(int)$data['version']); $owner=(int)$data['responsable_id'];
            if ((int)$current->responsable_id===$owner) { return; }
            $previous=$current->responsable_id; $current->responsable_id=$owner; $current->crm_version++; $current->save();
            $names=User::whereIn('id',array_filter([$previous,$owner]))->pluck('name','id');
            $this->log($current,$actor,'reasignacion','Responsable comercial: '.($names[$previous]??'Sin asignar').' → '.($names[$owner]??'Usuario #'.$owner).'. '.$data['observacion'],
                ['datos'=>['anterior'=>$previous,'nuevo'=>$owner]]);
        });
    }
    public function deactivate(Prospecto $record, array $data, int $actor): void
    {
        DB::transaction(function () use ($record,$data,$actor) {
            $current=$this->lock($record,(int)$data['version']);
            $current->crm_status='baja'; $current->baja_at=now(); $current->baja_por=$actor; $current->motivo_baja=$data['motivo']; $current->proximo_contacto_at=null;
            if ($data['motivo']==='no_mensajes') {
                $current->no_contactar=true;
                if ($current->persona_id) { DB::table('personas')->where('id',$current->persona_id)->update(['votos'=>0]); }
            }
            $current->crm_version++; $current->save();
            $this->log($current,$actor,'baja',self::REASONS[$data['motivo']].'. '.($data['observacion']??''), ['datos'=>['motivo'=>$data['motivo']]]);
        });
    }
    public function reactivate(Prospecto $record, array $data, int $actor): void
    {
        DB::transaction(function () use ($record,$data,$actor) {
            $current=$this->lock($record,(int)$data['version']);
            if ($current->crm_status!=='baja'||$current->convertido_at) { throw ValidationException::withMessages(['crm'=>'Solo puede reactivar una baja que no tenga una conversión registrada.']); }
            if (!empty($data['autoriza_contacto'])) {
                $current->no_contactar=false;
                if ($current->persona_id&&!Prospecto::where('persona_id',$current->persona_id)->where('id','<>',$current->id)->where('no_contactar',true)->exists()) { DB::table('personas')->where('id',$current->persona_id)->update(['votos'=>1]); }
            }
            $current->crm_status='activo'; $current->crm_etapa_id=$this->initial()->id; $current->etapa_desde=now();
            $current->baja_at=null; $current->baja_por=null; $current->motivo_baja=null; $current->crm_version++; $current->save();
            $this->log($current,$actor,'reactivacion','Prospecto reactivado. '.(!empty($data['autoriza_contacto'])?'El operador confirmó una nueva autorización de contacto.':'Se conserva la preferencia de contacto.'),['datos'=>['autoriza_contacto'=>!empty($data['autoriza_contacto'])]]);
        });
    }
    public function mayContact(Prospecto $record): bool
    {
        return $record->crm_status==='activo'&&!$record->no_contactar&&(!$record->persona_id||!$record->persona||$record->persona->votos===null||(int)$record->persona->votos===1);
    }
    public function linkNewContact(Prospecto $record, Persona $person, int $version, int $actor): void
    {
        $current=$this->lock($record,$version);
        if ($current->persona_id||$current->crm_status!=='activo') { throw ValidationException::withMessages(['crm'=>'La oportunidad ya tiene Persona vinculada o está cerrada. Revise su ficha.']); }
        $current->persona_id=$person->id; $current->nombre=trim($person->nombre.' '.$person->apellidop.' '.$person->apellidom);
        $current->telefono=(string)$person->telefono; $current->telefono_normalizado=app(TelefonoService::class)->normalize($person->telefono);
        $current->crm_version++; $current->gcontact_sync_pending=false;
        Prospecto::withoutEvents(function () use ($current) { $current->save(); });
        if ($current->no_contactar) { DB::table('personas')->where('id',$person->id)->update(['votos'=>0]); }
        $this->log($current,$actor,'vinculacion','Se registró la Persona #'.$person->id.' como contacto de esta oportunidad.');
    }
    public function whatsapp(Prospecto $record, string $kind): array
    {
        if (!$this->mayContact($record)) { throw ValidationException::withMessages(['whatsapp'=>'Contacto comercial restringido para esta ficha.']); }
        $phone=app(TelefonoService::class)->normalize($record->telefono);
        if (!$phone) { throw ValidationException::withMessages(['whatsapp'=>'Complete un teléfono válido antes de contactar.']); }
        $intro=$record->nombre?'Hola, '.$record->nombre.'. ':'Hola. ';
        $names=$record->interests->pluck('interest')->implode(', ');
        $service=$names!==''?' sobre '.$names:'';
        $texts=['primer_contacto'=>'Le saludamos del Instituto IFE. ¿Podemos ayudarle con su consulta'.$service.'?',
            'informacion'=>'Le escribimos del Instituto IFE para atender su solicitud de información'.$service.'. ¿Qué información necesita?',
            'seguimiento'=>'Le saludamos del Instituto IFE. ¿Pudo revisar la información'.$service.'? Quedamos atentos a sus consultas.',
            'inscripcion'=>'Le saludamos del Instituto IFE. ¿Desea que le ayudemos a continuar con su inscripción'.$service.'?',
            'reactivacion'=>'Le saludamos del Instituto IFE. ¿Todavía necesita orientación'.$service.'?'];
        $text=$intro.$texts[$kind];
        return ['mensaje'=>$text,'url'=>'https://wa.me/'.ltrim($phone,'+').'?text='.rawurlencode($text)];
    }
}
