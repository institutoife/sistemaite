@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <link rel="stylesheet" href="{{asset('dist/css/starrr.css')}}">
    <link href="{{asset('dist/css/zoomify.css')}}" rel="stylesheet" type="text/css">
    <style>
        #modal_estados .modal-header {
            background: linear-gradient(135deg, rgb(38, 186, 165), rgb(55, 95, 122));
            border-bottom: 0;
        }
        #modal_estados .nav-tabs .nav-link {
            color: rgb(55, 95, 122);
            border: 1px solid rgba(55, 95, 122, 0.2);
            margin-right: 6px;
            border-radius: 8px 8px 0 0;
            font-weight: 600;
            opacity: 0.7;
        }
        #modal_estados .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, rgb(38, 186, 165), rgb(55, 95, 122));
            color: #fff;
            border-color: transparent;
            opacity: 1;
            box-shadow: 0 4px 12px rgba(55, 95, 122, 0.25);
        }
        #modal_estados .tab-pane {
            border: 1px solid rgba(55, 95, 122, 0.15);
            border-top: 0;
            border-radius: 0 0 8px 8px;
            padding: 12px;
        }
    </style>
@stop

@section('title', 'Estudiantes')
@section('plugins.Jquery', true)
@section('plugins.Sweetalert2',true)
@section('plugins.Datatables',true)




@section('content')
        {{-- Dropdown de historial de búsquedas --}}
        @if(isset($ultimasBusquedas) && count($ultimasBusquedas) > 0)
            <div id="historial-busquedas-dropdown" class="dropdown-menu" style="display:none; position:absolute; z-index:1000; min-width:200px; background:rgba(255,255,255,0.85); box-shadow:0 2px 8px rgba(0,0,0,0.15); border-radius:0.25rem;">
                {{-- El contenido se renderiza dinámicamente por JS --}}
            </div>
        @endif
    {{ Breadcrumbs::render('home') }}

    {{-- El cuadro de búsqueda se elimina. Se debe adaptar la funcionalidad al input type=search existente en la plantilla. --}}
    
    <div class="pt-4">
        <div class="card">
            <div class="card-header bg-primary">
                Lista de Estudiantes x<a class="btn btn-secondary text-white btn-sm float-right" href="{{route('personas.create')}}">Crear Estudiante</a>
            </div>
            
            <div class="card-body">
                <table id="personas" class="table table-bordered table-hover table-striped">
                    <thead class="bg-primary text-center">
                        <tr>
                            <th>ID</th>
                            <th>NOMBRE</th>
                            <th>APELLIDOP</th>
                            <th>APELLIDOM</th>
                            <th>FOTO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

        <div id="imaginario">

        </div>
        
        
                <div class="modal fade" id="modal_estados" tabindex="-1" aria-labelledby="panelComercialLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title" id="panelComercialLabel">Panel Comercial Operativo</h5>
                        <span class="badge badge-warning">Pendientes: {{ $totalPendientesHome ?? 0 }}</span>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @php
                            $tabsMeta = [
                                'cumpleaneros' => ['label' => 'Cumpleaneros', 'color' => 'success'],
                                'clases_hoy' => ['label' => 'Vienen Hoy', 'color' => 'primary'],
                                'pago_hoy' => ['label' => 'Toca Pagar', 'color' => 'danger'],
                                'terminan' => ['label' => 'Terminan', 'color' => 'warning'],
                                'prospectos' => ['label' => 'Prospectos', 'color' => 'info'],
                                'faltones' => ['label' => 'Faltones', 'color' => 'secondary'],
                            ];
                        @endphp

                        <ul class="nav nav-tabs" role="tablist">
                            @foreach($tabsMeta as $slug => $meta)
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link {{ ($activeHomeTab ?? '') === $slug ? 'active' : '' }}" id="tab-{{ $slug }}" data-toggle="tab" href="#pane-{{ $slug }}" role="tab" aria-controls="pane-{{ $slug }}" aria-selected="{{ ($activeHomeTab ?? '') === $slug ? 'true' : 'false' }}">
                                        {{ $meta['label'] }}
                                        <span class="badge badge-{{ $meta['color'] }} ml-1">{{ $homeTabCounts[$slug] ?? 0 }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <div class="tab-content pt-3">
                            @foreach($tabsMeta as $slug => $meta)
                                @php $items = $homeAlerts[$slug] ?? collect(); @endphp
                                <div class="tab-pane fade {{ ($activeHomeTab ?? '') === $slug ? 'show active' : '' }}" id="pane-{{ $slug }}" role="tabpanel" aria-labelledby="tab-{{ $slug }}">
                                    @if(count($items) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-hover table-sm">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Telefono</th>
                                                        <th>Motivo</th>
                                                        <th>Fecha</th>
                                                        <th>Prioridad</th>
                                                        <th>Mensaje Sugerido</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($items as $item)
                                                        @php
                                                            $priority = (int)($item['prioridad'] ?? 3);
                                                            $priorityClass = $priority >= 4 ? 'danger' : ($priority === 3 ? 'warning' : 'success');
                                                            $isManaged = !empty($item['managed']);
                                                        @endphp
                                                        <tr class="{{ $isManaged ? 'table-success' : '' }}">
                                                            <td>{{ $item['nombre'] ?? 'Sin nombre' }}</td>
                                                            <td>{{ $item['telefono'] ?? '-' }}</td>
                                                            <td>{{ $item['motivo'] ?? '-' }}</td>
                                                            <td>{{ $item['fecha'] ?? '-' }}</td>
                                                            <td><span class="badge badge-{{ $priorityClass }}">P{{ $priority }}</span></td>
                                                            <td style="min-width:280px; white-space:normal;">{{ $item['mensaje'] ?? '' }}</td>
                                                            <td>
                                                                <div class="d-flex" style="gap:8px;">
                                                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-msg" title="Copiar mensaje" data-message="{{ $item['mensaje'] ?? '' }}">
                                                                        <i class="far fa-copy"></i>
                                                                    </button>
                                                                    @if(!empty($item['id']))
                                                                        <button
                                                                            type="button"
                                                                            class="btn btn-sm btn-outline-success btn-open-contactos-whatsapp"
                                                                            title="Abrir contactos para WhatsApp"
                                                                            data-persona-id="{{ $item['id'] }}"
                                                                            data-mensaje-id="5"
                                                                            data-message="{{ $item['mensaje'] ?? '' }}">
                                                                            <i class="fab fa-whatsapp"></i>
                                                                        </button>
                                                                    @endif
                                                                    @if(!empty($item['telefono']))
                                                                        <a class="btn btn-sm btn-outline-primary" title="Llamar" href="tel:{{ preg_replace('/\D+/', '', $item['telefono']) }}">
                                                                            <i class="fas fa-phone"></i>
                                                                        </a>
                                                                    @endif
                                                                    <button type="button" class="btn btn-sm {{ $isManaged ? 'btn-success' : 'btn-outline-dark' }} btn-mark-managed {{ $isManaged ? 'disabled' : '' }}" title="Marcar gestionado" data-persona-id="{{ $item['id'] ?? '' }}" data-message="{{ $item['mensaje'] ?? '' }}" data-motivo="{{ $item['motivo'] ?? '' }}" {{ $isManaged ? 'disabled' : '' }}>
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-warning btn-posponer" title="Posponer" data-persona-id="{{ $item['id'] ?? '' }}" data-message="{{ $item['mensaje'] ?? '' }}" data-motivo="{{ $item['motivo'] ?? '' }}">
                                                                        <i class="far fa-calendar-alt"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-info btn-cambiar-estado" title="Cambiar estado" data-persona-id="{{ $item['id'] ?? '' }}" data-message="{{ $item['mensaje'] ?? '' }}" data-motivo="{{ $item['motivo'] ?? '' }}">
                                                                        <i class="fas fa-random"></i>
                                                                    </button>
                                                                    @if($isManaged)
                                                                        <span class="badge badge-success managed-state">{{ $item['managed_by'] ?? 'usuario' }} {{ !empty($item['managed_at']) ? '(' . $item['managed_at'] . ')' : '' }}</span>
                                                                    @else
                                                                        <span class="badge badge-light managed-state d-none">Gestionado</span>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-light border">No hay pendientes en esta pestana.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal-marcado-normal" tabindex="-1" aria-labelledby="modalMarcadoNormalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMarcadoNormalLabel">Marcar asistencia</h5>
                        <button type="button" class="btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Cargando...
                    </div>
                </div>
            </div>
        </div>
        @include('observacion.modalcreate')
        @include('telefono.modales')
    </div>
@stop

@section('js')
        @php
            $arrayHistorial = is_array($ultimasBusquedas ?? []) ? ($ultimasBusquedas ?? []) : (($ultimasBusquedas ?? collect())->toArray());
            $historialUnicos = [];
            $vistosHistorial = [];
            foreach($arrayHistorial as $item) {
                if(is_array($item) && isset($item['termino'])) {
                    $termino = $item['termino'];
                    $usuario = $item['usuario'] ?? '';
                } elseif(is_object($item) && isset($item->termino)) {
                    $termino = $item->termino;
                    $usuario = $item->usuario ?? '';
                } else {
                    $termino = $item;
                    $usuario = '';
                }
                if(!in_array($termino, $vistosHistorial, true)) {
                    $vistosHistorial[] = $termino;
                    $historialUnicos[] = ['termino' => $termino, 'usuario' => $usuario];
                }
            }
        @endphp
        <script id="historial-busquedas-json" type="application/json">@json($historialUnicos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
                 
        <script>
        // Guardar historial en JS para filtrar (DISTINCT)
        var historialBusquedas = [];
        try {
            var historialRaw = document.getElementById('historial-busquedas-json');
            if (historialRaw) {
                historialBusquedas = JSON.parse(historialRaw.textContent || '[]');
            }
        } catch (e) {
            historialBusquedas = [];
        }
        const homeCrmEstados = @json($homeCrmEstados ?? []);
        const homeGestionUrl = "{{ route('home.alertas.gestionar') }}";

        function showMiniFeedback(icon, title) {
            if (window.toastr) {
                if (icon === 'success') toastr.success(title);
                if (icon === 'warning') toastr.warning(title);
                if (icon === 'error') toastr.error(title);
                return;
            }
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 1800,
                    showConfirmButton: false,
                    icon: icon,
                    title: title
                });
                return;
            }
            alert(title);
        }

        // Función global para renderizar un elemento del historial
        function renderItem(item) {
            if(typeof item === 'string') {
                return '<a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-busqueda="'+item+'">'
                    +'<span>'+item+'</span>'
                    +'</a>';
            } else if(item && typeof item === 'object') {
                var termino = item.termino || '';
                var usuario = item.usuario || '';
                return '<a class="dropdown-item d-flex justify-content-between align-items-center" href="#" data-busqueda="'+termino+'">'
                    +'<span>'+termino+'</span>'
                    +(usuario ? '<span class="text-muted small" style="margin-left:auto;">'+usuario+'</span>' : '')
                    +'</a>';
            }
            return '';
        }

        // Filtrar historial en tiempo real
        $(document).on('input', "input[type='search'][aria-controls='personas']", function() {
            var valor = this.value.trim().toLowerCase();
            var $input = $(this);
            var dropdown = $('#historial-busquedas-dropdown');
            if(dropdown.length){
                var offset = $input.offset();
                dropdown.css({
                    top: offset.top + $input.outerHeight() - 2,
                    left: offset.left,
                    width: $input.outerWidth(),
                    display: 'block'
                });
                var html = '';
                if(valor === ''){
                    for(let i=0; i<historialBusquedas.length; i++){
                        html += renderItem(historialBusquedas[i]);
                    }
                }else{
                    var encontrados = historialBusquedas.filter(function(item){
                        var termino = (typeof item === 'string') ? item : (item.termino || '');
                        return termino.toLowerCase().includes(valor);
                    });
                    if(encontrados.length > 0){
                        for(let i=0; i<encontrados.length; i++){
                            html += renderItem(encontrados[i]);
                        }
                    }else{
                        dropdown.hide();
                        return;
                    }
                }
                dropdown.html(html).show();
            }
        });

        // Al hacer click en el input, si está vacío, mostrar las 10 últimas búsquedas
        $(document).on('focus', "input[type='search'][aria-controls='personas']", function() {
            var valor = this.value.trim();
            var $input = $(this);
            var dropdown = $('#historial-busquedas-dropdown');
            if(dropdown.length && valor === ''){
                var offset = $input.offset();
                dropdown.css({
                    top: offset.top + $input.outerHeight() - 2,
                    left: offset.left,
                    width: $input.outerWidth(),
                    display: 'block'
                });
                var html = '';
                for(let i=0; i<historialBusquedas.length; i++){
                    html += renderItem(historialBusquedas[i]);
                }
                dropdown.html(html).show();
            }
        });

        // Al hacer click en un elemento del historial, restaurar las 10 últimas búsquedas
        $(document).on('click', '#historial-busquedas-dropdown .dropdown-item', function(e){
            e.preventDefault();
            var valor = $(this).data('busqueda');
            var $input = $("input[type='search'][aria-controls='personas']");
            $input.val(valor).trigger('input');
            // Restaurar las 10 últimas búsquedas
            var dropdown = $('#historial-busquedas-dropdown');
            var offset = $input.offset();
            dropdown.css({
                top: offset.top + $input.outerHeight() - 2,
                left: offset.left,
                width: $input.outerWidth(),
                display: 'block'
            });
            var html = '';
            for(let i=0; i<historialBusquedas.length; i++){
                html += renderItem(historialBusquedas[i]);
            }
            dropdown.html(html).show();
        });

        // Mostrar historial al limpiar el input (X)
        $(document).on('input', "input[type='search'][aria-controls='personas']", function() {
            if (this.value === '') {
                var $input = $(this);
                var dropdown = $('#historial-busquedas-dropdown');
                if(dropdown.length){
                    var offset = $input.offset();
                    dropdown.css({
                        top: offset.top + $input.outerHeight() - 2,
                        left: offset.left,
                        width: $input.outerWidth(),
                        display: 'block'
                    });
                    var html = '';
                    for(let i=0; i<historialBusquedas.length; i++){
                        html += renderItem(historialBusquedas[i]);
                    }
                    dropdown.html(html).show();
                }
            }
        });
        // Al hacer click en un elemento del historial, poner el valor en el input y disparar búsqueda
        $(document).on('click', '#historial-busquedas-dropdown .dropdown-item', function(e) {
            e.preventDefault();
            var valor = $(this).data('busqueda');
            var $input = $("input[type='search'][aria-controls='personas']");
            if ($input.length) {
                $input.val(valor).trigger('input').focus();
            }
            $('#historial-busquedas-dropdown').hide();
        });

        $(document).ready(function(){
            function bindBusquedaEvents() {
                const $inputSearch = $("input[type='search'][aria-controls='personas']");
                if(!$inputSearch.length) return;
                // Evitar múltiples binds
                $inputSearch.off('.historialBusqueda');
                let ignoreNextBlur = false;
                $inputSearch.on('mousedown.historialBusqueda', function(e) {
                    let oldValue = this.value;
                    setTimeout(() => {
                        if (this.value === '' && oldValue !== '') {
                            ignoreNextBlur = true;
                        }
                    }, 0);
                });
                $inputSearch.on('blur.historialBusqueda', function(e) {
                    console.log('blur input search');
                    if(ignoreNextBlur) {
                        console.log('Ignorado por clear X');
                        ignoreNextBlur = false;
                        return;
                    }
                    let termino = this.value.trim();
                    if(termino) {
                        console.log('Enviando búsqueda:', termino);
                        $.post("{{ route('busqueda.guardar') }}", {
                            busqueda: termino,
                            _token: '{{ csrf_token() }}'
                        }).done(function(resp){
                            console.log('Guardado OK', resp);
                        }).fail(function(xhr){
                            console.log('Error al guardar', xhr);
                        });
                    }
                });
                $inputSearch.on('focus.historialBusqueda', function(){
                    let dropdown = $('#historial-busquedas-dropdown');
                    if(dropdown.length){
                        var offset = $inputSearch.offset();
                        dropdown.css({
                            top: offset.top + $inputSearch.outerHeight() - 2,
                            left: offset.left,
                            width: $inputSearch.outerWidth(),
                            display: 'block'
                        });
                        dropdown.show();
                    }
                });
                $inputSearch.on('blur.historialBusqueda', function(){
                    setTimeout(()=>$('#historial-busquedas-dropdown').hide(),200);
                });
            }
            // Llamar al inicio y tras cada draw de DataTable
            bindBusquedaEvents();
            if ($.fn.dataTable) {
                $('#personas').on('draw.dt', function(){
                    bindBusquedaEvents();
                });
            }
        });
        </script>
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.0/themes/base/jquery-ui.css">
	<script src="https://code.jquery.com/ui/1.13.0/jquery-ui.min.js"></script>

    <script src="{{asset('dist/js/starrr.js')}}"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/piexif.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/plugins/sortable.min.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/fileinput.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/themes/fas/theme.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/5.1.5/js/locales/es.js"></script>

    <script src="{{ asset('ckeditor/ckeditor.js') }}"></script>
    <script src="{{asset('assets/js/observacion.js')}}"></script>
    <script src="{{asset('assets/js/eliminargenerico.js')}}"></script>
    <script src="{{asset('assets/js/mensajeAjax.js')}}"></script>
    <script src="{{asset('assets/js/informar.js')}}"></script>

    <script>
        let cantidadEsperados= "{{ $totalPendientesHome ?? 0 }}";
        function ensureModalCompatBindingsHome(el){
            if (!el || el.__modalCompatBoundHome) {
                return;
            }
            el.__modalCompatBoundHome = true;

            el.addEventListener('click', function(e){
                var dismissTarget = e.target.closest('[data-bs-dismiss="modal"], [data-dismiss="modal"], .btn-close, .close');
                if (dismissTarget && el.contains(dismissTarget)) {
                    e.preventDefault();
                    hideModalCompatHome(el);
                    return;
                }
                if (e.target === el) {
                    hideModalCompatHome(el);
                }
            });

            document.addEventListener('keydown', function(e){
                if (e.key === 'Escape' && el.classList.contains('show')) {
                    hideModalCompatHome(el);
                }
            });
        }
        function showModalCompatHome(selector){
            var el = (typeof selector === 'string') ? document.querySelector(selector) : selector;
            if (!el) {
                return;
            }
            ensureModalCompatBindingsHome(el);
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    if (!el.__bsModalInstance) {
                        el.__bsModalInstance = new window.bootstrap.Modal(el);
                    }
                    el.__bsModalInstance.show();
                    return;
                } catch (e) {}
            }
            if (window.jQuery && typeof window.jQuery(el).modal === 'function') {
                window.jQuery(el).modal('show');
                return;
            }
            el.style.display = 'block';
            el.classList.add('show');
            document.body.classList.add('modal-open');
        }
        function hideModalCompatHome(selector){
            var el = (typeof selector === 'string') ? document.querySelector(selector) : selector;
            if (!el) {
                return;
            }
            ensureModalCompatBindingsHome(el);
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    if (!el.__bsModalInstance) {
                        el.__bsModalInstance = new window.bootstrap.Modal(el);
                    }
                    el.__bsModalInstance.hide();
                    return;
                } catch (e) {}
            }
            if (window.jQuery && typeof window.jQuery(el).modal === 'function') {
                window.jQuery(el).modal('hide');
                return;
            }
            el.classList.remove('show');
            el.style.display = 'none';
            document.body.classList.remove('modal-open');
            $('.modal-backdrop').remove();
        }
        $(document).ready(function(){
            // Modal
            function cerrarModal() {
                var modal = document.getElementById("modal_estados");
                modal.style.display = "none";
                window.location.href = "espera/nuevo/view";
            }
            if(cantidadEsperados>0)
                showModalCompatHome('#modal_estados');
            $("#cerrarmodal").on("click", function(){
                if($('body').hasClass('modal-open')){
                    hideModalCompatHome('#modal_estados');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                }
            });
            $(".modal").on("hidden.bs.modal", function() {
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
            });

            // Guardar búsqueda al perder foco el input, excepto si se hace click en el botón X (clear)
            let ignoreNextBlur = false;
            // Selector específico para el input de búsqueda de DataTable
            const $inputSearch = $("input[type='search'][aria-controls='personas']");
            $inputSearch.on('mousedown', function(e) {
                let oldValue = this.value;
                setTimeout(() => {
                    if (this.value === '' && oldValue !== '') {
                        ignoreNextBlur = true;
                    }
                }, 0);
            });
            $inputSearch.on('blur', function(e) {
                console.log('blur input search');
                if(ignoreNextBlur) {
                    console.log('Ignorado por clear X');
                    ignoreNextBlur = false;
                    return;
                }
                let termino = this.value.trim();
                if(termino) {
                    console.log('Enviando búsqueda:', termino);
                    $.post("{{ route('busqueda.guardar') }}", {
                        busqueda: termino,
                        _token: '{{ csrf_token() }}'
                    }).done(function(resp){
                        console.log('Guardado OK', resp);
                    }).fail(function(xhr){
                        console.log('Error al guardar', xhr);
                    });
                }
            });

            // Mostrar dropdown de búsquedas recientes al hacer foco en el input
            $inputSearch.on('focus', function(){
                let dropdown = $('#historial-busquedas-dropdown');
                if(dropdown.length){
                    var offset = $inputSearch.offset();
                    dropdown.css({
                        top: offset.top + $inputSearch.outerHeight(),
                        left: offset.left,
                        display: 'block'
                    });
                }
            });
            $inputSearch.on('blur', function(){
                setTimeout(()=>$('#historial-busquedas-dropdown').hide(),200);
            });

            $(document).on('click', '.btn-copy-msg', function() {
                const mensaje = $(this).data('message') || '';
                if (!mensaje) {
                    return;
                }
                navigator.clipboard.writeText(mensaje).then(function() {
                    showMiniFeedback('success', 'Mensaje copiado');
                }).catch(function() {
                    showMiniFeedback('warning', 'No se pudo copiar automaticamente');
                });
            });

            $(document).on('click', '.btn-mark-managed', function() {
                const $btn = $(this);
                if ($btn.prop('disabled') || $btn.hasClass('disabled')) {
                    return;
                }
                const payload = {
                    persona_id: $btn.data('persona-id'),
                    accion: 'gestionado',
                    canal: 'whatsapp',
                    motivo: $btn.data('motivo') || null,
                    mensaje: $btn.data('message') || null,
                    _token: '{{ csrf_token() }}'
                };
                $.post(homeGestionUrl, payload).done(function() {
                    const $row = $btn.closest('tr');
                    $row.addClass('table-success');
                    $btn.removeClass('btn-outline-dark').addClass('btn-success');
                    $btn.prop('disabled', true).addClass('disabled');
                    const $state = $row.find('.managed-state').first();
                    $state.removeClass('d-none badge-light').addClass('badge-success').text('Gestionado');
                    showMiniFeedback('success', 'Gestion registrada');
                }).fail(function(xhr) {
                    showMiniFeedback('error', (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : 'No se pudo registrar la gestion');
                });
            });

            $(document).on('click', '.btn-posponer', function() {
                const $btn = $(this);
                const personaId = $btn.data('persona-id');
                if (!personaId) {
                    return;
                }

                Swal.fire({
                    title: 'Posponer contacto',
                    input: 'date',
                    inputLabel: 'Nueva fecha de contacto',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar'
                }).then(function(result) {
                    if (!result.isConfirmed || !result.value) {
                        return;
                    }
                    $.post(homeGestionUrl, {
                        persona_id: personaId,
                        accion: 'posponer',
                        canal: 'interno',
                        motivo: $btn.data('motivo') || null,
                        mensaje: $btn.data('message') || null,
                        vuelvefecha: result.value,
                        _token: '{{ csrf_token() }}'
                    }).done(function() {
                        showMiniFeedback('success', 'Fecha de seguimiento actualizada');
                    }).fail(function(xhr) {
                        showMiniFeedback('error', (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : 'No se pudo posponer');
                    });
                });
            });

            $(document).on('click', '.btn-cambiar-estado', function() {
                const $btn = $(this);
                const personaId = $btn.data('persona-id');
                if (!personaId || !homeCrmEstados.length) {
                    return;
                }
                const options = {};
                homeCrmEstados.forEach(function(e) {
                    options[e.id] = e.estado;
                });
                Swal.fire({
                    title: 'Cambiar estado comercial',
                    input: 'select',
                    inputOptions: options,
                    inputPlaceholder: 'Seleccione estado',
                    showCancelButton: true,
                    confirmButtonText: 'Actualizar'
                }).then(function(result) {
                    if (!result.isConfirmed || !result.value) {
                        return;
                    }
                    $.post(homeGestionUrl, {
                        persona_id: personaId,
                        accion: 'cambiar_estado',
                        estado_id: result.value,
                        canal: 'interno',
                        motivo: $btn.data('motivo') || null,
                        mensaje: $btn.data('message') || null,
                        _token: '{{ csrf_token() }}'
                    }).done(function() {
                        showMiniFeedback('success', 'Estado actualizado');
                    }).fail(function(xhr) {
                        showMiniFeedback('error', (xhr.responseJSON && xhr.responseJSON.mensaje) ? xhr.responseJSON.mensaje : 'No se pudo cambiar el estado');
                    });
                });
            });

            $(document).on('click', '.btn-open-contactos-whatsapp', function() {
                const $btn = $(this);
                const personaId = $btn.data('persona-id');
                const mensajeId = $btn.data('mensaje-id') || 5;
                const mensajeSugerido = $btn.data('message') || '';
                if (!personaId) {
                    showMiniFeedback('warning', 'No se encontro el cliente para cargar contactos');
                    return;
                }
                const urlContactos = "../persona/enviar/mensaje/componente";
                mostrarContactos(urlContactos, personaId, mensajeId, mensajeSugerido);
                if (typeof showModalCompatHome === 'function') {
                    showModalCompatHome('#modal-listar-contactos-component');
                } else {
                    $('#modal-listar-contactos-component').modal('show');
                }
            });
        });
    </script>
    
    {{-- telefono modales --}}
    <script src="{{asset('dist/js/moment.js')}}"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/locale/es.js"></script>
    {{-- js --}}
    <script type="text/javascript" src="{{asset('dist/js/jquery.leanModal.min.js')}}"></script>
    <script src="{{asset('assets/js/enviarmensaje/mostrarcontactos.js')}}"></script>
    <script src="{{asset('vistas/persona/estudiantes.js')}}"></script>
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('vistas/clase/asistencia.js') }}?v={{ is_file(public_path('vistas/clase/asistencia.js')) ? filemtime(public_path('vistas/clase/asistencia.js')) : 1 }}"></script>
@stop
