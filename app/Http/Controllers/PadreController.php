<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Support\SedeDB as DB;
use PDF;

class PadreController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function home()
    {
        $this->ensurePadreRole();

        $persona = Auth::user()->persona;
        $hijos = collect();

        if ($persona) {
            $hijos = $persona->hijos()
                ->when(!Auth::user()->hasRole('Padre'),fn($q)=>$q->whereRaw('1=0'))
                ->with(['estudiante.inscripciones', 'computacion.matriculaciones'])
                ->get();

            if ($hijos->isEmpty() && ($persona->estudiante || $persona->computacion)) {
                $hijos = collect([$persona]);
            }
        }

        return view('padre.home', compact('persona', 'hijos'));
    }

    public function show($hijoId)
    {
        $padrePersona = $this->getPadrePersona();
        $hijo = $this->resolveAuthorizedChild($padrePersona, $hijoId);
        $data = $this->buildChildData($hijo);

        return view('padre.show', array_merge([
            'padrePersona' => $padrePersona,
            'hijo' => $hijo,
        ], $data));
    }

    public function downloadResumenPdf($hijoId)
    {
        $padrePersona = $this->getPadrePersona();
        $hijo = $this->resolveAuthorizedChild($padrePersona, $hijoId);
        $data = $this->buildChildData($hijo);

        $pdf = PDF::loadView('padre.resumen_pdf', array_merge([
            'padrePersona' => $padrePersona,
            'hijo' => $hijo,
        ], $data));

        $pdf->setPaper('letter', 'portrait');
        $fecha = Carbon::now()->format('Ymd_His');
        $nombre = trim(($hijo->nombre ?? '').' '.($hijo->apellidop ?? ''));
        $nombre = str_replace(' ', '_', $nombre);

        return $pdf->download("resumen_hijo_{$hijo->id}_{$nombre}_{$fecha}.pdf");
    }

    private function ensurePadreRole()
    {
        if (!Auth::check() || !Auth::user()->hasRole(['Padre','Estudiante'])) {
            abort(403);
        }
    }

    private function getPadrePersona()
    {
        $this->ensurePadreRole();
        $persona = Auth::user()->persona;

        if (!$persona) {
            abort(403, 'No existe una persona asociada al usuario autenticado.');
        }

        return $persona;
    }

    private function resolveAuthorizedChild(Persona $padrePersona, $hijoId)
    {
        $hijo = $padrePersona->hijos()
            ->when(!Auth::user()->hasRole('Padre'),fn($q)=>$q->whereRaw('1=0'))
            ->where('personas.id', $hijoId)
            ->first();

        if (!$hijo && (int) $padrePersona->id === (int) $hijoId && ($padrePersona->estudiante || $padrePersona->computacion)) {
            $hijo = $padrePersona;
        }

        if (!$hijo) {
            abort(403, 'No tienes autorización para consultar ese estudiante.');
        }

        return $hijo->load([
            'estudiante',
            'computacion',
        ]);
    }

    private function buildChildData(Persona $hijo)
    {
        $today = Carbon::today();
        $estadoFinalizado = estado('FINALIZADO');
        $estadoPresente = estado('PRESENTE');
        $estadoFalta = estado('FALTA');
        $estadoFaltaNotificada = estado('FALTANOTIFICADA');
        $estadoLicencia = estado('LICENCIA');
        $estadoIndefinido = estado('INDEFINIDO');

        $hijo->load([
            'estudiante.inscripciones.estado',
            'estudiante.inscripciones.modalidad',
            'estudiante.inscripciones.programaciones.estado',
            'estudiante.inscripciones.programaciones.materia',
            'estudiante.inscripciones.programaciones.docente.persona:id,nombre,apellidop,apellidom,foto',
            'estudiante.inscripciones.programaciones.aula',
            'estudiante.inscripciones.pagos',
            'computacion.matriculaciones.estado',
            'computacion.matriculaciones.asignatura',
            'computacion.matriculaciones.programacionescom.estado',
            'computacion.matriculaciones.programacionescom.docente.persona:id,nombre,apellidop,apellidom,foto',
            'computacion.matriculaciones.programacionescom.aula',
            'computacion.matriculaciones.pagos',
        ]);

        $inscripciones = collect();
        $matriculaciones = collect();
        $sesionesPorInscripcion = collect();
        $sesionesPorMatriculacion = collect();

        if ($hijo->estudiante) {
            $inscripcionIds = $hijo->estudiante->inscripciones->pluck('id')->filter()->values();

            if ($inscripcionIds->isNotEmpty()) {
                $sesionesPorInscripcion = DB::table('sesions')
                    ->join('dias', 'dias.id', '=', 'sesions.dia_id')
                    ->leftJoin('docentes', 'docentes.id', '=', 'sesions.docente_id')
                    ->leftJoin('personas as docente_persona', 'docente_persona.id', '=', 'docentes.persona_id')
                    ->leftJoin('materias', 'materias.id', '=', 'sesions.materia_id')
                    ->leftJoin('aulas', 'aulas.id', '=', 'sesions.aula_id')
                    ->whereIn('sesions.inscripcione_id', $inscripcionIds)
                    ->orderBy('sesions.inscripcione_id')
                    ->orderBy('sesions.horainicio')
                    ->get([
                        'sesions.inscripcione_id',
                        'dias.dia',
                        'sesions.horainicio',
                        'sesions.horafin',
                        'materias.materia',
                        'docentes.nombrecorto',
                        'docente_persona.nombre as docente_nombre',
                        'docente_persona.apellidop as docente_apellidop',
                        'aulas.aula',
                    ])
                    ->groupBy('inscripcione_id');
            }

            $inscripciones = $hijo->estudiante->inscripciones
                ->sortByDesc('created_at')
                ->values()
                ->map(function ($inscripcion) use (
                    $today,
                    $estadoFinalizado,
                    $estadoPresente,
                    $estadoFalta,
                    $estadoFaltaNotificada,
                    $estadoLicencia,
                    $estadoIndefinido,
                    $sesionesPorInscripcion
                ) {
                    $programaciones = $inscripcion->programaciones
                        ->sortBy('fecha')
                        ->values();

                    $hoy = $programaciones
                        ->filter(function ($p) use ($today) {
                            return $p->fecha && $p->fecha->isSameDay($today);
                        })
                        ->values();

                    $asistencias = $programaciones->whereIn('estado_id', [$estadoFinalizado, $estadoPresente])->count();
                    $faltas = $programaciones->whereIn('estado_id', [$estadoFalta, $estadoFaltaNotificada])->count();
                    $licencias = $programaciones->where('estado_id', $estadoLicencia)->count();
                    $clasesProgramadas = $programaciones->count();
                    $clasesPasadas = $programaciones
                        ->filter(function ($p) use ($today, $estadoIndefinido) {
                            return $p->fecha && $p->fecha->lte($today) && (int) $p->estado_id !== (int) $estadoIndefinido;
                        })
                        ->count();

                    $materias = $programaciones->pluck('materia.materia')->filter()->unique()->values();
                    $docentes = $programaciones
                        ->map(function ($p) {
                            if (!$p->docente) {
                                return null;
                            }
                            if (!empty($p->docente->nombrecorto)) {
                                return $p->docente->nombrecorto;
                            }
                            $docentePersona = $p->docente->persona;
                            if ($docentePersona) {
                                return trim(($docentePersona->nombre ?? '').' '.($docentePersona->apellidop ?? ''));
                            }
                            return null;
                        })
                        ->filter()
                        ->unique()
                        ->values();

                    $pagos = $inscripcion->pagos->sortByDesc('created_at')->values();
                    $totalPagado = (float) $pagos->sum('monto');
                    $costo = (float) $inscripcion->costo;
                    $saldo = max(0, $costo - $totalPagado);
                    $llegoHoy = $hoy->contains(function ($p) use ($estadoPresente, $estadoFinalizado) {
                        return in_array((int) $p->estado_id, [(int) $estadoPresente, (int) $estadoFinalizado], true);
                    });
                    $salioHoy = $hoy->contains(function ($p) use ($estadoFinalizado) {
                        return (int) $p->estado_id === (int) $estadoFinalizado;
                    });

                    return [
                        'tipo' => 'inscripcion',
                        'id' => $inscripcion->id,
                        'modelo' => $inscripcion,
                        'estado' => optional($inscripcion->estado)->estado,
                        'vigente' => (bool) $inscripcion->vigente,
                        'modalidad' => optional($inscripcion->modalidad)->modalidad,
                        'asistencias' => $asistencias,
                        'faltas' => $faltas,
                        'licencias' => $licencias,
                        'clases_programadas' => $clasesProgramadas,
                        'clases_pasadas' => $clasesPasadas,
                        'materias' => $materias,
                        'docentes' => $docentes,
                        'llego_hoy' => $llegoHoy,
                        'salio_hoy' => $salioHoy,
                        'historial' => $programaciones->map(function ($p) {
                            return [
                                'fecha' => $p->fecha,
                                'estado' => optional($p->estado)->estado,
                                'hora_inicio' => $p->hora_ini,
                                'hora_fin' => $p->hora_fin,
                                'materia' => optional($p->materia)->materia,
                                'docente' => $p->docente->nombrecorto ?? null,
                                'aula' => optional($p->aula)->aula,
                            ];
                        })->sortBy('fecha')->values(),
                        'detalle_programacion' => $programaciones,
                        'pagos' => $pagos,
                        'total_pagado' => $totalPagado,
                        'saldo' => $saldo,
                        'planificacion' => $sesionesPorInscripcion->get($inscripcion->id, collect()),
                    ];
                });
        }

        if ($hijo->computacion) {
            $matriculacionIds = $hijo->computacion->matriculaciones->pluck('id')->filter()->values();

            if ($matriculacionIds->isNotEmpty()) {
                $sesionesPorMatriculacion = DB::table('sesioncoms')
                    ->join('dias', 'dias.id', '=', 'sesioncoms.dia_id')
                    ->leftJoin('docentes', 'docentes.id', '=', 'sesioncoms.docente_id')
                    ->leftJoin('personas as docente_persona', 'docente_persona.id', '=', 'docentes.persona_id')
                    ->leftJoin('aulas', 'aulas.id', '=', 'sesioncoms.aula_id')
                    ->whereIn('sesioncoms.matriculacion_id', $matriculacionIds)
                    ->orderBy('sesioncoms.matriculacion_id')
                    ->orderBy('sesioncoms.horainicio')
                    ->get([
                        'sesioncoms.matriculacion_id',
                        'dias.dia',
                        'sesioncoms.horainicio',
                        'sesioncoms.horafin',
                        'docentes.nombrecorto',
                        'docente_persona.nombre as docente_nombre',
                        'docente_persona.apellidop as docente_apellidop',
                        'aulas.aula',
                    ])
                    ->groupBy('matriculacion_id');
            }

            $matriculaciones = $hijo->computacion->matriculaciones
                ->sortByDesc('created_at')
                ->values()
                ->map(function ($matriculacion) use (
                    $today,
                    $estadoFinalizado,
                    $estadoPresente,
                    $estadoFalta,
                    $estadoFaltaNotificada,
                    $estadoLicencia,
                    $estadoIndefinido,
                    $sesionesPorMatriculacion
                ) {
                    $programaciones = $matriculacion->programacionescom
                        ->sortBy('fecha')
                        ->values();
                    $hoy = $programaciones
                        ->filter(function ($p) use ($today) {
                            return $p->fecha && $p->fecha->isSameDay($today);
                        })
                        ->values();

                    $asistencias = $programaciones->whereIn('estado_id', [$estadoFinalizado, $estadoPresente])->count();
                    $faltas = $programaciones->whereIn('estado_id', [$estadoFalta, $estadoFaltaNotificada])->count();
                    $licencias = $programaciones->where('estado_id', $estadoLicencia)->count();
                    $clasesProgramadas = $programaciones->count();
                    $clasesPasadas = $programaciones
                        ->filter(function ($p) use ($today, $estadoIndefinido) {
                            return $p->fecha && $p->fecha->lte($today) && (int) $p->estado_id !== (int) $estadoIndefinido;
                        })
                        ->count();

                    $docentes = $programaciones
                        ->map(function ($p) {
                            if (!$p->docente) {
                                return null;
                            }
                            if (!empty($p->docente->nombrecorto)) {
                                return $p->docente->nombrecorto;
                            }
                            $docentePersona = $p->docente->persona;
                            if ($docentePersona) {
                                return trim(($docentePersona->nombre ?? '').' '.($docentePersona->apellidop ?? ''));
                            }
                            return null;
                        })
                        ->filter()
                        ->unique()
                        ->values();

                    $pagos = $matriculacion->pagos->sortByDesc('created_at')->values();
                    $totalPagado = (float) $pagos->sum('monto');
                    $costo = (float) $matriculacion->costo;
                    $saldo = max(0, $costo - $totalPagado);
                    $llegoHoy = $hoy->contains(function ($p) use ($estadoPresente, $estadoFinalizado) {
                        return in_array((int) $p->estado_id, [(int) $estadoPresente, (int) $estadoFinalizado], true);
                    });
                    $salioHoy = $hoy->contains(function ($p) use ($estadoFinalizado) {
                        return (int) $p->estado_id === (int) $estadoFinalizado;
                    });

                    return [
                        'tipo' => 'matriculacion',
                        'id' => $matriculacion->id,
                        'modelo' => $matriculacion,
                        'estado' => optional($matriculacion->estado)->estado,
                        'vigente' => (bool) $matriculacion->vigente,
                        'asignatura' => optional($matriculacion->asignatura)->asignatura,
                        'asistencias' => $asistencias,
                        'faltas' => $faltas,
                        'licencias' => $licencias,
                        'clases_programadas' => $clasesProgramadas,
                        'clases_pasadas' => $clasesPasadas,
                        'docentes' => $docentes,
                        'llego_hoy' => $llegoHoy,
                        'salio_hoy' => $salioHoy,
                        'historial' => $programaciones->map(function ($p) {
                            return [
                                'fecha' => $p->fecha,
                                'estado' => optional($p->estado)->estado,
                                'hora_inicio' => $p->horaini,
                                'hora_fin' => $p->horafin,
                                'docente' => $p->docente->nombrecorto ?? null,
                                'aula' => optional($p->aula)->aula,
                            ];
                        })->sortBy('fecha')->values(),
                        'detalle_programacion' => $programaciones,
                        'pagos' => $pagos,
                        'total_pagado' => $totalPagado,
                        'saldo' => $saldo,
                        'planificacion' => $sesionesPorMatriculacion->get($matriculacion->id, collect()),
                    ];
                });
        }

        $resumenGlobal = [
            'asistencias' => $inscripciones->sum('asistencias') + $matriculaciones->sum('asistencias'),
            'faltas' => $inscripciones->sum('faltas') + $matriculaciones->sum('faltas'),
            'licencias' => $inscripciones->sum('licencias') + $matriculaciones->sum('licencias'),
            'clases_programadas' => $inscripciones->sum('clases_programadas') + $matriculaciones->sum('clases_programadas'),
            'clases_pasadas' => $inscripciones->sum('clases_pasadas') + $matriculaciones->sum('clases_pasadas'),
            'total_pagado' => $inscripciones->sum('total_pagado') + $matriculaciones->sum('total_pagado'),
            'total_saldo' => $inscripciones->sum('saldo') + $matriculaciones->sum('saldo'),
        ];

        $telefonoWhatsApp = preg_replace('/\D+/', '', (string) env('INSTITUCION_WHATSAPP', '59171039910'));
        $mensajeWhatsApp = rawurlencode(
            'Hola, soy apoderado(a) de '.
            trim(($hijo->nombre ?? '').' '.($hijo->apellidop ?? '').' '.($hijo->apellidom ?? '')).
            ' y quiero más información del portal.'
        );
        $whatsAppUrl = "https://wa.me/{$telefonoWhatsApp}?text={$mensajeWhatsApp}";

        return [
            'inscripciones' => $inscripciones,
            'matriculaciones' => $matriculaciones,
            'resumenGlobal' => $resumenGlobal,
            'whatsAppUrl' => $whatsAppUrl,
            'telefonoWhatsApp' => $telefonoWhatsApp,
        ];
    }
}
