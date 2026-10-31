<?php
namespace App\Services;
use App\Models\{Prospecto,Persona,CrmEtapa};
use Illuminate\Support\Facades\{DB,Cache};
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
class CrmConversionService
{
    public function convert(Prospecto $record, array $data, int $actor): void
    {
        // Same lock and lock order as Google: no in-flight contact CREATE can race with this handoff.
        $done=Cache::lock('ife:gcontact:central',180)->get(function () use ($record,$data,$actor) {
            DB::transaction(function () use ($record,$data,$actor) {
                $crm=app(CrmService::class); $current=Prospecto::whereKey($record->id)->lockForUpdate()->firstOrFail();
                $sameEnrollment=$data['tipo']==='inscripcion'?(int)$current->inscripcione_id===(int)$data['inscripcion_id']:(int)$current->matriculacion_id===(int)$data['inscripcion_id'];
                if ($current->convertido_at&&$sameEnrollment&&(int)$current->persona_id===(int)$data['persona_id']&&(int)$current->estudiante_persona_id===(int)$data['estudiante_persona_id']) { return; }
                if ((int)$current->crm_version!==(int)$data['version']) { throw new ConflictHttpException('Otro usuario modificó esta ficha. Actualice la página.'); }
                if ($current->crm_status!=='activo'||$current->convertido_at) { throw ValidationException::withMessages(['conversion'=>'La oportunidad debe estar activa y pendiente de conversión.']); }
                $contact=Persona::whereKey($data['persona_id'])->lockForUpdate()->firstOrFail();
                if ($current->persona_id&&(int)$current->persona_id!==$contact->id) { throw ValidationException::withMessages(['persona_id'=>'Conserve la identidad de la Persona ya vinculada.']); }
                $student=Persona::findOrFail($data['estudiante_persona_id']);
                $academic=$data['tipo']==='inscripcion';
                $enrollment=DB::table($academic?'inscripciones':'matriculacions')
                    ->join($academic?'estudiantes':'computacions',''.($academic?'estudiantes':'computacions').'.id','=',($academic?'inscripciones.estudiante_id':'matriculacions.computacion_id'))
                    ->where(($academic?'inscripciones':'matriculacions').'.id',$data['inscripcion_id'])
                    ->where(($academic?'estudiantes':'computacions').'.persona_id',$student->id)->exists();
                if (!$enrollment) { throw ValidationException::withMessages(['inscripcion_id'=>'La inscripción o matrícula no pertenece al estudiante seleccionado. Complete primero la inscripción real.']); }
                if ($contact->id!==$student->id&&!$student->apoderados()->where('personas.id',$contact->id)->exists()) {
                    if (empty($data['parentesco'])) { throw ValidationException::withMessages(['parentesco'=>'Indique el parentesco del contacto para vincularlo como apoderado del estudiante.']); }
                    $student->apoderados()->attach($contact->id,['parentesco'=>$data['parentesco'],'telefono'=>$contact->telefono]);
                }
                if ($contact->resourseName&&$current->resourseName&&$contact->resourseName!==$current->resourseName) {
                    throw ValidationException::withMessages(['conversion'=>'Hay dos enlaces Google distintos. Requiere revisión; se conservan ambos sin fusionarlos.']);
                }
                if ($contact->gcontact_account_id&&$current->gcontact_account_id&&(int)$contact->gcontact_account_id!==(int)$current->gcontact_account_id) {
                    throw ValidationException::withMessages(['conversion'=>'Los contactos pertenecen a cuentas Google diferentes.']);
                }
                $stage=CrmEtapa::where('conversion',true)->where('activa',true)->when(!empty($data['etapa_id']),function ($query) use ($data) { $query->whereKey($data['etapa_id']); })->orderBy('orden')->first();
                if (!$stage) { throw ValidationException::withMessages(['conversion'=>'Configure una etapa activa de conversión.']); }
                $current->persona_id=$contact->id; $current->estudiante_persona_id=$student->id;
                $current->inscripcione_id=$academic?$data['inscripcion_id']:null; $current->matriculacion_id=$academic?null:$data['inscripcion_id'];
                $current->convertido_at=now(); $current->crm_status='convertido'; $current->crm_etapa_id=$stage->id;
                $current->convertido_por=$actor; $current->responsable_conversion_id=$current->responsable_id;
                $current->etapa_desde=now(); $current->proximo_contacto_at=null; $current->crm_version++;
                // Suppress the generic observer; explicitly hand off to Persona after commit.
                $current->gcontact_sync_pending=false;
                Prospecto::withoutEvents(function () use ($current) { $current->save(); });
                if ($current->no_contactar) { DB::table('personas')->where('id',$contact->id)->update(['votos'=>0]); }
                $crm->log($current,$actor,'conversion','Conversión vinculada a '.$data['tipo'].' #'.$data['inscripcion_id'],
                    ['datos'=>['persona_id'=>$contact->id,'estudiante_persona_id'=>$student->id,'tipo'=>$data['tipo'],'inscripcion_id'=>(int)$data['inscripcion_id']]]);
                app(GoogleContactsService::class)->markPending($contact,$actor);
            });
            return true;
        });
        if (!$done) { throw new ConflictHttpException('Google está procesando un contacto. Espere a que termine e intente nuevamente la conversión.'); }
    }
}
