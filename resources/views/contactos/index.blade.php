@extends('adminlte::page')

@section('title', 'Google Contactos')

@section('content_header')
    <h1>Google Contactos</h1>
@stop

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
    @endif
    @foreach (['success' => 'success', 'error' => 'danger'] as $key => $color)
        @if (session($key))
            <div class="alert alert-{{ $color }}" role="alert">{{ session($key) }}</div>
        @endif
    @endforeach

    <div class="card">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
            <div class="mb-2 mr-3">
                <strong>Cuenta central de Google</strong><br>
                @if ($account && $account->isConnected())
                    <span class="badge badge-success">Conectado</span>
                    <span>{{ $account->email }}</span>
                @else
                    <span class="badge badge-warning">Requiere conexión</span>
                @endif
            </div>
            @can('Configurar Google Contacts')
                <div class="d-flex flex-wrap">
                    <a href="{{ route('signIn') }}" class="btn btn-primary mb-2 mr-2">{{ $account ? 'Reconectar Google' : 'Conectar Google' }}</a>
                    @if ($account && $account->isConnected())
                        <form method="POST" action="{{ route('contactos.disconnect') }}">
                            @csrf
                            <button class="btn btn-outline-secondary mb-2" type="submit">Desconectar de IFE</button>
                        </form>
                    @endif
                </div>
            @endcan
        </div>
    </div>

    @if (!$ready)
        <div class="alert alert-info">La configuración de Google Contacts está pendiente de aplicar en el servidor.</div>
    @endif

    <div class="card card-outline card-{{ $account && $account->isPaused() ? 'warning' : 'info' }}">
        <div class="card-header"><h2 class="card-title">Sincronización compartida</h2></div>
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2 mr-3">
                    <span class="badge badge-{{ !$account ? 'secondary' : ($account->isPaused() ? 'warning' : 'success') }}">{{ !$account ? 'Sin configurar' : ($account->isPaused() ? 'Pausada para todos' : 'Habilitada') }}</span>
                    <span class="badge badge-{{ $worker['healthy'] ? 'success' : 'secondary' }}">{{ $worker['mode'] === 'cron' ? ($worker['healthy'] ? 'Tarea programada al día' : 'Tarea programada por revisar') : ($worker['online'] ? 'Proceso en línea' : 'Sin señal del proceso') }}</span>
                    @if ($worker['last_seen_at'])
                        <div class="small text-muted mt-1">Última señal: {{ \Carbon\Carbon::createFromTimestamp($worker['last_seen_at'], 'America/La_Paz')->format('d/m/Y H:i:s') }}</div>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center mb-2">
                    @role('Admin')
                        @if ($ready && $account)
                            <form method="POST" action="{{ $account->isPaused() ? route('contactos.resume') : route('contactos.pause') }}" class="mr-2 mb-2">
                                @csrf
                                <button type="submit" class="btn btn-{{ $account->isPaused() ? 'success' : 'outline-warning' }}">{{ $account->isPaused() ? 'Reanudar sincronización' : 'Pausar sincronización' }}</button>
                            </form>
                        @endif
                    @endrole
                    <a href="{{ request()->fullUrl() }}" class="btn btn-outline-secondary mb-2">Actualizar estado</a>
                </div>
            </div>
            <p class="small text-muted">Todos los usuarios de esta instalación comparten la cuenta central y el mismo proceso. La señal indica actividad del servidor; la autorización de Google se muestra arriba.</p>
            @if ($account && $account->isPaused())
                <div class="alert alert-warning mb-3" role="status">La sincronización está pausada por un administrador. Puede seguir guardando cambios y solicitando contactos: quedarán pendientes. Una operación que ya estaba en curso terminará.</div>
            @elseif (!$worker['healthy'])
                <div class="alert alert-info mb-3" role="status">{{ $worker['mode'] === 'cron' ? 'La tarea programada del servidor necesita revisión.' : 'No hay una señal reciente del proceso.' }} Los cambios se conservan pendientes hasta que el procesamiento del servidor vuelva a estar disponible.</div>
            @elseif ($worker['mode'] === 'cron')
                <p class="small text-muted">Los contactos se procesan por turnos en las ejecuciones programadas del servidor. No necesita mantener abierto este panel.</p>
            @endif
            <div class="row text-center" aria-label="Estado general de contactos">
                @foreach (['pending' => 'Pendientes', 'processing' => 'Procesando', 'synced' => 'Sincronizados', 'error' => 'Requieren atención'] as $key => $label)
                    <div class="col-6 col-md-3 mb-2"><strong class="h4 d-block mb-1">{{ $counts[$key] }}</strong><span>{{ $label }}</span></div>
                @endforeach
            </div>
            <p class="small text-muted mb-0">Los totales corresponden a todos los contactos elegibles. Los casos que requieren atención no se reintentan automáticamente al reanudar.</p>
        </div>
    </div>

    @include('contactos.masivo')

    <div class="card">
        <div class="card-header"><h2 class="card-title">Contactos del sistema</h2></div>
        <div class="card-body">
            <nav class="nav nav-pills mb-3" aria-label="Origen de los contactos">
                @foreach (['persona' => 'Personas', 'prospecto' => 'Prospectos', 'comentario' => 'Consultas web'] as $kind => $label)
                    <a class="nav-link {{ $source === $kind ? 'active' : '' }}" href="{{ route('contactos.index', ['source' => $kind]) }}" @if($source === $kind) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ route('contactos.index') }}" class="row mb-3">
                <input type="hidden" name="source" value="{{ $source }}">
                <div class="col-md-6 mb-2">
                    <label for="contact-search">Buscar</label>
                    <input id="contact-search" name="q" class="form-control" maxlength="100" value="{{ request('q') }}" placeholder="Nombre, apellido, código o teléfono">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="contact-status">Estado</label>
                    <select id="contact-status" name="status" class="form-control">
                        <option value="">Todos</option>
                        @foreach (['idle' => 'Sin verificar', 'pending' => 'Pendiente', 'processing' => 'Procesando', 'synced' => 'Sincronizado', 'retrying' => 'Reintentando', 'error' => 'Requiere atención'] as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2 d-flex align-items-end"><button class="btn btn-primary btn-block" type="submit">Filtrar</button></div>
                <div class="col-12 mt-1">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="without_phone" id="without-phone" value="1" {{ request()->boolean('without_phone') ? 'checked' : '' }}>
                        <label class="form-check-label" for="without-phone">Solo registros sin teléfono</label>
                    </div>
                </div>
            </form>
            <p class="text-muted">Se incluyen personas de todos los papeles, prospectos y consultas web. Las fichas de persona incorporan su código IFE, parentescos, servicios, cumpleaños y fotografía propia cuando estén disponibles. Si faltan teléfono, cumpleaños o fotografía, se conservan los datos que ya existan en Google.</p>
            @if ($source === 'comentario')
                <p class="small text-muted">Las consultas vinculadas a una persona se sincronizan desde la ficha de esa persona y conservan su contacto Google cuando existe un enlace comprobable.</p>
            @endif
            <div class="row">
                @forelse ($personas as $persona)
                    @php
                        $status = $persona->gcontact_sync_pending && $persona->gcontact_sync_status === 'idle' ? 'pending' : ($persona->gcontact_sync_status ?: 'idle');
                        $labels = ['idle' => 'Sin verificar', 'pending' => 'Pendiente', 'processing' => 'Procesando', 'synced' => 'Sincronizado', 'retrying' => 'Reintentando', 'error' => 'Requiere atención'];
                        $colors = ['synced' => 'success', 'error' => 'danger', 'processing' => 'info', 'retrying' => 'warning'];
                        $photoWarning = $status === 'synced' && in_array($persona->gcontact_error_code, ['invalid_photo', 'photo_extension'], true);
                        if ($photoWarning) { $labels['synced'] = 'Sincronizado · revisar foto'; $colors['synced'] = 'warning'; }
                    @endphp
                    <div class="col-12 col-lg-6">
                        <article class="card card-outline card-{{ $colors[$status] ?? 'secondary' }}">
                            <div class="card-body">
                                <h3 class="h5">{{ $source === 'prospecto' ? 'Prospecto #'.$persona->id : (trim($persona->nombre.' '.$persona->apellidop.' '.$persona->apellidom) ?: 'Consulta #'.$persona->id) }}</h3>
                                <p class="mb-1">Código: {{ $persona->id }} · {{ app(\App\Services\TelefonoService::class)->isMissing($persona->telefono) ? 'Sin teléfono' : $persona->telefono }}</p>
                                <p class="mb-2">
                                    @if ($source === 'persona')
                                    @if ($persona->estudiante || $persona->computacion)<span class="badge badge-light">Estudiante</span>@endif
                                    @if ($persona->hijos->isNotEmpty())<span class="badge badge-light">Apoderado</span>@endif
                                    @if ($persona->docente)<span class="badge badge-light">Docente</span>@endif
                                    @if ($persona->administrativo)<span class="badge badge-light">Administrativo</span>@endif
                                    @if ($persona->cliservicio)<span class="badge badge-light">Cliente de servicios</span>@endif
                                    @if (!$persona->estudiante && !$persona->computacion && !$persona->docente && !$persona->administrativo && !$persona->cliservicio && $persona->hijos->isEmpty())
                                        <span class="badge badge-light">{{ $persona->papelinicial === 'estudiante' ? 'Contacto / potencial' : ucfirst($persona->papelinicial ?: 'Persona') }}</span>
                                    @endif
                                    @else
                                        <span class="badge badge-light">{{ $source === 'prospecto' ? 'Prospecto' : 'Consulta web' }}</span>
                                    @endif
                                    <span class="badge badge-{{ $colors[$status] ?? 'secondary' }}">{{ $labels[$status] ?? 'Pendiente' }}</span>
                                </p>
                                @if ($source === 'persona' && $persona->hijos->isNotEmpty())
                                    <p class="small mb-2">@foreach ($persona->hijos as $hijo){{ $loop->first ? '' : '; ' }}{{ $hijo->nombre }} {{ $hijo->apellidop }} ({{ $hijo->id }})@endforeach</p>
                                @endif
                                @if ($persona->gcontact_synced_at)
                                    <p class="small text-muted mb-2">Última sincronización: {{ \Carbon\Carbon::parse($persona->gcontact_synced_at)->format('d/m/Y H:i') }}</p>
                                @endif
                                @if ($persona->gcontact_sync_error)
                                    <p class="small text-{{ $photoWarning ? 'warning' : 'danger' }}">{{ $persona->gcontact_error_code ? $persona->gcontact_sync_error : 'Existe un error previo. Solicite una nueva comprobación del contacto.' }}</p>
                                @endif
                                @can('Ver Diagnostico Google')
                                    @if ($persona->resourseName || $persona->gcontact_error_code)
                                        <details class="small mb-2"><summary>Diagnóstico</summary>
                                            <div style="overflow-wrap:anywhere">{{ $persona->resourseName }} {{ $persona->gcontact_error_code }}</div>
                                        </details>
                                    @endif
                                @endcan
                                @if ($ready)
                                    @can('Sincronizar Contactos')
                                        <form method="POST" action="{{ route(['persona' => 'contactos.sync', 'prospecto' => 'contactos.sync-prospecto', 'comentario' => 'contactos.sync-comentario'][$source], $persona) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-primary btn-sm">{{ $status === 'error' ? 'Reintentar' : ($status === 'synced' ? 'Resincronizar' : 'Solicitar sincronización') }}</button>
                                        </form>
                                    @endcan
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <p class="col-12 text-muted">No hay contactos para estos filtros.</p>
                @endforelse
            </div>
            {{ $personas->links() }}
        </div>
    </div>
@stop
