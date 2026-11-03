@if(isset($program) && ($program->adicional || $program->compensacion))
    @include('clase.asistencia.relation')
    <a class="btn btn-sm btn-outline-primary" href="{{ route('clases.marcado.general',$program->inscripcione_id ) }}">Asistencia e historial</a>
@else
<a href="" class="text-danger licencia" data-toggle="tooltip" data-placement="top" title="Licencia"><i class="fas fa-ambulance"></i> </a>
<a href="{{route('marcado.presente.normal',$id)}}" class="text-success ml-2" data-toggle="tooltip" data-placement="top" title="Adelantar Clase"><i class="far fa-arrow-alt-circle-right"></i></a>
<a href="" class="text-primary ml-2 editar" data-toggle="tooltip" data-placement="top" title="Editar este programa"><i class="fas fa-edit"></i></a>
<a href="" id="promostrar{{$id}}" class="text-secondary ml-2 mostrar" data-toggle="tooltip" data-placement="top" title="Ver este programa"><i class="fas fa-eye"></i></a>
<a href="" class="text-secondary ml-2 observacion" data-toggle="tooltip" data-placement="top" title="Agregar Observacion"><i class="fas fa-comment-medical text-danger"></i></a>
<a class="text-secondary ml-2" href="{{route('marcado.presente.normal',$id)}}" title="Marcar clase atrazada"><i class="fa-solid fa-calendar-plus text-fuchsia"></i></a>
<form action=""  class="d-inline formulario">
    @csrf
    @method("delete")
    <button name="btn-eliminar" id="{{$id}}" type="submit" class="btn eliminargenerico" title="Eliminar este comentario">
        <i class="fa fa-fw fa-trash text-danger"></i>   
    </button>
</form> 

@endif
