@php
    $enrollment=$tab['enrollment'];
    $todayPrograms=$tab['todayPrograms'];
@endphp
<div class="attendance-panel" data-attendance-panel data-today="{{ now('America/La_Paz')->toDateString() }}" data-server-now="{{ now('America/La_Paz')->toIso8601String() }}" data-refresh-url="{{ $refreshUrl??request()->url() }}" data-teachers-url="{{ route('asistencia.docentes') }}" data-teacher-presence="{{ json_encode($tab['teacherPresence'],JSON_UNESCAPED_UNICODE) }}" data-active-branch="{{ app(\App\Services\SedeAccess::class)->active() }}">
    @include('clase.asistencia.summary')
    @if(empty($reportOnly))

        @forelse($todayPrograms as $program)
            @php
                $attendances=$program->{$tab['canal']==='apoyo'?'clases':'clasescom'};
                $inProgress=$attendances->firstWhere('estado_id',estado('PRESENTE'));
                $duration=$program->{$tab['start']}->diffInMinutes($program->{$tab['end']});
            @endphp
            <div class="card attendance-entry-card mb-3">
                <div class="card-header attendance-entry-header">
                    <h6 class="mb-1 font-weight-bold">Registro de entrada</h6>
                    <div class="d-flex flex-wrap align-items-center attendance-entry-meta"><strong>Programación #{{ $program->id }}</strong><span>Horario inscrito: {{ $program->{$tab['start']}->format('H:i') }}–{{ $program->{$tab['end']}->format('H:i') }}</span><span class="attendance-program-status">{{ (int)$program->estado_id===(int)estado('INDEFINIDO')?'PROGRAMADA':optional($program->estado)->estado }}</span></div>
                    @include('clase.asistencia.relation')
                </div>
                <div class="card-body">
                    @if($inProgress)
                        @if(app(\App\Services\ClassAttendanceService::class)->canFinish($inProgress,auth()->user()))
                            <button type="button" class="btn btn-sm btn-danger attendance-exit" data-url="{{ route($tab['canal']==='apoyo'?'clases.finalizar':'clasecom.finalizar') }}" data-id="{{ $inProgress->id }}">Marcar salida</button>
                        @else
                            <span class="badge badge-info">Clase en curso</span>
                        @endif
                    @elseif($attendances->isNotEmpty() || (int)$program->estado_id===(int)estado('FINALIZADO'))
                        <span class="badge badge-success">Asistencia registrada</span>
                    @elseif(!$program->habilitado)
                        <span class="text-warning">Registre primero el pago para habilitar esta clase.</span>
                    @elseif((int)$program->estado_id===(int)estado('INDEFINIDO') && $program->activo)
                        @can('Crear '.$tab['permission'])
                                <form class="attendance-form attendance-entry-form" data-duration="{{ $duration }}" method="POST" action="{{ route($tab['canal']==='apoyo'?'clases.guardar':'clasescom.guardar',$program->id) }}">
                                    @csrf
                                    @include('clase.asistencia.fields',['prefix'=>'entry-'.$tab['canal'].'-'.$program->id,'defaults'=>['fecha'=>$program->fecha->format('Y-m-d'),'horainicio'=>now('America/La_Paz')->format('H:i'),'horafin'=>now('America/La_Paz')->addMinutes($duration)->format('H:i'),'docente_id'=>$program->docente_id,'materia_id'=>$program->materia_id,'aula_id'=>$program->aula_id,'tema_id'=>optional($program->adicional)->tema_id,'tema_label'=>optional(optional($program->adicional)->tema)->tema]])

                                    <div class="attendance-errors alert alert-danger d-none" role="alert"></div>
                                    <div class="attendance-entry-actions"><button class="btn btn-ife-entry" type="submit">Confirmar entrada</button><button type="button" class="btn btn-outline-secondary attendance-scheduled" data-start="{{ $program->{$tab['start']}->format('H:i') }}" data-end="{{ $program->{$tab['end']}->format('H:i') }}">Usar horario programado</button></div>
                                </form>
                        @endcan
                    @else
                        <span class="small text-muted">Conserva su estado histórico; utilice una recuperación cuando corresponda.</span>
                    @endif
                </div>
            </div>
        @empty
            @if($tab['compensatedToday']->isNotEmpty())
                <div class="alert alert-info">La clase programada para hoy ya fue vinculada a una clase adicional. Consulte la clase efectiva y su historial.
                    @foreach($tab['compensatedToday'] as $program)<div class="mt-1">Original #{{ $program->id }}: @include('clase.asistencia.relation')</div>@endforeach
                </div>
            @else
                <div class="alert alert-info">Sin clase hoy en esta {{ $tab['canal']==='apoyo'?'inscripción':'matrícula' }} #{{ $enrollment->id }}. Revise también las otras pestañas. Puede adelantar, recuperar o agregar una clase dentro de esta inscripción o matrícula.</div>
            @endif
        @endforelse
        @if($tab['canAdd']) @include('clase.asistencia.add') @endif
    @endif
    @include('clase.asistencia.history')
    @if(empty($reportOnly))@can('Listar '.$tab['permission'])<a class="btn btn-sm btn-link mt-2" href="{{ route('asistencia.historial',[$tab['canal'],$enrollment->id]) }}">Ver reporte de asistencia e historial</a>@endcan @endif
</div>
