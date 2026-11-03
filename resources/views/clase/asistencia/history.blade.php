<details class="attendance-history" {{ !empty($historyOpen)?'open':'' }}>
    <summary class="font-weight-bold mb-2" style="cursor:pointer;color:#375F7A">Programación e historial de clases ({{ $tab['programaciones']->count() }})</summary>
    <div class="table-responsive" style="max-height:32rem;overflow:auto">
        <table class="table table-sm table-striped table-bordered mb-2">
            <thead><tr><th>Clase / fecha</th><th>Horario</th><th>Materia / docente / aula</th><th>Estado e historial</th></tr></thead>
            <tbody>
                @forelse($tab['programaciones'] as $program)
                    @php
                        $recordedAttendance=$program->{$tab['canal']==='apoyo'?'clases':'clasescom'}->first();
                        $selectedTopic=optional($program->adicional)->tema ?? ($tab['canal']==='apoyo'?optional($recordedAttendance)->tema:null);
                    @endphp
                    <tr><td>#{{ $program->id }} · {{ $program->fecha->format('d/m/Y') }} @include('clase.asistencia.relation')</td>
                        <td>{{ $program->{$tab['start']}->format('H:i') }}–{{ $program->{$tab['end']}->format('H:i') }}</td>
                        <td>{{ $tab['canal']==='apoyo'?optional($program->materia)->materia:optional($tab['enrollment']->asignatura)->asignatura }}<br>{{ optional($program->docente)->nombrecorto }} · {{ optional($program->aula)->aula }}</td>
                        <td>{{ optional($program->estado)->estado }}{{ $program->habilitado?'':' · Pago pendiente' }}
                            @if($program->adicional)
                                <small class="d-block">Registró {{ $program->adicional->usuario_nombre }} · {{ $program->adicional->created_at->format('d/m/Y H:i') }}<br>{{ $program->adicional->motivo }}</small>
                                @if($selectedTopic)<small class="d-block">Tema: {{ $selectedTopic->tema }}</small>@endif
                            @endif
                            @foreach($program->{$tab['canal']==='apoyo'?'clases':'clasescom'} as $attendance)
                                <small class="d-block">Asistencia #{{ $attendance->id }}: {{ $attendance->fecha->format('d/m/Y') }} {{ $attendance->horainicio->format('H:i') }}–{{ $attendance->horafin->format('H:i') }} · {{ optional($attendance->docente)->nombrecorto }} · {{ $tab['canal']==='apoyo'?optional($attendance->materia)->materia:optional($tab['enrollment']->asignatura)->asignatura }} · {{ optional($attendance->aula)->aula }} · Registró: {{ $attendance->usuarios->pluck('name')->join(', ') }}</small>
                            @endforeach
                            @if($program->eventosAsistencia->isNotEmpty())
                                <details class="mt-1"><summary>Auditoría ({{ $program->eventosAsistencia->count() }})</summary>
                                    @foreach($program->eventosAsistencia as $event)
                                        <p class="small mb-1">{{ $event->created_at->format('d/m/Y H:i:s') }} · {{ $event->usuario_nombre }} · {{ $event->accion }}<br>{{ $event->motivo }}
                                            @if($event->accion==='salida' && !empty($event->despues['salida_registrada_at']))
                                                <br><strong>Salida real: {{ \Carbon\Carbon::parse($event->despues['salida_registrada_at'])->timezone('America/La_Paz')->format('d/m/Y H:i:s') }}</strong>
                                            @endif
                                            @if($event->antes)<details><summary>Valores anteriores y nuevos</summary><pre class="small text-wrap">{{ json_encode(['antes'=>$event->antes,'después'=>$event->despues],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>@endif
                                        </p>
                                    @endforeach
                                </details>
                            @endif
                            @if($program->adicional && $tab['canCorrect'])
                                <details class="mt-2"><summary>Corregir</summary>
                                    <form class="attendance-form mt-2" method="POST" action="{{ route('asistencia.adicional.correct',[$tab['canal'],$program->id]) }}">
                                        @csrf @method('PATCH')
                                        @if($program->adicional->consume_original && in_array($program->adicional->tipo,['adelanto','extra'],true))<p class="small text-muted">Si no hubo asistencia y la fecha original ya pasó, al reprogramar después de ella se registrará como recuperación, conservando el vínculo y la auditoría.</p>@endif
                                        @include('clase.asistencia.fields',['prefix'=>'correct-'.$tab['canal'].'-'.$program->id,'defaults'=>['fecha'=>$program->fecha->format('Y-m-d'),'horainicio'=>$program->{$tab['start']}->format('H:i'),'horafin'=>$program->{$tab['end']}->format('H:i'),'docente_id'=>$program->docente_id,'materia_id'=>$program->materia_id,'aula_id'=>$program->aula_id,'tema_id'=>optional($selectedTopic)->id,'tema_label'=>optional($selectedTopic)->tema]])
                                        <label>Motivo de corrección<textarea name="motivo" required minlength="5" maxlength="2000" class="form-control"></textarea></label>
                                        <div class="attendance-errors alert alert-danger d-none" role="alert"></div>
                                        <button type="submit" class="btn btn-sm btn-primary">Guardar corrección</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No hay clases programadas en esta inscripción o matrícula.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>
