@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('pugins.Sweetalert2',true)
@section('pugins.Datatables',true)

@section('title', 'Mis Inscripciones')
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Inscripcione') }}
                            </span>

                            <div class="float-right">
                                <a href="{{ route('inscripciones.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                    {{ __('Nueva Inscripción') }}
                                </a>
                            </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="inscripciones" class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
										<th>Horainicio</th>
										<th>Horafin</th>
										<th>Fechaini</th>
										<th>Fechafin</th>
										<th>Totalhoras</th>
										<th>Horasxclase</th>
										<th>Estado / Sucursal</th>
										<th>Condonado</th>
										<th>Objetivo</th>
										<th>Lunes</th>
										<th>Martes</th>
										<th>Miercoles</th>
										<th>Jueves</th>
										<th>Viernes</th>
										<th>Sabado</th>
										<th>Estudiante Id</th>
										<th>Modalidad Id</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                
                            </table>
                        </div>
                    </div>
                </div>
            
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
    $(document).ready(function() {
     
        var tabla=$('#inscripciones').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false, 
                    "ajax": "{{ url('api/inscripciones') }}",
                    "columns": [
                        {data:'id'},
                        {data:'horainicio'},
						{data:'horafin'},
						{data:'fechaini'},
						{data:'fechafin'},
						{data:'totalhoras'},
						{data:'horasxclase'},
							{data:'vigente', render:function(data,type,row){return type==='display'?row.estado_sucursal:data;}},
						{data:'condonado'},
						{data:'objetivo'},
						{data:'lunes'},
						{data:'martes'},
						{data:'miercoles'},
						{data:'jueves'},
						{data:'viernes'},
						{data:'sabado'},
						
                        {data: 'btn'},
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );
        

            $('table').on('click','.eliminar',function (e) {
                e.preventDefault(); 
                id=$(this).parent().parent().parent().find('td').first().html();
                Swal.fire({
                    title: 'Estas seguro(a) de eliminar este registro?',
                    text: "Si eliminas el registro no lo podras recuperar jamás!",
                    icon: 'question',
                    showCancelButton: true,
                    showConfirmButton:true,
                    confirmButtonColor: '#25ff80',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Eliminar..!',
                    position:'center',        
                }).then((result) => {
                    if (result.value) {
                        $.ajax({
                            url: 'eliminar/inscripcion/'+id,
                            type: 'DELETE',
                            data:{
                                id:id,
                                _token:'{{ csrf_token() }}'
                            },
                            success: function(result) {
                                tabla.ajax.reload();
                                const Toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didOpen: (toast) => {
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                }
                                })
                                Toast.fire({
                                icon: 'success',
                                title: 'Se eliminó correctamente el registro'
                                })   
                            },
                            error: function (xhr, ajaxOptions, thrownError) {
                                switch (xhr.status) {
                                    case 500:
                                        Swal.fire({
                                            title: 'No se pudo eliminar el registro Codigo error:500',
                                            showClass: {
                                                popup: 'animate__animated animate__fadeInDown'
                                            },
                                            hideClass: {
                                                popup: 'animate__animated animate__fadeOutUp'
                                            }
                                        })
                                        break;
                                
                                    default:
                                        break;
                                }
                                
                            }
                        });
                    }else{
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 4000,
                            timerProgressBar: true,
                            onOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                        })

                        Toast.fire({
                            icon: 'error',
                            title: 'No se eliminó el registro'
                        })
                    }
                })
            });
        } );
    </script>
@stop

