@php
    $defaults = $defaults ?? [];
    $prefix = $prefix ?? 'asistencia';
    $selectedRoom=$tab['aulas']->firstWhere('id',$defaults['aula_id']??null);
    $presenceBranch=optional($selectedRoom)->attendance_sede_id ?? app(\App\Services\SedeAccess::class)->active();
@endphp
<div class="row">
    @foreach(['fecha'=>'Fecha efectiva','horainicio'=>'Hora de inicio','horafin'=>'Hora de finalización'] as $field=>$label)
        <div class="form-group col-md-4">
            <label for="{{ $prefix }}-{{ $field }}">{{ $label }}</label>
            <input required class="form-control" id="{{ $prefix }}-{{ $field }}" name="{{ $field }}"
                type="{{ $field==='fecha'?'date':'time' }}" value="{{ $defaults[$field] ?? ($field==='fecha'?now()->toDateString():'') }}">
        </div>
    @endforeach
    <div class="form-group col-md-4">
        <label for="{{ $prefix }}-docente">Docente</label>
        <select required class="form-control attendance-teacher" id="{{ $prefix }}-docente" name="docente_id">
            <option value="">Seleccione docente</option>
            @foreach($tab['docentes'] as $teacher)
                @php($teacherName=$teacher->nombrecorto ?: trim(optional($teacher->persona)->nombre.' '.optional($teacher->persona)->apellidop))
                <option value="{{ $teacher->id }}" data-name="{{ $teacherName }}" @if(($defaults['docente_id']??null)==$teacher->id) selected @endif>{{ \App\Services\TeacherPresence::label($teacherName,$tab['teacherPresence'][$teacher->id]??[],$presenceBranch===null?null:(int)$presenceBranch) }}</option>
            @endforeach
        </select>

    </div>
    <div class="form-group col-md-4">
        <label for="{{ $prefix }}-materia">Materia / curso</label>
        @if($tab['canal']==='apoyo')
            <select required class="form-control" id="{{ $prefix }}-materia" name="materia_id">
                <option value="">Seleccione materia</option>
                @foreach($tab['materias'] as $subject)
                    <option value="{{ $subject->id }}" @if(($defaults['materia_id']??null)==$subject->id) selected @endif>{{ $subject->materia }}</option>
                @endforeach
            </select>
        @else
            <input class="form-control" id="{{ $prefix }}-materia" value="{{ optional($tab['enrollment']->asignatura)->asignatura }}" readonly>
        @endif
    </div>
    <div class="form-group col-md-4">
        <label for="{{ $prefix }}-aula">Aula</label>
        <select required class="form-control" id="{{ $prefix }}-aula" name="aula_id">
            <option value="">Seleccione aula</option>
            @foreach($tab['aulas'] as $room)
                <option value="{{ $room->id }}" data-sede="{{ $room->attendance_sede_id }}" @if(($defaults['aula_id']??null)==$room->id) selected @endif>{{ $room->aula }}</option>
            @endforeach
        </select>
    </div>
    @if($tab['canal']==='apoyo')
        <div class="form-group col-md-12">
            <label for="{{ $prefix }}-tema">Tema (opcional)</label>
            <select class="form-control attendance-topic" name="tema_id" id="{{ $prefix }}-tema" data-topics-url="{{ route('asistencia.temas',$tab['enrollment']->id) }}">
                <option value="">Sin tema específico</option>
                @if(!empty($defaults['tema_id']))<option selected value="{{ $defaults['tema_id'] }}">{{ $defaults['tema_label']??('Tema #'.$defaults['tema_id']) }}</option>@endif
            </select>
        </div>
    @endif
</div>
