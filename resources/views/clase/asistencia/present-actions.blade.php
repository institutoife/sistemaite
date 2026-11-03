@if($label)<span class="badge badge-light">{{ $label }}</span>@endif
@if($canFinish)<a href="#" class="btn btn-sm btn-ife finalizar" title="Marcar salida"><i class="fas fa-door-open" aria-hidden="true"></i> Marcar salida</a>@endif
@if($editUrl)<a href="{{ $editUrl }}" class="btn btn-sm btn-light">Editar</a>@endif
<a href="{{ $history }}" class="btn btn-sm btn-outline-secondary" title="Historial y correcciones"><i class="fas fa-history" aria-hidden="true"></i> Historial y correcciones</a>
