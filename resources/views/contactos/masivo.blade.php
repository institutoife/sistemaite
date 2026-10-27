@if ($bulkReady)
<div class="card card-outline card-primary">
    <div class="card-header"><h2 class="card-title">Sincronización masiva</h2></div>
    <div class="card-body">
        <p>Incorpore los teléfonos existentes de Personas, Prospectos y consultas web a la cuenta central. Los registros sin teléfono y los que requieren atención se conservan para revisión.</p>
        @if ($bulkRun)
            <p><strong>Lote #{{ $bulkRun->id }}:</strong>
                {{ $bulkRun->status === 'stopped' ? 'Preparación detenida' : ($bulkProgress['finished'] ? 'Finalizado' : ($bulkRun->status === 'preparing' ? 'Preparando por turnos' : 'Procesando contactos')) }}
            </p>
            <div class="row text-center">
                @foreach (['queued' => 'Incluidos', 'synced' => 'Sincronizados', 'pending' => 'Pendientes', 'errors' => 'Requieren atención'] as $key => $label)
                    <div class="col-6 col-md-3 mb-2"><strong class="h4 d-block">{{ $key === 'queued' ? $bulkRun->queued : $bulkProgress[$key] }}</strong>{{ $label }}</div>
                @endforeach
            </div>
            <p class="small text-muted">Revisados: {{ $bulkRun->scanned }}. Números inválidos omitidos: {{ $bulkRun->skipped }}. Fichas eliminadas o consultas convertidas: {{ $bulkProgress['removed'] }}. Actualice el estado para consultar el progreso.</p>
        @endif
        @can('Sincronizar Contactos Masivamente')
            @if (!$bulkRun || !$bulkRun->active_slot || $bulkProgress['finished'])
                <button type="button" class="btn btn-primary" @if(!$account || !$account->isConnected()) disabled @endif
                    onclick="document.getElementById('gcontact-bulk-dialog').showModal()">Sincronizar masivamente</button>
            @else
                <form method="POST" action="{{ route('contactos.bulk.stop', $bulkRun) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">Detener siguientes lotes</button>
                    <span class="small text-muted">Los contactos ya encolados continuarán; las altas y cambios diarios seguirán funcionando.</span>
                </form>
            @endif
            <dialog id="gcontact-bulk-dialog" aria-labelledby="gcontact-bulk-title" style="width:min(94vw,680px);border:0;border-radius:8px;padding:24px">
                <h2 class="h4" id="gcontact-bulk-title">Sincronizar teléfonos existentes</h2>
                <p>Se procesarán gradualmente mediante la tarea del servidor. Puede cerrar esta página.</p>
                <table class="table table-sm">
                    <thead><tr><th>Origen</th><th>Sin sincronización previa</th><th>Incluyendo sincronizados</th></tr></thead>
                    <tbody>
                        @foreach (['persona' => 'Personas', 'prospecto' => 'Prospectos', 'comentario' => 'Consultas web'] as $kind => $label)
                            <tr><td>{{ $label }}</td><td>{{ $bulkCounts[$kind]['new'] }}</td><td>{{ $bulkCounts[$kind]['all'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="small text-muted">Se cuentan fichas con teléfono registrado. Los números inválidos se omitirán al preparar cada turno. Se conservan los enlaces Google existentes y los contactos con coincidencias dudosas requieren revisión.</p>
                <form method="POST" action="{{ route('contactos.bulk.start') }}">
                    @csrf
                    <div class="form-group"><label for="gcontact-bulk-source">Origen</label>
                        <select id="gcontact-bulk-source" name="source" class="form-control">
                            <option value="all">Todos los orígenes</option><option value="persona">Personas</option>
                            <option value="prospecto">Prospectos</option><option value="comentario">Consultas web</option>
                        </select>
                    </div>
                    <div class="form-group"><label for="gcontact-bulk-limit">Cantidad máxima de este lote</label>
                        <select id="gcontact-bulk-limit" name="limit" class="form-control">
                            <option value="100">100</option><option value="500">500</option><option value="1000" selected>1.000</option><option value="0">Todos los existentes</option>
                        </select>
                    </div>
                    <label class="d-block"><input type="checkbox" name="include_synced" value="1"> También actualizar los contactos ya sincronizados.</label>
                    <p>Ritmo configurado: hasta {{ max(1, min(50, (int) config('google-contacts.cron_max_jobs', 3))) }} trabajos por turno, cada {{ config('google-contacts.cron_interval_minutes', 5) }} minutos. Las altas y cambios diarios comparten ese turno; un volumen grande puede tardar varios días.</p>
                    <label class="d-block mb-3"><input type="checkbox" name="confirm_bulk" value="1" required> Confirmo que deseo sincronizar este lote con la cuenta central.</label>
                    <button type="submit" class="btn btn-primary">Iniciar lote</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="this.closest('dialog').close()">Volver</button>
                </form>
            </dialog>
        @endcan
    </div>
</div>
@endif
