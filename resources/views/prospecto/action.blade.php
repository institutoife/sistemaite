<a href="{{ route('prospecto.view', $id) }}" class="btn-accion-tabla mr-1" title="Ver prospecto y seguimiento">
    <i class="fa fa-fw fa-eye text-primary"></i>
</a>
@can('Editar Prospectos')
<a href="{{ route('prospecto.edit', $id) }}" class="btn-accion-tabla mr-1" title="Editar prospecto">
    <i class="fa fa-fw fa-edit text-primary"></i>
</a>
@endcan
