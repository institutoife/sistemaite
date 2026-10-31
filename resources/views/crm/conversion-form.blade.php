<p>Seleccione la inscripción o matrícula que ya se registró para este cliente. Si ya pagó, conserve ese pago: esta confirmación solo vincula la inscripción con el CRM.</p>
<p class="small text-muted">El contacto y el estudiante pueden ser personas distintas. No se vinculan automáticamente por compartir teléfono.</p>
@if(!$prospecto->persona_id)
@can('Crear Personas')
<p class="small"><a href="{{ route('personas.crear.contacto',['crm_prospecto'=>$prospecto->id]) }}">Registrar al contacto o apoderado si todavía no tiene ficha</a> · <a href="{{ route('personas.create',['crm_prospecto'=>$prospecto->id,'crm_papel'=>'estudiante']) }}">El propio contacto es el estudiante: abrir alta académica</a></p>
@endcan
@endif
<form method="POST" action="{{ route('crm.convertir',$prospecto) }}" data-crm-conversion-form data-enrollments-url="{{ route('crm.conversion.inscripciones',$prospecto) }}">
@csrf
<input type="hidden" name="version" value="{{ $prospecto->crm_version }}">
@if(isset($conversionStage)&&$conversionStage)<input type="hidden" name="etapa_id" value="{{ $conversionStage->id }}">
@endif
<p class="alert alert-danger d-none" data-conversion-error role="alert"></p>
@foreach(['persona_id'=>'Contacto: estudiante o apoderado','estudiante_persona_id'=>'Estudiante de la inscripción'] as $field=>$label)
<div class="form-group" data-person-search data-url="{{ route('crm.personas') }}">
<label for="convert-{{ $field }}">{{ $label }} *</label>
<input type="search" id="convert-{{ $field }}" class="form-control mb-1" placeholder="Buscar por nombre, teléfono o código" data-person-query autocomplete="off">
<select name="{{ $field }}" class="form-control" required data-person-select><option value="">Seleccione una persona</option>
@if($field==='persona_id'&&$prospecto->persona_id)<option value="{{ $prospecto->persona_id }}" selected>{{ optional($prospecto->persona)->nombre }} (#{{ $prospecto->persona_id }})</option>
@endif
@if($field==='estudiante_persona_id'&&$prospecto->estudiante_persona_id)<option value="{{ $prospecto->estudiante_persona_id }}" selected>{{ optional($prospecto->estudiantePersona)->nombre }} (#{{ $prospecto->estudiante_persona_id }})</option>
@endif
</select>
<div class="small mt-2"><a data-person-link="url" target="_blank" rel="noopener" hidden>Abrir ficha</a> <a data-person-link="academico" target="_blank" rel="noopener" hidden>Inscripción académica</a> <a data-person-link="computacion" target="_blank" rel="noopener" hidden>Computación</a></div>
</div>
@endforeach
@can('Crear Personas')<p class="small"><a href="{{ route('personas.create') }}" target="_blank" rel="noopener">Registrar a otro estudiante por el proceso habitual, si todavía no tiene ficha</a></p>
@endcan
<label for="convert-existing">Inscripción o matrícula existente</label>
<select id="convert-existing" class="form-control mb-1" data-conversion-enrollment><option value="">Seleccione primero al contacto</option></select>
<p class="small text-muted" data-conversion-help>Se muestran las inscripciones del contacto y de sus estudiantes vinculados; también puede buscar otro estudiante.</p>
<div class="row"><div class="col-md-6 form-group"><label for="convert-type">Proceso *</label><select id="convert-type" class="form-control" name="tipo"><option value="inscripcion">Inscripción académica</option><option value="matriculacion">Matrícula de computación</option></select></div>
<div class="col-md-6 form-group"><label for="convert-enrollment">Código de inscripción o matrícula *</label><input class="form-control" type="number" min="1" id="convert-enrollment" name="inscripcion_id" required><small>Se completa al elegir la inscripción; también puede ingresar el código conocido.</small></div></div>
<label for="convert-relation">Parentesco si el contacto es apoderado</label><input id="convert-relation" class="form-control mb-2" name="parentesco" maxlength="40" placeholder="Por ejemplo: Mamá">
<div class="alert alert-info small" data-google-link-choice>
<p>Si el prospecto y la Persona seleccionada ya tienen contactos Google distintos, puede conservar ambos. Las próximas sincronizaciones actualizarán el contacto de la Persona; el contacto anterior del prospecto permanecerá sin cambios.</p>
<label><input type="checkbox" name="conservar_contactos_google" value="1"> Conservar ambos contactos Google y usar el de la Persona seleccionada.</label>
</div>
<label class="d-block"><input type="checkbox" name="confirmar_vinculos" value="1" required> Revisé el contacto, el estudiante y la inscripción y confirmo la conversión.</label>
<button class="btn btn-primary" data-confirm-conversion>Confirmar conversión</button>
</form>
