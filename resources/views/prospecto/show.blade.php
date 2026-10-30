
@extends('crm.layout')
@section('crm_content')
<div class="row"><section class="col-lg-5"><div class="card card-body">
<h2 class="h4">{{ $prospecto->identificacion }}</h2><p class="text-muted">#{{ $prospecto->id }} · {{ $prospecto->legacy_persona_id?'Potencial vinculado':'Prospecto' }} · {{ ucfirst($prospecto->crm_status) }}</p>
<dl class="crm-details"><dt>Teléfono</dt><dd>{{ $prospecto->telefono ?: 'Sin teléfono' }}</dd><dt>Servicios</dt><dd>{{ $prospecto->interests->pluck('interest')->implode(', ') ?: 'Por definir' }}</dd><dt>Estudiante</dt><dd>{{ $prospecto->estudiante_nombre ?: 'Por definir' }}</dd><dt>Curso o nivel</dt><dd>{{ optional($prospecto->grado)->grado ?: 'Por definir' }}</dd><dt>Sucursal</dt><dd>{{ optional($prospecto->sede)->nombre ?: 'Por definir' }}</dd><dt>Origen</dt><dd>{{ optional($prospecto->como)->como ?: 'Por definir' }}</dd><dt>Responsable comercial</dt><dd>{{ optional($prospecto->responsable)->name ?: 'Sin asignar' }}</dd><dt>Creado por</dt><dd>{{ optional($prospecto->creador)->name ?: 'Sin registro histórico' }}</dd><dt>Responsable al alta</dt><dd>{{ optional($prospecto->responsableInicial)->name ?: 'Sin registro histórico' }}</dd><dt>Sin seguimiento</dt><dd>{{ $prospecto->dias_sin_seguimiento===null?'Sin fecha histórica':$prospecto->dias_sin_seguimiento.' días' }}</dd><dt>Etapa</dt><dd>{{ optional($prospecto->etapa)->nombre }}</dd><dt>Próxima actividad</dt><dd>{{ $prospecto->proximo_contacto_at?$prospecto->proximo_contacto_at->format('d/m/Y H:i'):'Sin programar' }}</dd><dt>Observaciones</dt><dd class="crm-prewrap">{{ $prospecto->observacion ?: 'Sin observaciones' }}</dd></dl>
@if($prospecto->persona_id)<p><a href="{{ route('personas.show',$prospecto->persona_id) }}">Abrir Persona #{{ $prospecto->persona_id }}</a></p>
@endif
@if($prospecto->convertido_at)<p>Conversión: {{ $prospecto->convertido_at->format('d/m/Y H:i') }}. Estudiante #{{ $prospecto->estudiante_persona_id }}; {{ $prospecto->inscripcione_id?'inscripción #'.$prospecto->inscripcione_id:'matrícula #'.$prospecto->matriculacion_id }}. Registrada por {{ optional($prospecto->convertidoPor)->name ?: 'Sin registro histórico' }}. Responsable en la conversión: {{ optional($prospecto->responsableConversion)->name ?: 'Sin registro histórico' }}.</p>
@endif
@if(!$mayContact)<p class="alert alert-warning">El contacto comercial está restringido. Puede conservar notas y registrar visitas presenciales.</p>
@endif
@can('update',$prospecto)
@if($prospecto->crm_status!=='convertido')<a class="btn btn-outline-primary mb-2" href="{{ route('prospecto.edit',$prospecto) }}">Editar ficha</a>
@endif
@if($prospecto->crm_status==='activo')
<form class="mb-3" action="{{ route('crm.mover',$prospecto) }}" method="POST">
@csrf<input type="hidden" name="version" value="{{ $prospecto->crm_version }}"><label for="detail-stage">Cambiar etapa</label><div class="input-group"><select class="form-control" id="detail-stage" name="etapa_id">
@foreach($etapas as $stage)
@if($stage->activa&&!$stage->conversion)<option value="{{ $stage->id }}"
@if($stage->id===$prospecto->crm_etapa_id) selected
@endif>{{ $stage->nombre }}</option>
@endif
@endforeach</select><div class="input-group-append"><button class="btn btn-outline-primary">Guardar</button></div></div></form>
@endif
@if($mayContact)<button class="btn btn-success mb-2" type="button" data-open-wa data-url="{{ route('crm.whatsapp',$prospecto) }}">Contactar por WhatsApp</button>
@endif
@endcan
</div>
@can('update',$prospecto)
<details class="card card-body"><summary>{{ $prospecto->crm_status==='baja'?'Reactivar oportunidad':'Dar de baja' }}</summary>
@if($prospecto->crm_status==='baja')<form method="POST" action="{{ route('crm.reactivar',$prospecto) }}" class="mt-3">
@csrf<input type="hidden" name="version" value="{{ $prospecto->crm_version }}"><p>Se conserva el historial y la preferencia de contacto.</p>
@if($prospecto->no_contactar)<label><input type="checkbox" name="autoriza_contacto" value="1"> El cliente volvió a autorizar expresamente el contacto comercial.</label>
@endif<button class="btn btn-primary">Reactivar</button></form>
@else<form method="POST" action="{{ route('crm.baja',$prospecto) }}" class="mt-3">
@csrf<input type="hidden" name="version" value="{{ $prospecto->crm_version }}"><label for="crm-reason">Motivo *</label><select class="form-control mb-2" name="motivo" id="crm-reason" required><option value="">Seleccione</option>
@foreach(\App\Services\CrmService::REASONS as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>
@endforeach</select><label for="crm-reason-note">Observaciones (obligatorias para «Otro»)</label><textarea id="crm-reason-note" class="form-control mb-2" name="observacion" maxlength="400"></textarea><button class="btn btn-outline-danger">Confirmar baja</button></form>
@endif
</details>
@endcan
@can('convert',$prospecto)
@if($prospecto->crm_status==='activo')
@include('crm.conversion')
@endif
@endcan
@can('Reasignar Prospectos')
<details class="card card-body"><summary>Reasignar responsable comercial</summary>
<form method="POST" action="{{ route('crm.reasignar',$prospecto) }}" class="mt-3">
@csrf
<input type="hidden" name="version" value="{{ $prospecto->crm_version }}">
<label for="reassign-owner">Nuevo responsable</label><select id="reassign-owner" class="form-control mb-2" name="responsable_id" required><option value="">Seleccione</option>
@foreach($responsables as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>
@endforeach</select>
<label for="reassign-note">Motivo de la reasignación *</label><textarea id="reassign-note" class="form-control mb-2" name="observacion" maxlength="400" required></textarea>
<p class="small">Se conserva quién lo creó y quién realizó las gestiones y conversiones anteriores. Asignar una ficha histórica permite recuperarla en el tablero operativo.</p>
<button class="btn btn-outline-primary">Confirmar reasignación</button></form></details>
@endcan
</section>
<section class="col-lg-7">
@can('update',$prospecto)<div class="card card-body"><h2 class="h5">Registrar actividad</h2>
<p class="small text-muted">Se registra con su usuario: {{ auth()->user()->name }}, y la fecha y hora actuales. El responsable comercial se conserva.</p>
<form method="POST" action="{{ route('crm.actividad',$prospecto) }}">
@csrf<input type="hidden" name="version" value="{{ $prospecto->crm_version }}">
<div class="row"><div class="col-md-6 form-group"><label for="activity-type">Tipo *</label><select id="activity-type" class="form-control" name="tipo">
@foreach(\App\Services\CrmService::TYPES as $value=>$label)<option value="{{ $value }}"
@if(old('tipo','observacion')===$value) selected
@endif>{{ $label }}</option>
@endforeach</select></div>
<div class="col-md-6 form-group"><label for="activity-result">Resultado *</label><select id="activity-result" class="form-control" name="resultado">
@foreach(['pendiente'=>'Pendiente','respondio'=>'Respondió','sin_respuesta'=>'Sin respuesta','interesado'=>'Interesado','enviado_confirmado'=>'Envío confirmado por el operador','otro'=>'Otro'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>
@endforeach</select></div></div>
<label for="activity-note">Observación</label><textarea id="activity-note" class="form-control mb-2" name="observacion" rows="3" maxlength="400">{{ old('observacion') }}</textarea>
<label for="activity-next">Próxima acción</label><input id="activity-next" class="form-control mb-2" name="proxima_accion" maxlength="200" value="{{ old('proxima_accion') }}">
<label for="activity-next-date">Programar próxima actividad</label><input id="activity-next-date" type="datetime-local" class="form-control mb-2" name="proximo_contacto_at" value="{{ old('proximo_contacto_at') }}">
<label class="d-block"><input type="checkbox" name="cerrar_seguimiento" value="1"> Marcar la próxima actividad anterior como atendida.</label>
<label class="d-block"><input type="checkbox" name="confirmado" value="1"> Confirmo que realicé el envío que estoy registrando (WhatsApp, información o cotización).</label>
<button class="btn btn-primary">Registrar actividad</button><p class="small text-muted mt-2">Abrir WhatsApp no registra un envío. La confirmación corresponde al operador.</p>
</form></div>
@endcan
<div class="card card-body"><h2 class="h5">Historial comercial</h2>
@forelse($observaciones as $observation)<article class="crm-timeline"><strong>{{ $observation->crmDetalle?(\App\Services\CrmService::TYPES[$observation->crmDetalle->tipo]??ucfirst($observation->crmDetalle->tipo)):'Observación anterior' }}</strong><p class="small text-muted">{{ $observation->crmDetalle?$observation->crmDetalle->ocurrio_at->format('d/m/Y H:i'):$observation->created_at }} · {{ $observation->usuarios->pluck('name')->implode(', ') }}</p><p class="crm-prewrap">{{ strip_tags($observation->observacion??'') }}</p>
@if($observation->crmDetalle)<p class="small">Autor: {{ optional($observation->crmDetalle->actor)->name ?: $observation->usuarios->pluck('name')->implode(', ') }} ? Responsable comercial en esa fecha: {{ optional($observation->crmDetalle->responsableComercial)->name ?: 'Sin registro histórico' }} · Resultado: {{ $observation->crmDetalle->resultado }}</p>
@if($observation->crmDetalle->tipo==='edicion'&&!empty($observation->crmDetalle->datos['observacion_anterior']))
<details><summary>Observación anterior a esta edición</summary><p class="crm-prewrap">{{ $observation->crmDetalle->datos['observacion_anterior'] }}</p></details>
@endif
@if($observation->crmDetalle->proxima_accion)<p>Próxima acción: {{ $observation->crmDetalle->proxima_accion }} {{ $observation->crmDetalle->proximo_contacto_at?$observation->crmDetalle->proximo_contacto_at->format('d/m/Y H:i'):'' }}</p>
@endif
@endif</article>
@empty<p>Sin actividades registradas.</p>
@endforelse
{{ $observaciones->links() }}</div>
@if($historiaPersona->isNotEmpty()||$seguimientos->isNotEmpty())<details class="card card-body"><summary>Historial previo de la Persona vinculada</summary>
@foreach($historiaPersona as $entry)<article class="crm-timeline"><small>{{ $entry->created_at }} · {{ $entry->usuarios->pluck('name')->implode(', ') }}</small><p>{{ strip_tags($entry->observacion??'') }}</p></article>
@endforeach
@foreach($seguimientos as $entry)<article class="crm-timeline"><small>{{ $entry->created_at }} · {{ optional($entry->user)->name }}</small><p>{{ $entry->accion }} · {{ $entry->motivo }} · {{ $entry->proximo_contacto }}</p></article>
@endforeach</details>
@endif
</section></div>
<dialog class="crm-dialog" data-wa-dialog><h2 class="h4">Preparar mensaje</h2><label for="wa-template">Situación comercial</label><select id="wa-template" class="form-control mb-2" data-wa-template>
@foreach(['primer_contacto'=>'Primer contacto','informacion'=>'Información','seguimiento'=>'Seguimiento','inscripcion'=>'Inscripción','reactivacion'=>'Reactivación'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>
@endforeach</select><label for="wa-message">Mensaje preparado</label><textarea class="form-control mb-3" id="wa-message" rows="5" data-wa-text readonly></textarea><p data-wa-error class="text-danger" role="alert"></p><a class="btn btn-success" data-wa-link target="_blank" rel="noopener noreferrer" hidden>Abrir WhatsApp</a><button class="btn btn-outline-secondary" type="button" onclick="this.closest('dialog').close()">Cerrar</button><p class="small text-muted mt-3">Después del envío, registre la actividad y su resultado en esta ficha.</p></dialog>
@stop
