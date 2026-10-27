<a href="{{route('personas.edit', $id)}}" class="tooltipsC mr-2" title="Editar esta persona">
    <i class="fa fa-fw fa-edit text-primary"></i>
</a>

<a href="{{route('personas.show', $id)}}" class="tooltipsC mr-2" title="Ver esta persona">
    <i class="fa fa-fw fa-eye text-primary"></i>
</a>
@can('Verificar Telefonos')
    <button type="button" class="btn btn-sm btn-link p-0 mr-2" data-verify-person="{{ $id }}" title="Verificar teléfono con el cliente" aria-label="Verificar teléfono">
        <i class="fas fa-phone"></i>
    </button>
@endcan

{{-- <form action=""  class="d-inline">
    @csrf
    @method("delete")
    <button name="btn-eliminar" id="{{$id}}" type="submit" class="btn eliminargenerico" title="Eliminar este contacto">
        <i class="fa fa-fw fa-trash text-danger"></i>   
    </button>
</form>  --}}


<a href="" class="tooltipsC mr-2 observacion" id="Persona" title="Agregar Observacion">
    <i class="fas fa-comment-alt"></i>
</a>
<a href="" class="tooltipsC mr-1 mostrarobservacionespersona" title="Mostrar observaciones">
    <i class="fas fa-comments text-secondary"></i>
</a>
@php
    $asistenciaDisponible = $asistencia_disponible ?? false;
    $asistenciaProgramacionId = $asistencia_programacion_id ?? null;
    $asistenciaMensaje = $asistencia_mensaje ?? 'No disponible';
    $asistenciaUrl = $asistencia_url ?? null;
    $asistenciaTipo = $asistencia_tipo ?? null;
@endphp
@if($asistenciaDisponible && $asistenciaUrl)
    <a href="#" class="btn-accion-tabla tooltipsC mr-1 marcar-asistencia" title="{{ $asistenciaMensaje }}"
        data-programacion-id="{{ $asistenciaProgramacionId }}"
        data-url="{{ $asistenciaUrl }}"
        data-tipo="{{ $asistenciaTipo }}">
        <i class="fas fa-clipboard-check"></i>
    </a>
@else
    <span class="btn-accion-tabla text-muted mr-1" title="{{ $asistenciaMensaje }}">
        <i class="fas fa-clipboard-check"></i>
    </span>
@endif
<a href="{{route('descargar.contacto', $id)}}" class="btn-accion-tabla tooltipsC mr-1" title="Descargar contacto">
    <i class="far fa-address-card"></i>
</a>
<a class="btn-accion-tabla tooltipsC mr-1 enviarmensaje" title="Cobrar por mensaje">
    &nbsp;<i class="fab fa-whatsapp"></i>
</a>
<a class="btn-accion-tabla tooltipsC mr-1 enviarcredenciales" title="Enviar credenciales por WhatsApp">
    <i class="fas fa-key"></i>
</a>
<a href="{{route('tus.inscripciones',App\Models\Persona::findOrFail($id)->estudiante)}}" class="btn-accion-tabla tooltipsC mr-1" title="Ir a Inscripciones">
    &nbsp;<i class="fas fa-table"></i>
</a>

<span class="d-inline-block" tabindex="0" data-bs-toggle="tooltip" title="Disabled tooltip">
{{-- <a href="{{route('opcion.principal', App\Models\Persona::findOrFail($id)->estudiante->id)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de matematicas"> --}}
    <a href="{{route('opcion.principal', $id)}}" class="btn btn-primary text-white tooltipsC mr-2" title="ir a opciones de matematicas">
        <i class="fas fa-bars"></i> &nbsp; Opciones
    </a>
</span>
    
    
    

