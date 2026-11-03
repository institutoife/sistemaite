@if(isset($program) && $program->adicional)
    @include('clase.asistencia.relation')
    <a href="{{ route('asistencia.historial',['apoyo',$program->inscripcione_id ]) }}" class="btn-accion-tabla mr-1" title="Historial y correcciones"><i class="fas fa-history"></i></a>
    <a href="#" class="mr-1 finalizar" title="Marcar salida"><i class="fas fa-clock"></i></a>
@else
@php
    $hora_inicio=new Carbon\Carbon($horafin);
    $hora_fin=Carbon\Carbon::now();
    $minutosRestantes=$hora_fin->diffInMinutes($hora_inicio,false);
@endphp

@if ($minutosRestantes<=-10)

    <a href="{{route('clases.edit', $id)}}" class="btn-accion-tabla tooltipsC mr-1 editar" title="Editar esta clase">
        <i class="fa fa-fw fa-edit text-danger"></i>
    </a>

    <a href="" class="btn-accion-tabla tooltipsC mr-1 mostrar" title="Ver esta clase">
        <i class="fa fa-fw fa-eye mostrar text-danger"></i>
    </a>

    <a href="" class="mr-1 finalizar" title="Finalizar esta clase">
        <i class="fas fa-clock text-danger"></i>
    </a>

@endif
@if (($minutosRestantes<=0)&&($minutosRestantes>-10))

    <a href="{{route('clases.edit', $id)}}" class="btn-accion-tabla tooltipsC mr-1 editar" title="Editar esta clase">
        <i class="fa fa-fw fa-edit text-danger"></i>
    </a>

    <a href="" class="btn-accion-tabla tooltipsC mr-1 mostrar" title="Ver esta clase">
        <i class="fa fa-fw fa-eye mostrar text-danger"></i>
    </a>

    <a href="" class="mr-1 finalizar" title="Finalizar esta clase">
        <i class="fas fa-clock text-danger"></i>
    </a>

@endif
@if (($minutosRestantes>0)&&($minutosRestantes<15))

    <a href="{{route('clases.edit', $id)}}" class="btn-accion-tabla tooltipsC mr-1 editar" title="Editar esta clase">
        <i class="fa fa-fw fa-edit text-warning"></i>
    </a>

    <a href="" class="btn-accion-tabla tooltipsC mr-1 mostrar" title="Ver esta clase">
        <i class="fa fa-fw fa-eye mostrar text-warning"></i>
    </a>

    <a href="" class="mr-1 finalizar" title="Finalizar esta clase">
        <i class="fas fa-clock text-warning"></i>
    </a>

@endif
@if ($minutosRestantes>15)

    <a href="{{route('clases.edit', $id)}}" class="btn-accion-tabla tooltipsC mr-1 editar" title="Editar esta clase">
        <i class="fa fa-fw fa-edit text-success"></i>
    </a>

    <a href="" class="btn-accion-tabla tooltipsC mr-1 mostrar" title="Ver esta clase">
        <i class="fa fa-fw fa-eye mostrar text-success"></i>
    </a>

    <a href="" class="mr-1 finalizar" title="Finalizar esta clase">
        <i class="fas fa-clock text-success"></i>
    </a>

@endif

@endif
