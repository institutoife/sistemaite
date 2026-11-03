@php $prefix='add-'.$tab['canal'].'-'.$tab['enrollment']->id; $absenceIds=app(\App\Services\ClassAttendanceService::class)->absenceIds(); @endphp
<details class="border rounded p-3 mb-3 attendance-add">
    <summary class="font-weight-bold" style="color:#375F7A;cursor:pointer"><i class="fas fa-plus-circle mr-1"></i> Agregar clase / Adelantar clase</summary>
    <form class="attendance-form attendance-add-form mt-3" action="{{ route('asistencia.adicional.store',[$tab['canal'],$tab['enrollment']->id]) }}" method="POST">
        @csrf
        <input type="hidden" name="solicitud" value="{{ (string)\Illuminate\Support\Str::uuid() }}">
        <div class="row">
            <div class="form-group col-md-4">
                <label for="{{ $prefix }}-tipo">Tipo de clase</label>
                <select required class="form-control" name="tipo" id="{{ $prefix }}-tipo"><option value="" selected>Elija tipo de clase</option><option value="recuperacion">Recuperación</option><option value="adelanto">Adelanto</option><option value="extra">Clase extra</option></select>
            </div>
            <div class="form-group col-md-8 attendance-original-group">
                <label for="{{ $prefix }}-original">Clase original</label>
                <input type="search" class="form-control mb-1 attendance-search" placeholder="Buscar por fecha, materia o docente" aria-label="Buscar clase original">
                <select class="form-control attendance-original" name="original_id" id="{{ $prefix }}-original" required>
                    <option value="">Seleccione la clase a compensar</option>
                    @foreach($tab['programaciones'] as $program)
                        @if($program->isContractedClass() && !$program->compensacion && !app(\App\Services\ClassAttendanceService::class)->hasAttendance($program))
                            @php
                                $absent=\Carbon\Carbon::parse($program->fecha->format('Y-m-d').' '.$program->{$tab['end']}->format('H:i'))->lte(now()) && in_array((int)$program->estado_id,$absenceIds,true);
                                $advance=(int)$program->estado_id===(int)estado('INDEFINIDO') && $program->activo;
                            @endphp
                            @if($absent || $advance)
                                <option value="{{ $program->id }}" data-recovery="{{ $absent?1:0 }}" data-advance="{{ $advance?1:0 }}"
                                    data-original-start="{{ $program->fecha->format('Y-m-d').'T'.$program->{$tab['start']}->format('H:i') }}" data-original-end="{{ $program->fecha->format('Y-m-d').'T'.$program->{$tab['end']}->format('H:i') }}"
                                    data-docente="{{ $program->docente_id }}" data-materia="{{ $program->materia_id }}" data-aula="{{ $program->aula_id }}"
                                    data-minutes="{{ $program->{$tab['start']}->diffInMinutes($program->{$tab['end']}) }}">
                                    #{{ $program->id }} · {{ $program->fecha->format('d/m/Y') }} {{ $program->{$tab['start']}->format('H:i') }} · {{ $tab['canal']==='apoyo'?optional($program->materia)->materia:optional($tab['enrollment']->asignatura)->asignatura }} · {{ optional($program->docente)->nombrecorto }} · {{ optional($program->estado)->estado }}{{ $program->habilitado?'':' · Pago pendiente' }}
                                </option>
                            @endif
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="{{ $prefix }}-duracion">Duración (minutos)</label>
            <input id="{{ $prefix }}-duracion" class="form-control attendance-duration" type="number" min="1" max="1439" step="1" readonly aria-describedby="{{ $prefix }}-duracion-ayuda">
            <small id="{{ $prefix }}-duracion-ayuda" class="form-text text-muted">Se conserva la duración de la clase programada que elija. Puede ajustar la hora de inicio; la finalización se calcula automáticamente.</small>
        </div>
        @include('clase.asistencia.fields',['defaults'=>['fecha'=>now('America/La_Paz')->toDateString(),'horainicio'=>now('America/La_Paz')->format('H:i'),'horafin'=>'']])
        <p class="small text-muted attendance-extra-note d-none">Seleccione la clase programada y pagada que desea consumir. La clase extra utiliza esa misma clase, conserva su duración y no descuenta horas por segunda vez.</p>
        <div class="form-group"><label for="{{ $prefix }}-motivo">Motivo / observaciones</label><textarea required minlength="5" maxlength="2000" class="form-control" id="{{ $prefix }}-motivo" name="motivo" rows="2"></textarea></div>
        <div class="custom-control custom-checkbox mb-3"><input class="custom-control-input" id="{{ $prefix }}-entrada" type="checkbox" name="marcar_entrada" value="1" checked><label class="custom-control-label" for="{{ $prefix }}-entrada">Marcar entrada al guardar (solo hoy o fechas pasadas)</label></div>
        <div class="attendance-errors alert alert-danger d-none" role="alert"></div>
        <button class="btn btn-primary" type="submit"><i class="fas fa-save mr-1"></i> Guardar clase</button>
    </form>
</details>
