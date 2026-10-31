@extends('adminlte::page')

@section('css')
    <link rel="stylesheet" href="{{asset('bootstrap/css/bootstrap.css')}}">
@stop

@section('title', 'Usuarios')

@section('plugins.Jquery', true)
@section('plugins.Sweetalert2', true)
@section('plugins.Datatables', true)


@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header bg-secondary">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <a href="{{ route('user.crear') }}"> <button class="btn btn-primary" type="button">Crear usuario</button> </a> 
                            <span id="card_title">
                                {{ __('User') }}
                            </span>
                        </div>
                    </div>
                    @if (Session::has('success'))
                        <script>
                            const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                            didOpen: (toast) => {
                                toast.addEventListener('mouseenter', Swal.stopTimer)
                                toast.addEventListener('mouseleave', Swal.resumeTimer)
                            }
                            })
                            Toast.fire({
                            icon: 'success',
                            title: 'Datos actualizados correctamente'
                            })
                        </script>
                    @endif
                         
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="usuarios" class="table table-striped table-hover table-bordered">
                                <thead class="thead">
                                    <tr>
                                        <th>#</th>
										<th>Name</th>
										<th>Email</th>
										<th>Foto</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-cambiar-password" tabindex="-1" role="dialog" aria-labelledby="modalCambiarPasswordLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="form-cambiar-password">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalCambiarPasswordLabel">Cambiar contraseña</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="password_user_id" name="user_id">
                            <div class="alert alert-info mb-3">
                                Usuario: <strong id="password_user_name">-</strong>
                            </div>

                            <div class="form-group">
                                <label for="password">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password" title="Mostrar/Ocultar contraseña">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.</small>
                                <div class="invalid-feedback d-block" id="password_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Confirmar contraseña</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirmation" title="Mostrar/Ocultar contraseña">
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="invalid-feedback d-block" id="password_confirmation_error"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    
    <script>
    $(document).ready(function() {
        const updatePasswordRoute = "{{ route('users.password.update', ['user' => '__USER__']) }}";

        var tabla=$('#usuarios').DataTable(
                {
                    "serverSide": true,
                    "responsive":true,
                    "autoWidth":false,

                    "ajax": "{{ route('users.data') }}",
                    "columns": [
                        {data: 'id'},
                        {data: 'name'},
                        {data: 'email'},
                        {
                            "name": "foto",
                            "data": "foto",
                            "render": function (data, type, full, meta) {
                                return IFEPhotos.render(data,type,full,{height:50});
                            },
                            "title": "FOTO",
                            "orderable": false,
            
                        },     
                        {
                            "name":"btn",
                            "data": 'btn',
                            "orderable": false,
                        },
                    ],
                    "columnDefs": [
                        { responsivePriority: 1, targets: 0 },  
                        { responsivePriority: 2, targets: -1 }
                    ],
                    "language":{
                        "url":"https://cdn.datatables.net/plug-ins/1.12.1/i18n/es-ES.json"
                    },  
                }
            );

            $('table').on('click','.eliminar',function (e) {
                e.preventDefault(); 
                id=$(this).parent().parent().parent().find('td').first().html();
                console.log(id);
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
                            url: 'eliminar/usuario/'+id,
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

            function getRowDataFromButton(button) {
                let tr = button.closest('tr');
                if (tr.hasClass('child')) {
                    tr = tr.prev();
                }
                return tabla.row(tr).data() || null;
            }

            function openPasswordModal(userId, userName) {
                $('#password_user_id').val(userId || '');
                $('#password_user_name').text(userName || (userId ? ('ID ' + userId) : 'Sin usuario'));
                $('#password').val('');
                $('#password_confirmation').val('');
                $('#password').attr('type', 'password');
                $('#password_confirmation').attr('type', 'password');
                $('#form-cambiar-password .toggle-password i').removeClass('fa-eye-slash').addClass('fa-eye');
                $('#password_error').text('');
                $('#password_confirmation_error').text('');
                $('#modal-cambiar-password').modal('show');
            }

            $('#modal-cambiar-password').on('show.bs.modal', function (event) {
                const button = $(event.relatedTarget);
                if (!button || !button.length) {
                    return;
                }
                const rowData = getRowDataFromButton(button);
                const userId = (rowData && rowData.id) ? rowData.id : button.data('user-id');
                const userName = (rowData && rowData.name) ? rowData.name : button.data('user-name');
                $('#password_user_id').val(userId || '');
                $('#password_user_name').text(userName || (userId ? ('ID ' + userId) : 'Sin usuario'));
            });

            $(document).on('click', '.btn-cambiar-password', function (e) {
                e.preventDefault();
                const button = $(this);
                const rowData = getRowDataFromButton(button);
                const userId = (rowData && rowData.id) ? rowData.id : button.data('user-id');
                const userName = (rowData && rowData.name) ? rowData.name : button.data('user-name');

                if (!userId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Usuario no detectado',
                        text: 'No se pudo identificar la fila seleccionada. Recarga la pagina e intenta nuevamente.'
                    });
                    return;
                }

                openPasswordModal(userId, userName);
            });

            $(document).on('click', '.toggle-password', function () {
                const target = $(this).data('target');
                const $input = $(target);
                const $icon = $(this).find('i');

                if ($input.attr('type') === 'password') {
                    $input.attr('type', 'text');
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    $input.attr('type', 'password');
                    $icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });

            $('#form-cambiar-password').on('submit', function (e) {
                e.preventDefault();

                const userId = $('#password_user_id').val();
                const password = $('#password').val();
                const confirmation = $('#password_confirmation').val();

                if (!userId) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Usuario no detectado',
                        text: 'Cierra el modal y vuelve a abrirlo desde el boton de la fila del usuario.'
                    });
                    return;
                }

                const url = updatePasswordRoute.replace('__USER__', userId);

                $('#password_error').text('');
                $('#password_confirmation_error').text('');

                if (password !== confirmation) {
                    $('#password_confirmation_error').text('La confirmación de contraseña no coincide.');
                    return;
                }

                Swal.fire({
                    title: 'Confirmar cambio de contraseña',
                    text: 'Esta acción actualizará la contraseña del usuario seleccionado.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cambiar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        url: url,
                        type: 'POST',
                        dataType: 'json',
                        headers: {
                            'Accept': 'application/json'
                        },
                        data: {
                            _token: '{{ csrf_token() }}',
                            _method: 'PUT',
                            password: password,
                            password_confirmation: confirmation
                        },
                        success: function (response) {
                            $('#modal-cambiar-password').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Contraseña actualizada',
                                text: response.message || 'La contraseña se actualizó correctamente.',
                                timer: 1800,
                                showConfirmButton: false
                            });
                            tabla.ajax.reload(null, false);
                        },
                        error: function (xhr) {
                            const errors = (xhr.responseJSON && xhr.responseJSON.errors) ? xhr.responseJSON.errors : {};
                            if (errors.password && errors.password.length) {
                                $('#password_error').text(errors.password[0]);
                            }
                            if (errors.password_confirmation && errors.password_confirmation.length) {
                                $('#password_confirmation_error').text(errors.password_confirmation[0]);
                            }

                            let errorMessage = 'Verifica los datos e intentalo de nuevo.';
                            if (xhr.status === 403) {
                                errorMessage = 'No autorizado para cambiar contrasenas. Verifica el permiso Editar Usuarios.';
                            } else if (xhr.status === 419) {
                                errorMessage = 'La sesion expiro. Recarga la pagina e intentalo otra vez.';
                            } else if (xhr.status === 422 && errors.password && errors.password.length) {
                                errorMessage = errors.password[0];
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo actualizar',
                                text: errorMessage
                            });
                        }
                    });
                });
            });
        
    } );
    </script>
@stop
