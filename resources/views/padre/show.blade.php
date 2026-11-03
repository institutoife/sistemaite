@extends('adminlte::page')

@section('title', 'Detalle del Estudiante')

@section('css')
    <style>
        :root {
            --padre-c1: #1f7a8c;
            --padre-c2: #eaf4f4;
            --padre-c3: #f8fafc;
        }
        .hero-padre {
            background: linear-gradient(135deg, #1f7a8c 0%, #2a9d8f 100%);
            border-radius: .9rem;
            color: #fff;
        }
        .kpi-card {
            border: 1px solid #dde3ea;
            border-radius: .8rem;
            background: #fff;
            height: 100%;
        }
        .kpi-label { color: #6b7280; font-size: .85rem; }
        .kpi-value { font-size: 1.35rem; font-weight: 700; color: #0f172a; }
        .section-card {
            border: 1px solid #e5e7eb;
            border-radius: .8rem;
            background: #fff;
        }
        .section-title { font-weight: 700; color: #0f172a; }
        .status-pill {
            border-radius: 999px;
            font-size: .73rem;
            padding: .2rem .55rem;
            font-weight: 600;
        }
        .status-ok { background: #d1fae5; color: #065f46; }
        .status-warn { background: #fef3c7; color: #92400e; }
        .status-danger { background: #fee2e2; color: #991b1b; }
        .status-neutral { background: #e5e7eb; color: #374151; }
        .compact-table th, .compact-table td { white-space: nowrap; font-size: .82rem; }
        .print-only { display: none; }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block; }
            .content-wrapper, .content { margin: 0 !important; padding: 0 !important; }
            .card, .section-card, .kpi-card { box-shadow: none !important; border: 1px solid #d1d5db !important; }
        }
    </style>
@stop

@section('content')
    <div class="container-fluid">
        <div class="hero-padre p-3 p-md-4 mb-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
                <div>
                    <h4 class="mb-1">{{ $hijo->nombre }} {{ $hijo->apellidop }} {{ $hijo->apellidom }}</h4>
                    <small>Vista privada para apoderados. Solo se muestran estudiantes vinculados a tu cuenta.</small>
                </div>
                <div class="mt-3 mt-lg-0 no-print">
                    <a href="{{ route('padre.home') }}" class="btn btn-light btn-sm mr-1"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
                    <button type="button" class="btn btn-outline-light btn-sm mr-1" onclick="window.print()"><i class="fas fa-print mr-1"></i>Imprimir</button>
                    <a href="{{ route('padre.hijos.pdf', $hijo->id) }}" class="btn btn-dark btn-sm mr-1"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                    <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-success btn-sm"><i class="fab fa-whatsapp mr-1"></i>WhatsApp</a>
                </div>
            </div>
        </div>

        <div class="print-only mb-2">
            <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}
        </div>

        @if($inscripciones->isEmpty() && $matriculaciones->isEmpty())
            <div class="alert alert-warning">No se encontraron inscripciones ni matriculaciones para este estudiante.</div>
        @endif

        @foreach($inscripciones as $item)
            <div class="section-card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h5 class="section-title mb-1">Inscripcion #{{ $item['id'] }} {{ $item['modalidad'] ? '- '.$item['modalidad'] : '' }}</h5>
                            <span class="status-pill {{ $item['vigente'] ? 'status-ok' : 'status-danger' }}">{{ $item['vigente'] ? 'Vigente' : 'No vigente' }}</span>
                            <span class="status-pill status-neutral">Estado: {{ $item['estado'] ?? 'N/D' }}</span>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="status-pill {{ $item['llego_hoy'] ? 'status-ok' : 'status-neutral' }}">Llego hoy: {{ $item['llego_hoy'] ? 'Si' : 'No' }}</span>
                            <span class="status-pill {{ $item['salio_hoy'] ? 'status-ok' : 'status-warn' }}">Salio hoy: {{ $item['salio_hoy'] ? 'Si' : 'No' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Asistencias</div><div class="kpi-value">{{ $item['asistencias'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Faltas</div><div class="kpi-value">{{ $item['faltas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Licencias</div><div class="kpi-value">{{ $item['licencias'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Programadas</div><div class="kpi-value">{{ $item['clases_programadas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Ya pasadas</div><div class="kpi-value">{{ $item['clases_pasadas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Pagado</div><div class="kpi-value">Bs {{ number_format($item['total_pagado'], 2) }}</div></div></div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-xl-6 mb-3">
                            <h6 class="mb-2">Historial de asistencias por fecha</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-striped mb-0 compact-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha-Dia</th>
                                            <th>Horario</th>
                                            <th>Docente/Aula</th>
                                            <th>Estado</th>
                                            <th>Materia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($item['historial'] as $h)
                                            @php
                                                $fechaHist = $h['fecha'] ? \Carbon\Carbon::parse($h['fecha']) : null;
                                                $fechaDia = $fechaHist ? ucfirst($fechaHist->locale('es')->isoFormat('D-M-YYYY-dddd')) : 'N/D';
                                                $horaIni = $h['hora_inicio'] ? \Carbon\Carbon::parse($h['hora_inicio'])->format('H:i') : 'N/D';
                                                $horaFin = $h['hora_fin'] ? \Carbon\Carbon::parse($h['hora_fin'])->format('H:i') : 'N/D';
                                            @endphp
                                            <tr>
                                                <td>{{ $fechaDia }}</td>
                                                <td>{{ $horaIni }}-{{ $horaFin }}</td>
                                                <td>{{ $h['docente'] ?? 'N/D' }}/{{ $h['aula'] ?? 'N/D' }}</td>
                                                <td>{{ $h['estado'] ?? 'N/D' }}<small class="d-block">{{ $h['relacion_clase']??'' }}</small></td>
                                                <td>{{ $h['materia'] ?? 'N/D' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">Sin historial</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12 col-xl-6 mb-3">
                            <h6 class="mb-2">Pagos e inscripcion vigente</h6>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Pago con</th>
                                            <th>Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($item['pagos'] as $pago)
                                            <tr>
                                                <td>{{ optional($pago->created_at)->format('d/m/Y H:i') }}</td>
                                                <td>Bs {{ number_format($pago->monto, 2) }}</td>
                                                <td>Bs {{ number_format($pago->pagocon, 2) }}</td>
                                                <td>Bs {{ number_format($pago->cambio, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">Sin pagos registrados</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><strong>Total pagado:</strong> Bs {{ number_format($item['total_pagado'], 2) }}</span>
                                <span><strong>Saldo:</strong> Bs {{ number_format($item['saldo'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @php
                        $diasAsistenciaIns = collect($item['planificacion'] ?? [])->pluck('dia')->filter()->unique()->values();
                    @endphp
                    <h6 class="mb-2">Dias de asistencias</h6>
                    <div class="alert alert-light border mb-0">
                        {{ $diasAsistenciaIns->isNotEmpty() ? $diasAsistenciaIns->implode(', ') : 'Sin dias planificados' }}
                    </div>
                </div>
            </div>
        @endforeach

        @foreach($matriculaciones as $item)
            <div class="section-card mb-3">
                <div class="card-body">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                        <div>
                            <h5 class="section-title mb-1">Matriculacion #{{ $item['id'] }} {{ $item['asignatura'] ? '- '.$item['asignatura'] : '' }}</h5>
                            <span class="status-pill {{ $item['vigente'] ? 'status-ok' : 'status-danger' }}">{{ $item['vigente'] ? 'Vigente' : 'No vigente' }}</span>
                            <span class="status-pill status-neutral">Estado: {{ $item['estado'] ?? 'N/D' }}</span>
                        </div>
                        <div class="mt-2 mt-lg-0">
                            <span class="status-pill {{ $item['llego_hoy'] ? 'status-ok' : 'status-neutral' }}">Llego hoy: {{ $item['llego_hoy'] ? 'Si' : 'No' }}</span>
                            <span class="status-pill {{ $item['salio_hoy'] ? 'status-ok' : 'status-warn' }}">Salio hoy: {{ $item['salio_hoy'] ? 'Si' : 'No' }}</span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Asistencias</div><div class="kpi-value">{{ $item['asistencias'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Faltas</div><div class="kpi-value">{{ $item['faltas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Licencias</div><div class="kpi-value">{{ $item['licencias'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Programadas</div><div class="kpi-value">{{ $item['clases_programadas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Ya pasadas</div><div class="kpi-value">{{ $item['clases_pasadas'] }}</div></div></div>
                        <div class="col-6 col-md-2 mb-2"><div class="kpi-card p-2"><div class="kpi-label">Pagado</div><div class="kpi-value">Bs {{ number_format($item['total_pagado'], 2) }}</div></div></div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-xl-6 mb-3">
                            <h6 class="mb-2">Historial de asistencias por fecha</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered table-striped mb-0 compact-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha-Dia</th>
                                            <th>Horario</th>
                                            <th>Docente/Aula</th>
                                            <th>Estado</th>
                                            <th>Materia</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($item['historial'] as $h)
                                            @php
                                                $fechaHistCom = $h['fecha'] ? \Carbon\Carbon::parse($h['fecha']) : null;
                                                $fechaDiaCom = $fechaHistCom ? ucfirst($fechaHistCom->locale('es')->isoFormat('D-M-YYYY-dddd')) : 'N/D';
                                                $horaIniCom = $h['hora_inicio'] ? \Carbon\Carbon::parse($h['hora_inicio'])->format('H:i') : 'N/D';
                                                $horaFinCom = $h['hora_fin'] ? \Carbon\Carbon::parse($h['hora_fin'])->format('H:i') : 'N/D';
                                            @endphp
                                            <tr>
                                                <td>{{ $fechaDiaCom }}</td>
                                                <td>{{ $horaIniCom }}-{{ $horaFinCom }}</td>
                                                <td>{{ $h['docente'] ?? 'N/D' }}/{{ $h['aula'] ?? 'N/D' }}</td>
                                                <td>{{ $h['estado'] ?? 'N/D' }}<small class="d-block">{{ $h['relacion_clase']??'' }}</small></td>
                                                <td>{{ $item['asignatura'] ?? 'N/D' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">Sin historial</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12 col-xl-6 mb-3">
                            <h6 class="mb-2">Pagos</h6>
                            <div class="table-responsive mb-2">
                                <table class="table table-sm table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Monto</th>
                                            <th>Pago con</th>
                                            <th>Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($item['pagos'] as $pago)
                                            <tr>
                                                <td>{{ optional($pago->created_at)->format('d/m/Y H:i') }}</td>
                                                <td>Bs {{ number_format($pago->monto, 2) }}</td>
                                                <td>Bs {{ number_format($pago->pagocon, 2) }}</td>
                                                <td>Bs {{ number_format($pago->cambio, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted">Sin pagos registrados</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><strong>Total pagado:</strong> Bs {{ number_format($item['total_pagado'], 2) }}</span>
                                <span><strong>Saldo:</strong> Bs {{ number_format($item['saldo'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @php
                        $diasAsistenciaMat = collect($item['planificacion'] ?? [])->pluck('dia')->filter()->unique()->values();
                    @endphp
                    <h6 class="mb-2">Dias de asistencias</h6>
                    <div class="alert alert-light border mb-0">
                        {{ $diasAsistenciaMat->isNotEmpty() ? $diasAsistenciaMat->implode(', ') : 'Sin dias planificados' }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@stop
