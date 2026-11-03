@extends('adminlte::page')
@section('title','Estudiantes presentes')
@section('content_header')<h1>Estudiantes presentes</h1>@stop
@section('content')
<div class="ife-presentes" id="ife-presentes-list">
    @if(!$noticesReady)<div class="alert alert-warning">Falta aplicar la migración de avisos de salida para habilitar el listado y las alertas.</div>@endif
    <div class="card">
        <div class="card-header d-flex align-items-center"><h3 class="card-title">Asistencias sin salida <span data-presence-total class="badge badge-light">0</span></h3><button type="button" class="btn btn-sm btn-light ml-auto" data-presence-refresh>Actualizar</button></div>
        <div class="card-body">
            <div class="row align-items-end mb-3">
                <div class="col-md-6"><label for="presence-search">Buscar estudiante, docente, materia o sucursal</label><input id="presence-search" type="search" class="form-control" autocomplete="off"></div>
                <div class="col-md-3"><label for="presence-channel">Tipo de inscripción</label><select class="form-control" id="presence-channel"><option value="">Todos</option><option value="apoyo">Inscripciones</option><option value="computacion">Matriculaciones</option></select></div>
                <div class="col-md-3 pt-2"><button type="button" class="btn btn-ife btn-block" data-presence-open>Revisar salidas pendientes</button></div>
            </div>
            <div class="presence-legend mb-2" aria-label="Indicadores de horario"><span class="presence-normal">Dentro del horario</span><span class="presence-warning">Quedan 10 minutos o menos</span><span class="presence-due">Hora de salida (primer minuto)</span><span class="presence-overdue">Salida vencida</span></div>
            <p class="small text-muted mb-2" data-presence-updated>Cargando presentes…</p>
            <div class="alert alert-danger d-none" data-presence-page-error role="alert"></div>
            <div class="table-responsive"><table class="table table-bordered table-sm mb-0"><thead><tr><th>Estudiante</th><th>Clase / sucursal</th><th>Docente / materia / aula</th><th>Fecha y horario</th><th>Transcurrido</th><th>Tiempo restante</th><th>Acciones</th></tr></thead><tbody data-presence-rows><tr><td colspan="7">Cargando presentes…</td></tr></tbody></table></div>
            <p class="small text-muted mt-2 mb-0">Los tiempos se actualizan cada segundo; las asistencias se consultan cada 30 segundos. La salida requiere confirmación y registra la hora real. El historial se conserva en cada inscripción o matrícula.</p>
        </div>
    </div>
</div>
@stop
