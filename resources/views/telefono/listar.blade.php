<table id="telefonos" class="table table-hover table-bordered table-striped display responsive nowrap" width="100%">
        <thead class="bg-primary">
            <th>#</th>
            <th>CONTACTO</th>
            <th>NUMERO</th>
            <th>PARENTESCO</th>
            <th>ACTUALIZADO</th>
            <th width="120px">Opciones</th>
        </thead>
        <tbody>

            <tr>
                <td>1</td>
                    <td>
                        {{$persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom}}
                    </td>
                    <td>
                        <a href="tel:{{$persona->telefono}}" data-phone-persona="{{ $persona->id }}">{{$persona->telefono}}</a>
                    </td>
                    <td>
                        PERSONAL
                    </td>
                    <td>{{$persona->updated_at}}</td>
                    <td>
                        <a href="{{route('personas.edit',$persona)}}" class="btn-accion-tabla tooltipsC mr-2" title="Editar este número">
                            <i class="fa fa-fw fa-edit text-primary"></i>
                        </a> 
                    </td>
            </tr>

            @foreach ($apoderados as $apoderado)
                <tr>
                    <td>{{$loop->iteration+1}}</td>
                    <td>
                        {{$apoderado->nombre.' '.$apoderado->apellidop.' '.$apoderado->apellidom}}
                    </td>
                    <td>
                        <a href="tel:{{$apoderado->telefono}}" data-phone-persona="{{ $apoderado->id }}">{{$apoderado->telefono}}</a>
                    </td>
                    <td>
                        {{$apoderado->pivot->parentesco}}
                    </td>
                    <td>{{$apoderado->updated_at}}</td>
                    <td>
                        <a href="{{route('telefono.editar',['persona'=>$persona,'apoderado_id'=>$apoderado->id])}}" class="btn-accion-tabla tooltipsC mr-2" title="Editar este número">
                            <i class="fa fa-fw fa-edit text-primary"></i>
                        </a> 
                        <form action="{{route('telefono.eliminar',['persona'=>$persona,'id'=>$apoderado->id])}}"  class="d-inline formulario" method="POST">
                            @csrf
                            @method("delete")
                            <button name="btn-eliminar" type="submit" class="btn eliminar" title="Eliminar este Número">
                                <i class="fa fa-fw fa-trash text-danger"></i>   
                            </button>
                        </form> 
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
