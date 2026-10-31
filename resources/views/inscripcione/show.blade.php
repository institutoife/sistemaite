@extends('adminlte::page')
@section('css')
    <link rel="stylesheet" href="{{asset('dist/css/bootstrap/bootstrap.css')}}">
    <link rel="stylesheet" href="{{asset('custom/css/custom.css')}}">
    <style>
        :root {
            --primary-color: #26BAA5;
            --secondary-color: #375F7A;
        }
        .insc-card-label { font-size: 0.75rem; letter-spacing: .02em; text-transform: uppercase; color: #6c757d; }
        .insc-card-value { font-size: 0.98rem; font-weight: 600; color: var(--secondary-color); }
        .insc-card { border: 1px solid #e9ecef; border-radius: .65rem; padding: .75rem .9rem; }
        .insc-card + .insc-card { margin-top: .6rem; }
        .insc-chip { font-size: .75rem; padding: .2rem .5rem; border-radius: 999px; }
        .insc-meta { font-size: .8rem; color: var(--secondary-color); opacity: .9; }
        .insc-btn-primary { background: var(--primary-color); border-color: var(--primary-color); color: #fff; }
        .insc-btn-secondary { background: var(--secondary-color); border-color: var(--secondary-color); color: #fff; }
        .insc-btn-outline { border-color: var(--primary-color); color: var(--primary-color); }
        .insc-btn-outline:hover { background: var(--primary-color); color: #fff; }
        .insc-summary { background: #f8fafb; border: 1px solid #e9ecef; border-radius: .75rem; }
        .insc-summary .insc-card-label { color: var(--secondary-color); }
        .insc-summary .insc-card-value { color: var(--primary-color); }
        .insc-table thead { background: var(--secondary-color); color: #fff; }
        .insc-row-danger { background-color: rgba(220, 53, 69, 0.15) !important; }
        .insc-row-primary { background-color: rgba(38, 186, 165, 0.18) !important; }
        .insc-row-secondary { background-color: rgba(55, 95, 122, 0.12) !important; }
        .insc-acc-header { border-left: 4px solid var(--primary-color); background: #f8fafb; }
        .insc-acc-header.insc-row-danger { border-left-color: rgba(220, 53, 69, 0.6); }
        .insc-acc-header.insc-row-secondary { border-left-color: rgba(55, 95, 122, 0.6); }
        .insc-acc-header.insc-row-primary { border-left-color: rgba(38, 186, 165, 0.8); }
        .insc-badge-primary { background: var(--primary-color); color: #fff; }
        .insc-badge-secondary { background: var(--secondary-color); color: #fff; }
    </style>
@stop

@section('title', 'Detalle de Inscripción')
@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                            <span class="card-title mb-2 mb-md-0"><i class="fas fa-id-card mr-2"></i>Detalle de inscripción</span>
                            <span class="badge badge-light">ID #{{$inscripcione->id}}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-block d-md-none">
                            <div class="insc-card">
                                <div class="insc-card-label">Estudiante</div>
                                <div class="insc-card-value">{{$inscripcione->estudiante->persona->nombre.' '.$inscripcione->estudiante->persona->apellidop.' '.$inscripcione->estudiante->persona->apellidom}}</div>
                                <div class="insc-meta mt-1">Modalidad: {{$inscripcione->modalidad->modalidad}}</div>
                            </div>
                            <div class="insc-card">
                                <div class="insc-card-label">Vigencia</div>
                                <div class="d-flex align-items-center flex-wrap">
                                    @if($inscripcione->vigente==1)
                                        <span class="badge badge-success insc-chip mr-2">Vigente</span>
                                    @else
                                        <span class="badge badge-danger insc-chip mr-2">No vigente</span>
                                    @endif
                                    @if($inscripcione->condonado==1)
                                        <span class="badge badge-warning insc-chip">Condonado</span>
                                    @else
                                        <span class="badge badge-secondary insc-chip">Sin condonar</span>
                                    @endif
                                </div>
                            </div>
                            <div class="insc-card">
                                <div class="insc-card-label">Fechas</div>
                                <div class="insc-card-value">{{$inscripcione->fechaini}} → {{$inscripcione->fechafin}}</div>
                                <div class="insc-meta mt-1">Total horas: {{$inscripcione->totalhoras}}</div>
                            </div>
                            <div class="insc-card">
                                <div class="insc-card-label">Objetivo</div>
                                <div class="insc-card-value">{!!$inscripcione->objetivo!!}</div>
                            </div>
                            <div class="insc-card">
                                <div class="insc-card-label">Registro</div>
                                <div class="insc-meta">Creado: {{$inscripcione->created_at}}</div>
                                <div class="insc-meta">Actualizado: {{$inscripcione->updated_at}}</div>
                            </div>
                            @isset($user)
                                <div class="insc-card">
                                    <div class="insc-card-label">Usuario</div>
                                    <div class="d-flex align-items-center">
                                        <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail border-primary" style="width: 44px; height: 44px; object-fit: cover;">
                                        <div class="ml-2 insc-card-value">{{$user->name}}</div>
                                    </div>
                                </div>
                            @endisset
                        </div>

                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-sm table-striped mb-0"> 
                                <tbody>
                                    <tr>
                                        <td class="font-weight-bold">ID</td>
                                        <td>{{$inscripcione->id}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">FECHA INICIO</td>
                                        <td>{{$inscripcione->fechaini}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">FECHA FIN</td>
                                        <td>{{$inscripcione->fechafin}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">TOTAL HORAS</td>
                                        <td>{{$inscripcione->totalhoras}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">VIGENTE</td>
                                        <td>
                                            @if($inscripcione->vigente==1)
                                                <span class="badge badge-success">Vigente</span>
                                            @else
                                                <span class="badge badge-danger">No vigente</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">CONDONADO</td>
                                        <td>
                                            @if($inscripcione->condonado==1)
                                                <span class="badge badge-warning">Condonado</span>
                                            @else
                                                <span class="badge badge-secondary">No</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">OBJETIVO</td>
                                        <td>{!!$inscripcione->objetivo!!}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">ESTUDIANTE</td>
                                        <td>{{$inscripcione->estudiante->persona->nombre.' '.$inscripcione->estudiante->persona->apellidop.' '.$inscripcione->estudiante->persona->apellidom}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Modalidad</td>
                                        <td>{{$inscripcione->modalidad->modalidad}}</td>
                                    </tr>
                                    
                                    <tr>
                                        <td class="font-weight-bold">Creado</td>
                                        <td>{{$inscripcione->created_at}}</td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Actualizado</td>
                                        <td>{{$inscripcione->updated_at}}</td>
                                    </tr>

                                    <tr>
                                        <td class="font-weight-bold">Usuario</td>
                                        <td>
                                            @isset($user)
                                                <div class="d-flex align-items-center">
                                                    <img data-profile-photo  src="{{app(\App\Services\ProfilePhotoService::class)->forUser($user)}}" alt="{{$user->name}}" class="rounded img-thumbnail border-primary" style="width: 48px; height: 48px; object-fit: cover;">
                                                    <div class="ml-2">{{$user->name}}</div>
                                                </div>
                                            @endisset
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @php
        $qrLocal = asset('images/qr.jpg');
    @endphp
    <section class="content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="background: #26BAA5; color: #fff;">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                            <span class="card-title"><i class="fas fa-wallet mr-2"></i>Pagos de la inscripción</span>
                            <div class="text-white-50">Resumen y detalle de pagos</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-8">
                                <div class="insc-summary p-3 mb-3">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="insc-card-label">Total a pagar</div>
                                            <div class="insc-card-value">{{$inscripcione->costo}} Bs.</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="insc-card-label">Acuenta</div>
                                            <div class="insc-card-value">{{$acuenta}} Bs.</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="insc-card-label">Saldo</div>
                                            <div class="insc-card-value">{{$saldo}} Bs.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-block d-md-none">
                                    @forelse($pagos as $pago)
                                        <div class="insc-card">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="insc-card-value">{{$pago->monto}} Bs.</div>
                                                <span class="insc-chip badge badge-light">{{ $pago->created_at->format('d/m/Y') }}</span>
                                            </div>
                                            <div class="insc-meta mt-1">Pagó con: {{$pago->pagocon}} Bs. • Cambio: {{$pago->cambio}} Bs.</div>
                                            <div class="insc-meta">{{ optional($pago->usuarios->first())->name ?? 'Sistema' }}</div>
                                        </div>
                                    @empty
                                        <p class="text-muted mb-0">No hay pagos registrados.</p>
                                    @endforelse
                                </div>

                                <div class="table-responsive d-none d-md-block">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Monto</th>
                                                <th>Pagó con</th>
                                                <th>Cambio</th>
                                                <th>Usuario</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($pagos as $pago)
                                                <tr>
                                                    <td>{{$pago->monto}} Bs.</td>
                                                    <td>{{$pago->pagocon}} Bs.</td>
                                                    <td>{{$pago->cambio}} Bs.</td>
                                                    <td>{{ optional($pago->usuarios->first())->name ?? 'Sistema' }}</td>
                                                    <td>{{ $pago->created_at->format('d/m/Y H:i') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No hay pagos registrados.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                @if($saldo > 0)
                                    <div class="insc-card text-center">
                                        <div class="insc-card-label">QR para pago</div>
                                        <img src="{{$qrLocal}}" alt="QR de pago" class="img-thumbnail my-2" style="max-width: 200px;">
                                        <div class="insc-meta">Guarda el QR para escanear desde la galería.</div>
                                        <a class="btn insc-btn-outline btn-block mt-2" href="{{$qrLocal}}" download>
                                            <i class="fas fa-download mr-1"></i>Descargar QR
                                        </a>
                                    </div>
                                @else
                                    <div class="insc-card text-center">
                                        <div class="insc-card-label">Estado</div>
                                        <div class="insc-card-value" style="color: var(--primary-color);">PAGADO</div>
                                        <div class="insc-meta">No tienes saldo pendiente.</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="background: #375F7A; color: #fff;">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                            <span class="card-title mb-2 mb-md-0"><i class="far fa-calendar-alt mr-2"></i>Programación de clases</span>
                            <a class="btn btn-primary" href="{{ route('imprimir.programa',$inscripcione) }}">Imprimir</a>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="programacionAccordion">
                            @foreach ($programacion as $programa)
                                @php
                                    $claseActual = $programa->clases->sortByDesc('fecha')->first();
                                    $estadoNombre = optional(optional($claseActual)->estado)->estado;
                                    $estadoLabel = $estadoNombre ?: 'PROGRAMADO';
                                    $estadoBadge = 'badge-secondary';
                                    $estadoRow = '';
                                    switch ($estadoNombre) {
                                        case 'PRESENTE':
                                            $estadoBadge = 'insc-badge-primary';
                                            $estadoRow = 'insc-row-primary';
                                            break;
                                        case 'FINALIZADO':
                                            $estadoBadge = 'insc-badge-secondary';
                                            $estadoRow = 'insc-row-secondary';
                                            break;
                                        case 'FALTANOTIFICADA':
                                        case 'FALTA':
                                        case 'AUSENTE':
                                            $estadoBadge = 'badge-danger';
                                            $estadoRow = 'insc-row-danger';
                                            break;
                                        case 'LICENCIA':
                                            $estadoBadge = 'badge-warning';
                                            $estadoRow = 'insc-row-secondary';
                                            break;
                                        default:
                                            $estadoBadge = 'badge-info';
                                            $estadoRow = '';
                                            break;
                                    }
                                    $docenteNombre = optional($programa->docente->persona)->nombre;
                                    $docenteApellido = optional($programa->docente->persona)->apellidop;
                                @endphp
                                <div class="card mb-2">
                                    <div class="card-header insc-acc-header {{$estadoRow}}" id="heading-{{$programa->id}}">
                                        <button class="btn btn-link w-100 text-left p-0" data-toggle="collapse" data-target="#collapse-{{$programa->id}}" aria-expanded="false" aria-controls="collapse-{{$programa->id}}">
                                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                                                <div>
                                                    <div class="insc-card-value">{{$programa->fecha->isoFormat('DD/MM/YYYY')}} • {{$programa->hora_ini->isoFormat('HH:mm')}}-{{$programa->hora_fin->isoFormat('HH:mm')}}</div>
                                                    <div class="insc-meta">{{$programa->materia->materia ?? 'Materia'}} • Aula: {{$programa->aula->aula ?? 'N/D'}} • Docente: {{$docenteNombre}} {{$docenteApellido}}</div>
                                                </div>
                                                <span class="badge {{$estadoBadge}} insc-chip mt-2 mt-md-0">{{$estadoLabel}}</span>
                                            </div>
                                        </button>
                                    </div>
                                    <div id="collapse-{{$programa->id}}" class="collapse" aria-labelledby="heading-{{$programa->id}}" data-parent="#programacionAccordion">
                                        <div class="card-body">
                                            @if($programa->clases->isEmpty())
                                                <p class="text-muted mb-0">Aún no hay clases registradas para esta programación.</p>
                                            @else
                                                <div class="table-responsive d-none d-md-block">
                                                    <table class="table table-bordered table-hover mb-0">
                                                        <thead class="bg-light">
                                                            <tr>
                                                                <th>Fecha</th>
                                                                <th>Horario</th>
                                                                <th>Estado</th>
                                                                <th>Docente</th>
                                                                <th>Aula</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($programa->clases as $clase)
                                                                @php
                                                                    $estadoClase = optional($clase->estado)->estado;
                                                                    $claseBadge = 'badge-secondary';
                                                                    $claseRow = '';
                                                                    switch ($estadoClase) {
                                                                        case 'PRESENTE':
                                                                            $claseBadge = 'insc-badge-primary';
                                                                            $claseRow = 'insc-row-primary';
                                                                            break;
                                                                        case 'FINALIZADO':
                                                                            $claseBadge = 'insc-badge-secondary';
                                                                            $claseRow = 'insc-row-secondary';
                                                                            break;
                                                                        case 'FALTANOTIFICADA':
                                                                        case 'FALTA':
                                                                        case 'AUSENTE':
                                                                            $claseBadge = 'badge-danger';
                                                                            $claseRow = 'insc-row-danger';
                                                                            break;
                                                                        case 'LICENCIA':
                                                                            $claseBadge = 'badge-warning';
                                                                            $claseRow = 'insc-row-secondary';
                                                                            break;
                                                                    }
                                                                    $claseDocente = optional($clase->docente->persona)->nombre;
                                                                    $claseDocenteAp = optional($clase->docente->persona)->apellidop;
                                                                @endphp
                                                                <tr class="{{$claseRow}}">
                                                                    <td>{{$clase->fecha->isoFormat('DD/MM/YYYY')}}</td>
                                                                    <td>{{$clase->horainicio->isoFormat('HH:mm')}}-{{$clase->horafin->isoFormat('HH:mm')}}</td>
                                                                    <td><span class="badge {{$claseBadge}}">{{$estadoClase ?? 'SIN ESTADO'}}</span></td>
                                                                    <td>{{$claseDocente}} {{$claseDocenteAp}}</td>
                                                                    <td>{{$clase->aula->aula ?? 'N/D'}}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="d-block d-md-none">
                                                    @foreach($programa->clases as $clase)
                                                        @php
                                                            $estadoClase = optional($clase->estado)->estado;
                                                            $claseBadge = 'badge-secondary';
                                                            $claseRow = '';
                                                            switch ($estadoClase) {
                                                                case 'PRESENTE':
                                                                    $claseBadge = 'insc-badge-primary';
                                                                    $claseRow = 'insc-row-primary';
                                                                    break;
                                                                case 'FINALIZADO':
                                                                    $claseBadge = 'insc-badge-secondary';
                                                                    $claseRow = 'insc-row-secondary';
                                                                    break;
                                                                case 'FALTANOTIFICADA':
                                                                case 'FALTA':
                                                                case 'AUSENTE':
                                                                    $claseBadge = 'badge-danger';
                                                                    $claseRow = 'insc-row-danger';
                                                                    break;
                                                                case 'LICENCIA':
                                                                    $claseBadge = 'badge-warning';
                                                                    $claseRow = 'insc-row-secondary';
                                                                    break;
                                                            }
                                                            $claseDocente = optional($clase->docente->persona)->nombre;
                                                            $claseDocenteAp = optional($clase->docente->persona)->apellidop;
                                                        @endphp
                                                        <div class="insc-card {{$claseRow}}">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <div class="insc-card-label">{{$clase->fecha->isoFormat('DD/MM/YYYY')}}</div>
                                                                <span class="badge {{$claseBadge}} insc-chip">{{$estadoClase ?? 'SIN ESTADO'}}</span>
                                                            </div>
                                                            <div class="insc-card-value mt-1">{{$clase->horainicio->isoFormat('HH:mm')}}-{{$clase->horafin->isoFormat('HH:mm')}}</div>
                                                            <div class="insc-meta">Docente: {{$claseDocente}} {{$claseDocenteAp}} • Aula: {{$clase->aula->aula ?? 'N/D'}}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
   
@endsection
