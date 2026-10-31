@extends('adminlte::page')

@section('title', 'Clases Actuales por Docente')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)

@section('css')
    <style>
        .schedule-item {
            border-radius: .75rem;
            border: 1px solid #d9dee5;
            overflow: hidden;
            margin-bottom: 1rem;
            transition: all .2s ease-in-out;
            box-shadow: 0 2px 8px rgba(16, 24, 40, .06);
        }
        .schedule-item .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, .06);
            background: #f8fafc;
            cursor: pointer;
            transition: background .2s ease-in-out, color .2s ease-in-out, border-color .2s ease-in-out;
        }
        .schedule-item .student-photo {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid rgba(0,0,0,.08);
        }
        .schedule-item .compact-requirement {
            font-size: .9rem;
            line-height: 1.3;
            max-height: 2.6em;
            overflow: hidden;
        }
        .schedule-item .status-pill {
            font-size: .78rem;
            border-radius: 999px;
            padding: .25rem .6rem;
            font-weight: 700;
            letter-spacing: .01em;
        }
        .state-ok {
            background: #dcfce7;
            border-color: #bbf7d0;
        }
        .schedule-item.state-ok .card-header {
            background: #bbf7d0;
            border-bottom-color: #86efac;
        }
        .state-ok .status-pill {
            background: #15803d;
            color: #fff;
        }
        .state-warning {
            background: #fff7ed;
            border-color: #fed7aa;
        }
        .schedule-item.state-warning .card-header {
            background: #fed7aa;
            border-bottom-color: #fdba74;
        }
        .state-warning .status-pill {
            background: #c2410c;
            color: #fff;
        }
        .state-finished {
            background: #fee2e2;
            border-color: #fecaca;
        }
        .schedule-item.state-finished .card-header {
            background: #fecaca;
            border-bottom-color: #fca5a5;
        }
        .state-finished .status-pill {
            background: #dc2626;
            color: #fff;
        }
        .state-overdue {
            background: #7f1d1d;
            border-color: #7f1d1d;
            color: #fff;
            font-weight: 700;
        }
        .state-overdue a,
        .state-overdue .text-muted,
        .state-overdue .text-secondary {
            color: #fff !important;
        }
        .schedule-item.state-overdue .card-header {
            background: #7f1d1d;
            border-bottom-color: #7f1d1d;
            color: #fff;
        }
        .schedule-item.state-overdue .card-header .btn-link {
            color: #fff !important;
        }
        .state-overdue .status-pill {
            background: #ffffff;
            color: #7f1d1d;
        }
        .badge-info {
            background-color: rgb(55,95,122) !important;
            border-color: rgb(55,95,122) !important;
            color: #fff !important;
        }
        .text-info {
            color: rgb(55,95,122) !important;
        }
        .metric-card {
            border-radius: .75rem;
            border: 1px solid #e5e7eb;
        }
    </style>
@stop

@section('content_header')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <div>
            <h1 class="mb-1">Clases actuales por docente</h1>
            <p class="text-muted mb-0">Vista operativa del {{ \Carbon\Carbon::parse($hoy)->isoFormat('dddd D [de] MMMM [de] YYYY') }}</p>
        </div>
    </div>
@stop

@section('content')
    <div class="content pt-3">
        @if($esSupervisor)
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <strong>Supervisi&oacute;n por docente</strong>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('misestudiantes') }}" class="row align-items-end">
                        <div class="col-md-8 col-lg-6">
                            <label for="docente_id">Docente</label>
                            <select class="form-control" id="docente_id" name="docente_id" required>
                                @foreach($docentes as $docente)
                                    <option value="{{ $docente->id }}" {{ (int)$docenteSeleccionadoId === (int)$docente->id ? 'selected' : '' }}>
                                        {{ $docente->nombrecorto }} - {{ optional($docente->persona)->nombre }} {{ optional($docente->persona)->apellidop }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3 mt-3 mt-md-0">
                            <button class="btn btn-primary btn-block" type="submit">
                                Ver horario actual
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="text-muted">Total asignados hoy</div>
                        <h3 class="mb-0" id="metric-total">{{ $totalAsignados }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="text-muted">En horario ahora</div>
                        <h3 class="mb-0 text-success" id="metric-current">{{ $cantidadEnHorario }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-2">
                <div class="card metric-card">
                    <div class="card-body">
                        <div class="text-muted">Excedidos</div>
                        <h3 class="mb-0 text-danger" id="metric-overdue">0</h3>
                    </div>
                </div>
            </div>
        </div>

        @if($clases->isEmpty() && $clasescom->isEmpty())
            <div class="alert alert-info">
                No hay estudiantes presentes para este docente en la fecha actual.
            </div>
        @else
            <div id="accordion-programaciones">
                @foreach ($clases as $index => $clase)
                    @php
                        $programa = $clase->programacion;
                        $observacionesPrograma = optional($programa)->observaciones ?? collect();
                        $persona = optional(optional(optional($programa)->inscripcione)->estudiante)->persona;
                        $horaInicio = optional($clase->horainicio)->format('H:i');
                        $horaFin = optional($clase->horafin)->format('H:i');
                        $requerimiento = strip_tags(optional($observacionesPrograma->first())->observacion ?? '');
                        $objetivoGeneral = strip_tags(optional(optional($programa)->inscripcione)->objetivo ?? '');
                        $foto = $persona && $persona->foto ? app(\App\Services\ProfilePhotoService::class)->url($persona->foto) : app(\App\Services\ProfilePhotoService::class)->placeholder();
                    @endphp
                    <div class="card schedule-item"
                         id="clase-{{ $clase->id }}"
                         data-hora-inicio="{{ $horaInicio }}"
                         data-hora-fin="{{ $horaFin }}">
                        <div class="card-header" id="heading-clase-{{ $clase->id }}">
                            <button class="btn btn-link text-left text-dark w-100 p-0 d-flex align-items-center justify-content-between"
                                    data-toggle="collapse"
                                    data-target="#collapse-clase-{{ $clase->id }}"
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}"
                                    aria-controls="collapse-clase-{{ $clase->id }}">
                                <div class="d-flex align-items-center">
                                    <div class="mr-3">
                                        <img data-profile-photo src="{{ $foto }}" alt="Foto de {{ $persona->nombre ?? 'estudiante' }}" class="student-photo">
                                    </div>
                                    <div>
                                        <div class="font-weight-bold">
                                            #{{ $index + 1 }} - {{ $persona->nombre ?? '-' }} {{ $persona->apellidop ?? '' }} {{ $persona->apellidom ?? '' }}
                                            <span class="badge badge-primary ml-2">Nivelaci&oacute;n</span>
                                        </div>
                                        <div class="small">
                                            <span class="mr-2"><strong>Horario:</strong> {{ $horaInicio }} - {{ $horaFin }}</span>
                                            <span><strong>Materia:</strong> {{ optional($clase->materia)->materia ?? optional(optional($programa)->materia)->materia ?? '-' }}</span>
                                        </div>
                                        <div class="compact-requirement mt-1">
                                            <strong>Requerimiento del d&iacute;a:</strong>
                                            <span class="requirement-text">{{ $requerimiento !== '' ? $requerimiento : 'Sin observaci&oacute;n registrada a&uacute;n.' }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right ml-3">
                                    <span class="status-pill status-text">En horario</span>
                                    <div class="small mt-1 time-text"></div>
                                </div>
                            </button>
                        </div>

                        <div id="collapse-clase-{{ $clase->id }}" class="collapse {{ $index === 0 ? 'show' : '' }}" aria-labelledby="heading-clase-{{ $clase->id }}" data-parent="#accordion-programaciones">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-8">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <strong>Hora inicio:</strong> {{ $horaInicio }}
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Hora fin:</strong> {{ $horaFin }}
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Aula:</strong> {{ optional($clase->aula)->aula ?? optional(optional($programa)->aula)->aula ?? '-' }}
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Docente:</strong> {{ optional($clase->docente)->nombrecorto ?? optional(optional($programa)->docente)->nombrecorto ?? '-' }}
                                            </div>
                                        </div>

                                        <div class="mb-3 p-2 border rounded bg-light">
                                            <strong>Requerimiento o necesidad del d&iacute;a</strong>
                                            @if($observacionesPrograma->isNotEmpty())
                                                <ul class="mt-1 mb-0 pl-3 requirement-list">
                                                    @foreach($observacionesPrograma as $observacionItem)
                                                        <li class="requirement-text">{{ strip_tags($observacionItem->observacion) }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="mt-1 text-muted requirement-empty">Sin observaci&oacute;n registrada.</div>
                                            @endif
                                        </div>

                                        <div class="mb-0 p-2 border rounded">
                                            <strong>Objetivo general de la inscripci&oacute;n actual</strong>
                                            <div class="mt-1 text-muted">
                                                {{ $objetivoGeneral !== '' ? $objetivoGeneral : 'Sin objetivo definido.' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 mt-3 mt-lg-0">
                                        <a href=""
                                           class="btn btn-primary btn-block mb-2 tooltipsC observacion"
                                           id="Programacion"
                                           title="Agregar Observacion"
                                           data-observable-id="{{ optional($programa)->id }}"
                                           data-item-target="#clase-{{ $clase->id }}">
                                            <i class="fas fa-comment-alt mr-1"></i>
                                            Agregar observaci&oacute;n de esta clase
                                        </a>

                                        @if($clase)
                                            <button type="button"
                                                    class="btn btn-success btn-block mb-2 btn-finalizar"
                                                    data-clase-id="{{ $clase->id }}">
                                                Marcar salida
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-secondary btn-block mb-2" disabled>
                                                Sin clase marcada como presente
                                            </button>
                                        @endif

                                        <div class="small border rounded p-2">
                                            <strong>Tiempo de clase:</strong>
                                            <div class="time-text"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($clasescom->isNotEmpty())
                <h5 class="mt-4 mb-3">Computaci&oacute;n presentes</h5>
                <div id="accordion-programacionescom">
                    @foreach ($clasescom as $indexCom => $claseCom)
                        @php
                            $programaCom = $claseCom->programacioncom;
                            $observacionesProgramaCom = optional($programaCom)->observaciones ?? collect();
                            $personaCom = optional(optional(optional($programaCom)->matriculacion)->computacion)->persona;
                            $horaInicioCom = optional($claseCom->horainicio)->format('H:i');
                            $horaFinCom = optional($claseCom->horafin)->format('H:i');
                            $requerimientoCom = strip_tags(optional($observacionesProgramaCom->first())->observacion ?? '');
                            $detalleCom = optional(optional(optional($programaCom)->matriculacion)->asignatura)->asignatura ?? 'Computaci&oacute;n';
                            
                            $fotoCom = $personaCom && $personaCom->foto ? app(\App\Services\ProfilePhotoService::class)->url($personaCom->foto) : app(\App\Services\ProfilePhotoService::class)->placeholder();
                        @endphp
                        <div class="card schedule-item"
                             id="clasecom-{{ $claseCom->id }}"
                             data-hora-inicio="{{ $horaInicioCom }}"
                             data-hora-fin="{{ $horaFinCom }}">
                            <div class="card-header" id="heading-com-{{ $claseCom->id }}">
                                <button class="btn btn-link text-left text-dark w-100 p-0 d-flex align-items-center justify-content-between"
                                        data-toggle="collapse"
                                        data-target="#collapse-com-{{ $claseCom->id }}"
                                        aria-expanded="{{ $indexCom === 0 ? 'true' : 'false' }}"
                                        aria-controls="collapse-com-{{ $claseCom->id }}">
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3">
                                            <img data-profile-photo src="{{ $fotoCom }}" alt="Foto de {{ $personaCom->nombre ?? 'estudiante' }}" class="student-photo">
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">
                                                #{{ $indexCom + 1 }} - {{ $personaCom->nombre ?? '-' }} {{ $personaCom->apellidop ?? '' }} {{ $personaCom->apellidom ?? '' }}
                                                <span class="badge badge-info ml-2">Computaci&oacute;n</span>
                                            </div>
                                            <div class="small">
                                                <span class="mr-2"><strong>Horario:</strong> {{ $horaInicioCom }} - {{ $horaFinCom }}</span>
                                                <span><strong>Asignatura:</strong> {{ $detalleCom }}</span>
                                            </div>
                                            <div class="compact-requirement mt-1">
                                            <strong>Requerimiento del d&iacute;a:</strong>
                                                <span class="requirement-text">{{ $requerimientoCom !== '' ? $requerimientoCom : 'Sin observaci&oacute;n registrada a&uacute;n.' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right ml-3">
                                        <span class="status-pill status-text">En horario</span>
                                        <div class="small mt-1 time-text"></div>
                                    </div>
                                </button>
                            </div>
                            <div id="collapse-com-{{ $claseCom->id }}" class="collapse {{ $indexCom === 0 ? 'show' : '' }}" aria-labelledby="heading-com-{{ $claseCom->id }}" data-parent="#accordion-programacionescom">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-8">
                                            <div class="row">
                                                <div class="col-md-6 mb-2"><strong>Hora inicio:</strong> {{ $horaInicioCom }}</div>
                                                <div class="col-md-6 mb-2"><strong>Hora fin:</strong> {{ $horaFinCom }}</div>
                                                <div class="col-md-6 mb-2"><strong>Aula:</strong> {{ optional($claseCom->aula)->aula ?? optional(optional($programaCom)->aula)->aula ?? '-' }}</div>
                                                <div class="col-md-6 mb-2"><strong>Docente:</strong> {{ optional($claseCom->docente)->nombrecorto ?? optional(optional($programaCom)->docente)->nombrecorto ?? '-' }}</div>
                                            </div>
                                            <div class="mb-0 p-2 border rounded bg-light">
                                                <strong>Requerimiento o necesidad del d&iacute;a</strong>
                                                @if($observacionesProgramaCom->isNotEmpty())
                                                    <ul class="mt-1 mb-0 pl-3 requirement-list">
                                                        @foreach($observacionesProgramaCom as $observacionItemCom)
                                                            <li class="requirement-text">{{ strip_tags($observacionItemCom->observacion) }}</li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <div class="mt-1 text-muted requirement-empty">Sin observaci&oacute;n registrada.</div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-lg-4 mt-3 mt-lg-0">
                                            <a href=""
                                               class="btn btn-primary btn-block mb-2 tooltipsC observacion"
                                               id="Programacioncom"
                                               title="Agregar Observacion"
                                               data-observable-id="{{ optional($programaCom)->id }}"
                                               data-item-target="#clasecom-{{ $claseCom->id }}">
                                                <i class="fas fa-comment-alt mr-1"></i>
                                                Agregar observaci&oacute;n de esta clase
                                            </a>
                                            @if($claseCom)
                                                <button type="button"
                                                        class="btn btn-success btn-block mb-2 btn-finalizar-com"
                                                        data-clase-id="{{ $claseCom->id }}">
                                                    Marcar salida
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-secondary btn-block mb-2" disabled>
                                                    Sin clase marcada como presente
                                                </button>
                                            @endif
                                            <div class="small border rounded p-2">
                                                <strong>Tiempo de clase:</strong>
                                                <div class="time-text"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    @include('observacion.modalcreate')
@stop

@section('js')
    <script src="{{ asset('dist/js/moment.js') }}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script>
        (function ($) {
            'use strict';
            $.fn.addTempClass = function(className, expire, callback) {
                className || (className = '');
                expire || (expire = 2000);
                return this.each(function() {
                    $(this).addClass(className).delay(expire).queue(function() {
                        $(this).removeClass(className).clearQueue();
                        callback && callback();
                    });
                });
            };
        }(jQuery));

        function showModalCompat(selector) {
            const el = document.querySelector(selector);
            if (!el) return;
            if (window.bootstrap && window.bootstrap.Modal) {
                const Modal = window.bootstrap.Modal;
                let instance = null;
                if (typeof Modal.getOrCreateInstance === 'function') {
                    instance = Modal.getOrCreateInstance(el);
                } else if (typeof Modal.getInstance === 'function') {
                    instance = Modal.getInstance(el) || new Modal(el);
                } else {
                    instance = new Modal(el);
                }
                instance.show();
                return;
            }
            if (window.jQuery && typeof $(el).modal === 'function') {
                $(el).modal('show');
                return;
            }
            el.style.display = 'block';
            el.classList.add('show');
            document.body.classList.add('modal-open');
        }

        function hideModalCompat(selector) {
            const el = document.querySelector(selector);
            if (!el) return;
            if (window.bootstrap && window.bootstrap.Modal) {
                const Modal = window.bootstrap.Modal;
                let instance = null;
                if (typeof Modal.getOrCreateInstance === 'function') {
                    instance = Modal.getOrCreateInstance(el);
                } else if (typeof Modal.getInstance === 'function') {
                    instance = Modal.getInstance(el) || new Modal(el);
                } else {
                    instance = new Modal(el);
                }
                instance.hide();
                return;
            }
            if (window.jQuery && typeof $(el).modal === 'function') {
                $(el).modal('hide');
                return;
            }
            el.classList.remove('show');
            el.style.display = 'none';
            document.body.classList.remove('modal-open');
            $('.modal-backdrop').remove();
        }

        if (window.CKEDITOR && typeof CKEDITOR.replace === 'function') {
            CKEDITOR.replace('editorguardar', {
                height: 120,
                width: '100%',
                removeButtons: 'PasteFromWord'
            });
            CKEDITOR.replace('editoreditar', {
                height: 120,
                width: '100%',
                removeButtons: 'PasteFromWord'
            });
        }

        function actualizarEstadosTiempo() {
            let enHorario = 0;
            let excedidos = 0;

            $('.schedule-item').each(function() {
                const item = $(this);
                const inicio = item.data('hora-inicio');
                const fin = item.data('hora-fin');
                const [hIni, mIni] = (inicio || '00:00').split(':').map(Number);
                const [hFin, mFin] = (fin || '00:00').split(':').map(Number);

                const ahora = moment();
                const start = moment().hours(hIni).minutes(mIni).seconds(0).milliseconds(0);
                const end = moment().hours(hFin).minutes(mFin).seconds(0).milliseconds(0);

                const minutosRestantes = end.diff(ahora, 'minutes');
                const minutosDesdeInicio = ahora.diff(start, 'minutes');

                let claseEstado = 'state-ok';
                let textoEstado = 'En horario';
                let textoTiempo = '';

                if (minutosRestantes > 15) {
                    claseEstado = 'state-ok';
                    textoEstado = 'En horario';
                    textoTiempo = 'Restan ' + minutosRestantes + ' min';
                } else if (minutosRestantes > 0) {
                    claseEstado = 'state-warning';
                    textoEstado = 'Por terminar';
                    textoTiempo = 'Restan ' + minutosRestantes + ' min';
                } else if (minutosRestantes >= -10) {
                    claseEstado = 'state-finished';
                    textoEstado = 'Hora final';
                    textoTiempo = 'Finalizo hace ' + Math.abs(minutosRestantes) + ' min';
                } else {
                    claseEstado = 'state-overdue';
                    textoEstado = 'Excedido';
                    textoTiempo = 'Pasado por ' + Math.abs(minutosRestantes) + ' min';
                    excedidos++;
                }

                if (minutosDesdeInicio >= 0 && minutosRestantes >= 0) {
                    enHorario++;
                }

                item.removeClass('state-ok state-warning state-finished state-overdue').addClass(claseEstado);
                item.find('.status-text').text(textoEstado);
                item.find('.time-text').text(textoTiempo);
            });

            $('#metric-current').text(enHorario);
            $('#metric-overdue').text(excedidos);
        }

        $(function() {
            actualizarEstadosTiempo();
            setInterval(actualizarEstadosTiempo, 30000);
            let observacionItemTarget = null;

            $(document).on('click', '.observacion', function(e) {
                e.preventDefault();
                console.log('misestudiantes observacion click');
                const observableId = $(this).data('observable-id');
                const observableType = $(this).attr('id');
                observacionItemTarget = $(this).data('item-target');
                $('#observable_id').val(observableId);
                $('#observable_type').val(observableType);
                if (window.CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.editorguardar) {
                    CKEDITOR.instances.editorguardar.setData('');
                } else {
                    $('#editorguardar').val('');
                }
                showModalCompat('#modal-agregar-observacion');
            });

            $('#guardar-observacion').on('click', function(e) {
                e.preventDefault();
                if (window.CKEDITOR && CKEDITOR.instances) {
                    for (let instance in CKEDITOR.instances) {
                        CKEDITOR.instances[instance].updateElement();
                    }
                }
                const observableId = $('#observable_id').val();
                const observableType = $('#observable_type').val();
                const observacion = $('#editorguardar').val();
                let guardarUrl = "{{ url('guardar/observacion') }}";
                let payload = {
                    observacion: observacion,
                    observable_id: observableId,
                    observable_type: observableType
                };

                if (observableType === 'Programacion') {
                    guardarUrl = "{{ route('guardar.observacion.programacion') }}";
                    payload = {
                        observacion: observacion,
                        id_programacion: observableId
                    };
                } else if (observableType === 'Programacioncom') {
                    guardarUrl = "{{ route('guardar.observacion.programacioncom') }}";
                    payload = {
                        observacion: observacion,
                        id_programacioncom: observableId
                    };
                }

                $.ajax({
                    url: guardarUrl,
                    data: payload,
                    success: function(json) {
                        if (json.errores) {
                            $('.error').html(json.errores.observacion);
                            $('.diverror').removeClass('d-none');
                            return;
                        }

                        const textoPlano = $('<div>').html(observacion).text().trim();
                        const textoFinal = textoPlano.length ? textoPlano : 'Observacion actualizada.';
                        if (observacionItemTarget) {
                            const item = $(observacionItemTarget);
                            item.find('.compact-requirement .requirement-text').first().text(textoFinal);

                            const listas = item.find('.requirement-list');
                            if (listas.length) {
                                listas.each(function() {
                                    $(this).prepend($('<li/>', { class: 'requirement-text', text: textoFinal }));
                                });
                                item.find('.requirement-empty').remove();
                            } else {
                                item.find('.requirement-text').text(textoFinal);
                            }

                            item.addTempClass('border border-success', 1600);
                        }
                        hideModalCompat('#modal-agregar-observacion');
                        $('.diverror').addClass('d-none');

                        Swal.fire({
                            icon: 'success',
                            title: 'Observacion registrada',
                            timer: 1600,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo guardar la observacion.', 'error');
                    }
                });
            });

            $('.btn-finalizar').on('click', function() {
                const claseId = $(this).data('clase-id');
                const boton = $(this);

                $.ajax({
                    url: "{{ route('clases.finalizar') }}",
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: {
                        id: claseId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(json) {
                        boton.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .text('Salida registrada');

                        Swal.fire({
                            icon: 'success',
                            title: json.message || 'Salida marcada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo marcar la salida.', 'error');
                    }
                });
            });

            $('.btn-finalizar-com').on('click', function() {
                const claseId = $(this).data('clase-id');
                const boton = $(this);

                $.ajax({
                    url: "{{ route('clasecom.finalizar') }}",
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: {
                        id: claseId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(json) {
                        boton.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .text('Salida registrada');

                        Swal.fire({
                            icon: 'success',
                            title: json.message || 'Salida marcada correctamente',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo marcar la salida.', 'error');
                    }
                });
            });

            $('#modal-agregar-observacion').on('hidden.bs.modal', function() {
                $('.diverror').addClass('d-none');
            });
        });
    </script>
@stop

