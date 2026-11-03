@if(isset($program) && ($program->adicional || $program->compensacion))
    @include('clase.asistencia.relation')
    <a class="btn btn-sm btn-outline-primary" href="{{ route('clases.marcado.general',$program->inscripcione_id) }}">Asistencia e historial</a>
@elseif ($estado=="PRESENTE")
    <a href="" class="text-secondary ml-2 editar" data-toggle="tooltip" data-placement="top" title="Editar este programa"><i class="fas fa-edit fa-2x"></i></a>    
    <a href="" class="text-secondary ml-2 observacion" data-toggle="tooltip" data-placement="top" title="Agregar Observacion"><i class="fas fa-comment-medical fa-2x"></i></a>
    <a href="" class="text-secondary ml-2 mostrar" data-toggle="tooltip" data-placement="top" title="Ver este programa"><i class="fas fa-eye fa-2x"></i></a>
@else
     <a class="btn text-secondary ml-2" href="{{route('marcado.presente.rapido',$id)}}" title="Marcado Rapido"><i class="fas fa-bolt fa-2x text-fuchsia"></i></a>
    <a class="text-secondary ml-2" href="{{route('marcado.presente.normal',$id)}}" title="Marcado Normal"> <i class="fas fa-check-circle fa-2x text-success"></i></a>
    <a href="" class="text-secondary ml-2 editar" data-toggle="tooltip" data-placement="top" title="Editar este programa"><i class="fas fa-edit fa-2x text-warning"></i></a>    
    <a href="" class="text-secondary ml-2 mostrar" data-toggle="tooltip" data-placement="top" title="Ver este programa"><i class="fas fa-eye fa-2x text-purple"></i></a>
    <a href="" class="text-secondary ml-2 observacion" data-toggle="tooltip" data-placement="top" title="Agregar Observacion"><i class="fas fa-comment-medical fa-2x text-indigo"></i></a>
    {{-- <a href="" class="text-secondary ml-2 marcar_clase_atrazada" data-toggle="tooltip" data-placement="top" title="Marcar clase Atrazada"><i class="fa-solid fa-alarm-plus"></i> --}}
@endif

