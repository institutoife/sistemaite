@php
    $enrollment=$tab['enrollment'];
    $todayPrograms=$tab['programaciones']->filter(fn($p)=>$p->fecha->isToday() && !$p->compensacion);
@endphp
<div class="attendance-panel" data-attendance-panel data-today="{{ now('America/La_Paz')->toDateString() }}" data-server-now="{{ now('America/La_Paz')->toIso8601String() }}" data-refresh-url="{{ $refreshUrl??request()->url() }}">
    <div class="mb-2"><strong>{{ $tab['canal']==='apoyo'?'Inscripción':'Matrícula' }} #{{ $enrollment->id }}</strong> · {{ $tab['canal']==='apoyo'?optional($enrollment->modalidad)->modalidad:optional($enrollment->asignatura)->asignatura }}</div>
    @if($tab['canal']==='apoyo')<p class="small">{{ strip_tags($enrollment->objetivo) }}</p>@endif
    <div class="d-flex flex-wrap mb-3" aria-label="Estadísticas de asistencia">
        @foreach($tab['stats'] as $label=>$count)<div class="border rounded py-1 px-2 mr-2 mb-1"><strong style="color:#375F7A">{{ $count }}</strong> <small>{{ $label }}</small></div>@endforeach
    </div>
    @can('Reporte Pagos')<p class="small">Pagado: Bs {{ number_format($tab['totalPagado'],2) }} · Saldo: Bs {{ number_format(max(0,$enrollment->costo-$tab['totalPagado']),2) }}</p>@endcan
    @if(empty($reportOnly))
        <h6 class="font-weight-bold">Clases de hoy</h6>
        @forelse($todayPrograms as $program)
            @php
                $attendances=$program->{$tab['canal']==='apoyo'?'clases':'clasescom'};
                $inProgress=$attendances->firstWhere('estado_id',estado('PRESENTE'));
                $duration=$program->{$tab['start']}->diffInMinutes($program->{$tab['end']});
            @endphp
            <div class="card card-outline mb-3" style="border-top-color:#26BAA5">
                <div class="card-header p-2"><strong>#{{ $program->id }} · {{ $program->{$tab['start']}->format('H:i') }}–{{ $program->{$tab['end']}->format('H:i') }}</strong> · {{ optional($program->estado)->estado }} @include('clase.asistencia.relation')</div>
                <div class="card-body p-2">
                    <p class="small mb-2">{{ $tab['canal']==='apoyo'?optional($program->materia)->materia:optional($enrollment->asignatura)->asignatura }} · {{ optional($program->docente)->nombrecorto }} · {{ optional($program->aula)->aula }}</p>
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
                            <button type="button" class="btn btn-sm btn-primary attendance-quick-entry mb-2"><i class="fas fa-bolt mr-1"></i> Entrada rápida</button>
                            <details><summary class="text-primary" style="cursor:pointer">Revisar horario y marcar entrada</summary>
                                <form class="attendance-form mt-2" method="POST" action="{{ route($tab['canal']==='apoyo'?'clases.guardar':'clasescom.guardar',$program->id) }}">
                                    @csrf
                                    @include('clase.asistencia.fields',['prefix'=>'entry-'.$tab['canal'].'-'.$program->id,'defaults'=>['fecha'=>$program->fecha->format('Y-m-d'),'horainicio'=>now()->format('H:i'),'horafin'=>now()->addMinutes($duration)->format('H:i'),'docente_id'=>$program->docente_id,'materia_id'=>$program->materia_id,'aula_id'=>$program->aula_id,'tema_id'=>optional($program->adicional)->tema_id,'tema_label'=>optional(optional($program->adicional)->tema)->tema]])
                                    <button type="button" class="btn btn-sm btn-outline-secondary attendance-scheduled mb-2" data-start="{{ $program->{$tab['start']}->format('H:i') }}" data-end="{{ $program->{$tab['end']}->format('H:i') }}">Usar horario programado</button>
                                    <div class="attendance-errors alert alert-danger d-none" role="alert"></div>
                                    <button class="btn btn-primary btn-sm" type="submit">Confirmar entrada</button>
                                </form>
                            </details>
                        @endcan
                    @else
                        <span class="small text-muted">Conserva su estado histórico; utilice una recuperación cuando corresponda.</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-info">Sin clase hoy. Puede adelantar, recuperar o agregar una clase dentro de esta inscripción o matrícula.</div>
        @endforelse
        @if($tab['canAdd']) @include('clase.asistencia.add') @endif
    @endif
    @include('clase.asistencia.history')
    @if(empty($reportOnly))@can('Listar '.$tab['permission'])<a class="btn btn-sm btn-link mt-2" href="{{ route('asistencia.historial',[$tab['canal'],$enrollment->id]) }}">Ver reporte de asistencia e historial</a>@endcan @endif
</div>
