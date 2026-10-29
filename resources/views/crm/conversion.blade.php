<details class="card card-body"><summary>Vincular inscripción y convertir en cliente</summary>
<p class="mt-3">Seleccione a la persona que habló con IFE y al estudiante. Pueden ser personas distintas. Complete la inscripción mediante el proceso habitual antes de confirmar.</p>
@if($prospecto->persona_id)<p>Contacto vinculado: <a href="{{ route('personas.show',$prospecto->persona_id) }}">{{ optional($prospecto->persona)->nombre }} (#{{ $prospecto->persona_id }})</a>.</p>
@else
@can('Crear Personas')<p><a href="{{ route('personas.crear.contacto',['crm_prospecto'=>$prospecto->id]) }}">Registrar al contacto o apoderado con sus datos actuales</a></p><p><a href="{{ route('personas.create',['crm_prospecto'=>$prospecto->id,'crm_papel'=>'estudiante']) }}">El propio contacto es el estudiante: abrir alta académica</a></p>
@endcan
@endif
<form method="POST" action="{{ route('crm.convertir',$prospecto) }}">
@csrf<input type="hidden" name="version" value="{{ $prospecto->crm_version }}">
@foreach(['persona_id'=>'Contacto: estudiante o apoderado','estudiante_persona_id'=>'Estudiante de la inscripción'] as $field=>$label)
<div class="form-group" data-person-search data-url="{{ route('crm.personas') }}"><label for="convert-{{ $field }}">{{ $label }} *</label><input type="search" id="convert-{{ $field }}" class="form-control mb-1" placeholder="Buscar por nombre, teléfono o código" data-person-query autocomplete="off"><select name="{{ $field }}" class="form-control" required data-person-select><option value="">Seleccione una persona</option>
@if($field==='persona_id'&&$prospecto->persona_id)<option value="{{ $prospecto->persona_id }}" selected>{{ optional($prospecto->persona)->nombre }} (#{{ $prospecto->persona_id }})</option>
@endif</select><div class="small mt-2"><a data-person-link="url" target="_blank" rel="noopener" hidden>Abrir ficha</a> <a data-person-link="academico" target="_blank" rel="noopener" hidden>Inscripción académica</a> <a data-person-link="computacion" target="_blank" rel="noopener" hidden>Computación</a></div></div>
@endforeach
<label for="convert-relation">Parentesco si el contacto es apoderado</label><input id="convert-relation" class="form-control mb-2" name="parentesco" maxlength="40" placeholder="Por ejemplo: Mamá">
<p class="small">El estudiante debe tener su inscripción real. Desde su ficha puede abrir las opciones académicas o de computación habituales.</p>
@can('Crear Personas')<p class="small"><a href="{{ route('personas.create') }}" target="_blank" rel="noopener">Registrar a otro estudiante por el proceso habitual</a></p>
@endcan
<label for="convert-type">Proceso *</label><select id="convert-type" class="form-control mb-2" name="tipo"><option value="inscripcion">Inscripción académica</option><option value="matriculacion">Matrícula de computación</option></select>
<label for="convert-enrollment">Código de esa inscripción o matrícula *</label><input class="form-control mb-2" type="number" min="1" id="convert-enrollment" name="inscripcion_id" required>
<label><input type="checkbox" name="confirmar_vinculos" value="1" required> Revisé las identidades y confirmo que esta inscripción corresponde a la oportunidad.</label>
<button class="btn btn-primary">Confirmar conversión</button></form></details>
