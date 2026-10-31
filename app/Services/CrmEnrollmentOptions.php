<?php
namespace App\Services;

use App\Models\{Persona,Prospecto,Inscripcione,Matriculacion};
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class CrmEnrollmentOptions
{
    public function forLead(Prospecto $lead, ?int $contactId, ?int $studentId, bool $showPayments=false): array
    {
        $contactId=(int)($contactId?:$lead->persona_id);
        if (!$contactId) { return ['enrollments'=>[],'students'=>[]]; }
        if ($lead->persona_id&&(int)$lead->persona_id!==$contactId) { throw ValidationException::withMessages(['persona_id'=>'Conserve el contacto ya vinculado a esta oportunidad.']); }
        Persona::findOrFail($contactId);
        $ids=$studentId?[$studentId]:array_unique(array_merge([$contactId],DB::table('persona_persona')->where('persona_id_apoderado',$contactId)->pluck('persona_id')->all(),array_filter([$lead->estudiante_persona_id])));
        $enrollments=[]; $students=[];
        foreach (['inscripcion'=>['inscripciones','estudiantes','estudiante_id','modalidads','modalidad_id','modalidad',Inscripcione::class],
            'matriculacion'=>['matriculacions','computacions','computacion_id','asignaturas','asignatura_id','asignatura',Matriculacion::class]] as $type=>$config) {
            [$table,$roles,$roleKey,$catalog,$catalogKey,$label,$model]=$config;
            $records=DB::table($table.' as e')->join($roles.' as r','r.id','=','e.'.$roleKey)->join('personas as p','p.id','=','r.persona_id')
                ->leftJoin($catalog.' as c','c.id','=','e.'.$catalogKey)->whereIn('r.persona_id',$ids)->orderByDesc('e.id')->limit(50)
                ->get(['e.id','e.created_at','r.persona_id','p.nombre','p.apellidop','p.apellidom','c.'.$label.' as servicio']);
            $paid=collect();
            if ($showPayments&&$records->isNotEmpty()&&Schema::hasTable('pagos')) {
                $paid=DB::table('pagos')->where('pagable_type',$model)->whereIn('pagable_id',$records->pluck('id'))
                    ->groupBy('pagable_id')->selectRaw('pagable_id, SUM(monto) as pagado')->pluck('pagado','pagable_id');
            }
            foreach ($records as $record) {
                $name=trim($record->nombre.' '.$record->apellidop.' '.$record->apellidom);
                $date=substr((string)$record->created_at,0,10);
                if (!preg_match('/^([1-9]\d{3})-(\d{2})-(\d{2})$/',$date,$parts)||!checkdate((int)$parts[2],(int)$parts[3],(int)$parts[1])) { $date='Sin fecha anterior'; }
                $students[$record->persona_id]=['id'=>(int)$record->persona_id,'nombre'=>$name];
                $enrollments[]=['id'=>(int)$record->id,'tipo'=>$type,'estudiante_persona_id'=>(int)$record->persona_id,
                    'label'=>($type==='inscripcion'?'Inscripción':'Matrícula').' #'.$record->id.' · '.$name.' · '.($record->servicio?:'Servicio registrado').' · '.$date,
                    'tiene_pago'=>$showPayments?(float)($paid[$record->id]??0)>0:null];
            }
        }
        return ['enrollments'=>$enrollments,'students'=>array_values($students)];
    }
}
