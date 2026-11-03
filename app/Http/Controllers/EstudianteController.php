<?php

namespace App\Http\Controllers;

use App\Models\Estudiante;
use App\Models\Felicitado;
use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\Programacioncom;
use App\Models\Programacion;
use App\Models\Mensajeable;
use App\Models\Persona;
use App\Models\Estado;
use App\Models\CrmSeguimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
class EstudianteController extends Controller
    // Endpoint para guardar búsqueda en tiempo real
    
{
    public function __construct()
    {
        $this->middleware('can:Listar Estudiantes')->only("cumplenerosView","historia","cumpleaneros","faltonesView","sinfaltaView","recordatorioView","finalizandoView","empezandoView");
        $this->middleware('can:Crear Estudiantes')->only("store","show");
        $this->middleware('can:Editar Estudiantes')->only("edit","update");
        $this->middleware('can:Eliminar Estudiantes')->only("destroy");
    }


        public function home(){

        if (Auth::check() && Auth::user()->hasRole(['Padre'])) {
            return redirect()->route('padre.home');
        }

        $nowBo = Carbon::now('America/La_Paz');
        $today = $nowBo->toDateString();
        $yesterday = $nowBo->copy()->subDay()->toDateString();
        $cutoffDate = $nowBo->copy()->subDays(60)->toDateString();
        $faltasStartDate = $nowBo->copy()->subDays(30)->toDateString();

        if (request()->has('busqueda')) {
            $termino = request('busqueda');
            \App\Models\HistorialBusqueda::create([
                'user_id' => Auth::id(),
                'termino' => $termino,
                'ip' => request()->ip(),
            ]);
        }

        $saludo = $nowBo->hour < 12 ? 'Buenos dias' : ($nowBo->hour < 19 ? 'Buenas tardes' : 'Buenas noches');
        $cleanPhone = static function ($raw) {
            if ($raw === null) {
                return null;
            }
            $digits = preg_replace('/\D+/', '', (string) $raw);
            if ($digits === '') {
                return null;
            }
            if (strlen($digits) > 8 && substr($digits, 0, 3) === '591') {
                $digits = substr($digits, 3);
            }
            return $digits;
        };
        $waLink = static function ($phone, $message) use ($cleanPhone) {
            $phoneDigits = $cleanPhone($phone);
            if (!$phoneDigits) {
                return null;
            }
            return 'https://wa.me/591'.$phoneDigits.'?text='.rawurlencode($message);
        };
        $fullName = static function ($nombre, $apellidop = null, $apellidom = null) {
            return trim(collect([$nombre, $apellidop, $apellidom])->filter()->implode(' '));
        };

        $nuevos = Persona::join('estudiantes', 'estudiantes.persona_id', 'personas.id')
            ->join('inscripciones', 'inscripciones.estudiante_id', 'estudiantes.id')
            ->join('programacions', 'programacions.inscripcione_id', 'inscripciones.id')
            ->where('programacions.fecha', '=', $today)
            ->select(
                'personas.id',
                'inscripciones.id as inscripcion_id',
                'programacions.estado_id as estado',
                'nombre',
                'apellidop',
                'apellidom',
                'telefono',
                'programacions.hora_ini',
                'programacions.hora_fin',
                'personas.foto'
            )
            ->orderBy('programacions.hora_ini', 'asc')
            ->get();

        $matriculaciones = Persona::join('computacions', 'computacions.persona_id', 'personas.id')
            ->join('matriculacions', 'matriculacions.computacion_id', 'computacions.id')
            ->join('programacioncoms', 'programacioncoms.matriculacion_id', 'matriculacions.id')
            ->where('programacioncoms.fecha', '=', $today)
            ->select(
                'personas.id',
                'matriculacions.id as matriculacion_id',
                'nombre',
                'apellidop',
                'apellidom',
                'telefono',
                'programacioncoms.horaini',
                'programacioncoms.horafin',
                'programacioncoms.estado_id as estado'
            )
            ->orderBy('programacioncoms.horaini', 'asc')
            ->get();

        $reinscripciones = Persona::join('interest_persona', 'interest_persona.persona_id', 'personas.id')
            ->join('interests', 'interests.id', 'interest_persona.interest_id')
            ->where('habilitado', estado('ESPERAREINSCRIPCION'))
            ->where('vuelvefecha', '<=', $today)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'apellidom', 'interests.interest', 'personas.foto', 'vuelvefecha')
            ->get();

        $rematriculaciones = Persona::join('interest_persona', 'interest_persona.persona_id', 'personas.id')
            ->join('interests', 'interests.id', 'interest_persona.interest_id')
            ->where('habilitado', estado('ESPERAREMATRICULACION'))
            ->where('vuelvefecha', '<=', $today)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'apellidom', 'interests.interest', 'personas.foto', 'vuelvefecha')
            ->get();

        $cumpleaneros = Persona::whereMonth('fechanacimiento', $nowBo->month)
            ->whereDay('fechanacimiento', $nowBo->day)
            ->whereNotNull('telefono')
            ->where('telefono', '<>', '')
            ->select('id', 'nombre', 'apellidop', 'apellidom', 'telefono', 'fechanacimiento', 'volvera')
            ->get()
            ->map(function ($item) use ($fullName, $waLink, $saludo) {
                $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Le deseamos un feliz cumpleanos y muchas bendiciones. Un abrazo de parte del equipo IFE.';
                return [
                    'id' => $item->id,
                    'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                    'telefono' => $item->telefono,
                    'motivo' => 'Cumpleanos hoy',
                    'fecha' => $item->fechanacimiento ? Carbon::parse($item->fechanacimiento)->format('d/m') : $today,
                    'mensaje' => $msg,
                    'whatsapp' => $waLink($item->telefono, $msg),
                    'prioridad' => (int) ($item->volvera ?? 3),
                    'color' => 'success',
                ];
            })
            ->unique('telefono')
            ->values();

        $clasesHoy = collect();
        foreach ($nuevos as $item) {
            $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Le recordamos su clase de hoy de '.$item->hora_ini.' a '.$item->hora_fin.'. Lo esperamos puntualmente.';
            $clasesHoy->push([
                'id' => $item->id,
                'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                'telefono' => $item->telefono,
                'motivo' => 'Clase hoy (academico)',
                'fecha' => $today.' '.$item->hora_ini,
                'mensaje' => $msg,
                'whatsapp' => $waLink($item->telefono, $msg),
                'prioridad' => 3,
                'color' => 'primary',
            ]);
        }
        foreach ($matriculaciones as $item) {
            $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Le recordamos su clase de hoy de '.$item->horaini.' a '.$item->horafin.'. Lo esperamos puntualmente.';
            $clasesHoy->push([
                'id' => $item->id,
                'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                'telefono' => $item->telefono,
                'motivo' => 'Clase hoy (computacion)',
                'fecha' => $today.' '.$item->horaini,
                'mensaje' => $msg,
                'whatsapp' => $waLink($item->telefono, $msg),
                'prioridad' => 3,
                'color' => 'primary',
            ]);
        }

        $pagosIns = Persona::join('estudiantes', 'estudiantes.persona_id', 'personas.id')
            ->join('inscripciones', 'inscripciones.estudiante_id', 'estudiantes.id')
            ->where('inscripciones.vigente', 1)
            ->where('inscripciones.condonado', 0)
            ->whereNotNull('inscripciones.fecha_proximo_pago')
            ->whereDate('inscripciones.fecha_proximo_pago', '>=', $cutoffDate)
            ->whereDate('inscripciones.fecha_proximo_pago', '<=', $today)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'personas.apellidom', 'personas.telefono', 'inscripciones.fecha_proximo_pago', 'personas.volvera')
            ->get();
        $pagosMat = Persona::join('computacions', 'computacions.persona_id', 'personas.id')
            ->join('matriculacions', 'matriculacions.computacion_id', 'computacions.id')
            ->where('matriculacions.vigente', 1)
            ->where('matriculacions.condonado', 0)
            ->whereNotNull('matriculacions.fecha_proximo_pago')
            ->whereDate('matriculacions.fecha_proximo_pago', '>=', $cutoffDate)
            ->whereDate('matriculacions.fecha_proximo_pago', '<=', $today)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'personas.apellidom', 'personas.telefono', 'matriculacions.fecha_proximo_pago', 'personas.volvera')
            ->get();
        $tocaPagar = collect();
        foreach ($pagosIns->merge($pagosMat) as $item) {
            $fechaPago = Carbon::parse($item->fecha_proximo_pago)->format('d/m/Y');
            $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Le escribimos para recordarle su pago pendiente con fecha '.$fechaPago.'. Si desea, le ayudamos a regularizar hoy mismo.';
            $tocaPagar->push([
                'id' => $item->id,
                'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                'telefono' => $item->telefono,
                'motivo' => 'Pago pendiente',
                'fecha' => $fechaPago,
                'mensaje' => $msg,
                'whatsapp' => $waLink($item->telefono, $msg),
                'prioridad' => max(4, (int) ($item->volvera ?? 4)),
                'color' => 'danger',
            ]);
        }

        $estadoIndefinidoId = Estado::where('estado', 'INDEFINIDO')->value('id');
        $inscripcionesConPocasClases = Programacion::where('fecha', '>=', $today)
            ->where('estado_id', $estadoIndefinidoId)
            ->select('inscripcione_id', DB::raw('COUNT(*) as pendientes'))
            ->groupBy('inscripcione_id')
            ->havingRaw('COUNT(*) <= 3')
            ->pluck('inscripcione_id');
        $matriculacionesConPocasClases = Programacioncom::where('fecha', '>=', $today)
            ->where('estado_id', $estadoIndefinidoId)
            ->select('matriculacion_id', DB::raw('COUNT(*) as pendientes'))
            ->groupBy('matriculacion_id')
            ->havingRaw('COUNT(*) <= 3')
            ->pluck('matriculacion_id');

        $terminanIns = Persona::join('estudiantes', 'estudiantes.persona_id', 'personas.id')
            ->join('inscripciones', 'inscripciones.estudiante_id', 'estudiantes.id')
            ->whereIn('inscripciones.id', $inscripcionesConPocasClases)
            ->whereDate('inscripciones.fechafin', '>=', $cutoffDate)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'personas.apellidom', 'personas.telefono', 'inscripciones.fechafin', 'personas.volvera')
            ->get();
        $terminanMat = Persona::join('computacions', 'computacions.persona_id', 'personas.id')
            ->join('matriculacions', 'matriculacions.computacion_id', 'computacions.id')
            ->whereIn('matriculacions.id', $matriculacionesConPocasClases)
            ->whereDate('matriculacions.fechafin', '>=', $cutoffDate)
            ->select('personas.id', 'personas.nombre', 'personas.apellidop', 'personas.apellidom', 'personas.telefono', 'matriculacions.fechafin', 'personas.volvera')
            ->get();
        $terminan = collect();
        foreach ($terminanIns->merge($terminanMat) as $item) {
            $fechaFin = Carbon::parse($item->fechafin)->format('d/m/Y');
            $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Le avisamos que su proceso esta por finalizar. Podemos ayudarle a renovar su inscripcion y reservar horario.';
            $terminan->push([
                'id' => $item->id,
                'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                'telefono' => $item->telefono,
                'motivo' => 'Termina en pocas clases',
                'fecha' => $fechaFin,
                'mensaje' => $msg,
                'whatsapp' => $waLink($item->telefono, $msg),
                'prioridad' => max(4, (int) ($item->volvera ?? 4)),
                'color' => 'warning',
            ]);
        }

        $estadoProspectoIds = Estado::whereIn('estado', ['ESPERANUEVO', 'SEGUIMIENTO', 'PROSPECTO', 'ESPERAREINSCRIPCION', 'ESPERAREMATRICULACION', 'ESPERARETOMA'])
            ->pluck('id')
            ->toArray();
        $prospectosRaw = Persona::with(['interests:id,interest', 'observaciones' => function ($q) {
            $q->latest('id')->limit(1);
        }])
            ->whereIn('habilitado', $estadoProspectoIds)
            ->where('votos', 1)
            ->whereDate('vuelvefecha', '<=', $today)
            ->get();
        $prospectos = $prospectosRaw->map(function ($persona) use ($fullName, $waLink, $saludo, $nowBo, $today) {
            $interes = optional($persona->interests->first())->interest ?: 'el apoyo academico';
            $obs = optional($persona->observaciones->first())->observacion;
            $requerimiento = trim(strip_tags((string) $obs));
            if ($requerimiento === '') {
                $requerimiento = 'la necesidad que nos comento';
            }
            $horas = $persona->created_at ? $persona->created_at->diffInHours($nowBo) : 2;
            if ($horas <= 2) {
                $msg = $saludo.', '.$fullName($persona->nombre, $persona->apellidop, $persona->apellidom).'. Queria asegurarme de que recibio la informacion sobre '.$interes.' para '.$requerimiento.'.';
            } elseif ($horas <= 24) {
                $msg = $saludo.', '.$fullName($persona->nombre, $persona->apellidop, $persona->apellidom).'. Si tiene dudas sobre '.$interes.' para '.$requerimiento.', avisenos y con gusto le ayudamos.';
            } elseif ($horas <= 48) {
                $msg = $saludo.', '.$fullName($persona->nombre, $persona->apellidop, $persona->apellidom).'. Entendemos que puede estar ocupado/a. Aun le interesa avanzar con '.$interes.' para '.$requerimiento.'?';
            } else {
                $msg = $saludo.', '.$fullName($persona->nombre, $persona->apellidop, $persona->apellidom).'. Estamos cerrando cupos para '.$interes.'. Si desea, le ayudo a reservar hoy mismo.';
            }

            return [
                'id' => $persona->id,
                'nombre' => $fullName($persona->nombre, $persona->apellidop, $persona->apellidom),
                'telefono' => $persona->telefono,
                'motivo' => 'Prospecto con seguimiento pendiente',
                'fecha' => $persona->vuelvefecha ? Carbon::parse($persona->vuelvefecha)->format('d/m/Y') : $today,
                'mensaje' => $msg,
                'whatsapp' => $waLink($persona->telefono, $msg),
                'prioridad' => max(1, (int) ($persona->volvera ?? 3)),
                'color' => 'info',
            ];
        })->values();

        $estadoFaltaIds = Estado::whereIn('estado', ['FALTA', 'FALTANOTIFICADA'])->pluck('id');
        $faltonesIns = Persona::join('estudiantes', 'estudiantes.persona_id', 'personas.id')
            ->join('inscripciones', 'inscripciones.estudiante_id', 'estudiantes.id')
            ->join('programacions', 'programacions.inscripcione_id', 'inscripciones.id')
            ->whereDate('programacions.fecha', '>=', $faltasStartDate)
            ->whereDate('programacions.fecha', '<=', $today)
            ->whereIn('programacions.estado_id', $estadoFaltaIds)
            ->whereNotIn('programacions.id',app(\App\Services\ClassAttendanceService::class)->resolvedAbsenceIds('programacions'))
            ->select(
                'personas.id',
                'personas.nombre',
                'personas.apellidop',
                'personas.apellidom',
                'personas.telefono',
                'personas.volvera',
                'inscripciones.id as referencia_id',
                DB::raw("'inscripcion' as referencia_tipo"),
                DB::raw('COUNT(*) as faltas_count'),
                DB::raw('MAX(programacions.fecha) as falta_fecha')
            )
            ->groupBy(
                'personas.id',
                'personas.nombre',
                'personas.apellidop',
                'personas.apellidom',
                'personas.telefono',
                'personas.volvera',
                'inscripciones.id'
            )
            ->get();
        $faltonesMat = Persona::join('computacions', 'computacions.persona_id', 'personas.id')
            ->join('matriculacions', 'matriculacions.computacion_id', 'computacions.id')
            ->join('programacioncoms', 'programacioncoms.matriculacion_id', 'matriculacions.id')
            ->whereDate('programacioncoms.fecha', '>=', $faltasStartDate)
            ->whereDate('programacioncoms.fecha', '<=', $today)
            ->whereIn('programacioncoms.estado_id', $estadoFaltaIds)
            ->whereNotIn('programacioncoms.id',app(\App\Services\ClassAttendanceService::class)->resolvedAbsenceIds('programacioncoms'))
            ->select(
                'personas.id',
                'personas.nombre',
                'personas.apellidop',
                'personas.apellidom',
                'personas.telefono',
                'personas.volvera',
                'matriculacions.id as referencia_id',
                DB::raw("'matriculacion' as referencia_tipo"),
                DB::raw('COUNT(*) as faltas_count'),
                DB::raw('MAX(programacioncoms.fecha) as falta_fecha')
            )
            ->groupBy(
                'personas.id',
                'personas.nombre',
                'personas.apellidop',
                'personas.apellidom',
                'personas.telefono',
                'personas.volvera',
                'matriculacions.id'
            )
            ->get();
        $faltones = collect();
        $faltonesBase = $faltonesIns
            ->merge($faltonesMat)
            ->sortByDesc(function ($item) {
                return (string) ($item->falta_fecha ?? '');
            })
            ->unique(function ($item) {
                return ($item->id ?? '').'|'.($item->referencia_tipo ?? '').'|'.($item->referencia_id ?? '');
            })
            ->values();

        foreach ($faltonesBase as $item) {
            $fechaFalta = !empty($item->falta_fecha)
                ? Carbon::parse($item->falta_fecha)->format('d/m/Y')
                : Carbon::parse($yesterday)->format('d/m/Y');
            $faltasCount = (int) ($item->faltas_count ?? 0);
            $referenciaTexto = ($item->referencia_tipo ?? '') === 'matriculacion' ? 'matriculacion' : 'inscripcion';
            $msg = $saludo.', '.$fullName($item->nombre, $item->apellidop, $item->apellidom).'. Notamos que no pudo asistir a su clase anterior. Si desea, le ayudamos a reprogramar.';
            $faltones->push([
                'id' => $item->id,
                'nombre' => $fullName($item->nombre, $item->apellidop, $item->apellidom),
                'telefono' => $item->telefono,
                'motivo' => 'Falto a la clase anterior ('.$faltasCount.' faltas en esta '.$referenciaTexto.')',
                'fecha' => $fechaFalta,
                'mensaje' => $msg,
                'whatsapp' => $waLink($item->telefono, $msg),
                'prioridad' => max(4, (int) ($item->volvera ?? 4)),
                'color' => 'danger',
            ]);
        }

        $homeAlerts = [
            'cumpleaneros' => $cumpleaneros,
            'clases_hoy' => $clasesHoy->values(),
            'pago_hoy' => $tocaPagar->values(),
            'terminan' => $terminan->values(),
            'prospectos' => $prospectos,
            'faltones' => $faltones->values(),
        ];
        $normalizeMotivo = static function ($motivo) {
            return mb_strtolower(trim((string) $motivo));
        };
        $gestionadosHoy = CrmSeguimiento::with('user:id,name')
            ->whereDate('created_at', $today)
            ->where('accion', 'gestionado')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'persona_id', 'motivo', 'user_id', 'created_at']);
        $gestionadosMap = [];
        foreach ($gestionadosHoy as $row) {
            $key = $row->persona_id.'|'.$normalizeMotivo($row->motivo);
            if (!isset($gestionadosMap[$key])) {
                $gestionadosMap[$key] = [
                    'user' => optional($row->user)->name ?: 'Usuario',
                    'at' => optional($row->created_at)->format('H:i'),
                ];
            }
        }
        foreach ($homeAlerts as $tab => $items) {
            $homeAlerts[$tab] = collect($items)->map(function ($item) use ($gestionadosMap, $normalizeMotivo) {
                $key = ($item['id'] ?? '').'|'.$normalizeMotivo($item['motivo'] ?? '');
                if (isset($gestionadosMap[$key])) {
                    $item['managed'] = true;
                    $item['managed_by'] = $gestionadosMap[$key]['user'];
                    $item['managed_at'] = $gestionadosMap[$key]['at'];
                } else {
                    $item['managed'] = false;
                }
                return $item;
            })->values();
        }
        $homeTabCounts = collect($homeAlerts)->map(function ($items) {
            return is_countable($items) ? count($items) : 0;
        })->toArray();
        $homeTabOrder = ['cumpleaneros', 'clases_hoy', 'pago_hoy', 'terminan', 'prospectos', 'faltones'];
        $totalPendientesHome = array_sum($homeTabCounts);

        $rotationKey = 'home_alert_rotation_'.$nowBo->format('Ymd');
        Cache::add($rotationKey, -1, $nowBo->copy()->endOfDay());
        $rotationIndex = (int) Cache::increment($rotationKey);
        $activeHomeTab = $homeTabOrder[$rotationIndex % count($homeTabOrder)];
        if (($homeTabCounts[$activeHomeTab] ?? 0) === 0 && $totalPendientesHome > 0) {
            $start = array_search($activeHomeTab, $homeTabOrder, true);
            for ($step = 1; $step < count($homeTabOrder); $step++) {
                $candidate = $homeTabOrder[($start + $step) % count($homeTabOrder)];
                if (($homeTabCounts[$candidate] ?? 0) > 0) {
                    $activeHomeTab = $candidate;
                    break;
                }
            }
        }

        $ultimasBusquedas = \App\Models\HistorialBusqueda::with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('termino')
            ->take(10)
            ->map(function ($item) {
                return [
                    'termino' => $item->termino,
                    'usuario' => $item->user ? $item->user->name : '',
                ];
            })
            ->values()
            ->toArray();
        $homeCrmEstados = Estado::orderBy('estado', 'asc')->get(['id', 'estado']);

        return view('persona.estudiantes', compact(
            'nuevos',
            'rematriculaciones',
            'reinscripciones',
            'matriculaciones',
            'ultimasBusquedas',
            'homeAlerts',
            'homeTabCounts',
            'activeHomeTab',
            'totalPendientesHome',
            'homeCrmEstados'
        ));

    }

    public function gestionarAlertaHome(Request $request)
    {
        $request->validate([
            'persona_id' => 'required|exists:personas,id',
            'accion' => 'required|in:gestionado,posponer,cambiar_estado',
            'canal' => 'nullable|string|max:20',
            'motivo' => 'nullable|string|max:160',
            'mensaje' => 'nullable|string|max:600',
            'vuelvefecha' => 'nullable|date',
            'estado_id' => 'nullable|exists:estados,id',
        ]);

        $persona = Persona::findOrFail($request->persona_id);
        $accion = $request->accion;
        $estadoId = null;
        $proximoContacto = null;
        $today = Carbon::now('America/La_Paz')->toDateString();

        if ($accion === 'gestionado') {
            $gestionExistente = CrmSeguimiento::where('persona_id', $persona->id)
                ->where('accion', 'gestionado')
                ->whereDate('created_at', $today)
                ->where('motivo', $request->motivo)
                ->first();

            if ($gestionExistente) {
                return response()->json([
                    'ok' => true,
                    'already' => true,
                    'mensaje' => 'Este registro ya fue gestionado hoy',
                ]);
            }
        }

        if ($accion === 'posponer') {
            if (!$request->filled('vuelvefecha')) {
                return response()->json(['ok' => false, 'mensaje' => 'La fecha para posponer es obligatoria'], 422);
            }
            $persona->vuelvefecha = Carbon::parse($request->vuelvefecha)->format('Y-m-d');
            $persona->save();
            $proximoContacto = $persona->vuelvefecha;
        }

        if ($accion === 'cambiar_estado') {
            if (!$request->filled('estado_id')) {
                return response()->json(['ok' => false, 'mensaje' => 'Debe seleccionar un estado'], 422);
            }
            $persona->habilitado = (int) $request->estado_id;
            $persona->save();
            $estadoId = $persona->habilitado;
        }

        CrmSeguimiento::create([
            'persona_id' => $persona->id,
            'user_id' => Auth::id(),
            'estado_id' => $estadoId,
            'accion' => $accion,
            'canal' => $request->canal ?: 'whatsapp',
            'motivo' => $request->motivo,
            'mensaje_sugerido' => $request->mensaje,
            'proximo_contacto' => $proximoContacto,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Gestion registrada',
            'persona' => [
                'id' => $persona->id,
                'habilitado' => $persona->habilitado,
                'vuelvefecha' => $persona->vuelvefecha,
            ],
        ]);
    }

    public function guardarBusqueda(Request $request)
    {
        $termino = $request->input('busqueda');
        if ($termino) {
            \App\Models\HistorialBusqueda::create([
                'user_id' => Auth::id(),
                'termino' => $termino,
                'ip' => $request->ip(),
            ]);
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false], 400);
    }

    public function cumplenerosView()
    {
        return view('estudiantes.cumpleaneros');
    }
    public function historia($estudiante_id)
    {
        $grados=Estudiante::join('estudiante_grado','estudiantes.id','=','estudiante_grado.estudiante_id')
            ->join('grados','grados.id','=','estudiante_grado.grado_id')
            ->join('colegios','colegios.id','=','estudiante_grado.colegio_id')
            ->select('estudiantes.id','colegio_id','colegios.nombre','grados.grado','anio')
            ->orderBy('anio', 'desc')
            ->get();
        return view('estudiantes.historiaacademica',compact('grados','estudiante_id'));
    }
    public function cumpleaneros(Request $request){
        $cumpleaneros=Persona::join('estudiantes','estudiantes.persona_id','personas.id')
  			->whereMonth('fechanacimiento', '=', Carbon::now()->add($request->dias, 'day')->format('m'))
                ->whereDay('fechanacimiento', '=', Carbon::now()->add($request->dias, 'day')->format('d'))
                ->whereNotIn('personas.id',Felicitado::where('anio',Carbon::now()->year)->select('persona_id')->get()->toArray())
  			->select('personas.id','nombre','apellidop','apellidom','foto','telefono',DB::raw("(SELECT TIMESTAMPDIFF(YEAR,fechanacimiento,CURDATE()) AS viejo FROM personas where personas.id=estudiantes.persona_id GROUP BY nombre,fechanacimiento) as edad"))
                ->groupBy('personas.id','nombre','fechanacimiento','apellidop','apellidom','foto','telefono','edad')
                ->get();
        return DataTables::of($cumpleaneros)
            ->addColumn('btn','estudiantes.action')
            ->rawColumns(['btn'])
            ->toJson(); 
    }

    public function estudiantesFaltones()
    {
        $faltones=Persona::join('estudiantes','personas.id','estudiantes.persona_id')
                ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
                ->join('programacions','programacions.inscripcione_id','inscripciones.id')
                ->join('estados','estados.id','programacions.estado_id')
                ->join('userables','userables.userable_id','inscripciones.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Inscripcione::class)
                ->where('estados.estado','FALTA')->whereNotIn('programacions.id',app(\App\Services\ClassAttendanceService::class)->resolvedAbsenceIds('programacions'))
                ->where('inscripciones.vigente',1)
                ->select('personas.id','programacions.id as programacion_id','nombre','apellidop','apellidom','telefono','users.name','personas.foto')
                ->get();
        return DataTables::of($faltones)
        ->addColumn('btn','estudiantes.actionfaltones')
        ->rawColumns(['btn'])
        ->toJson(); 
    }
    public function estudiantesSinFalta()
    {
            $InscripcionesConFalta=Persona::join('estudiantes','personas.id','estudiantes.persona_id')
                ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
                ->join('programacions','programacions.inscripcione_id','inscripciones.id')
                ->join('estados','estados.id','programacions.estado_id')
                ->join('userables','userables.userable_id','inscripciones.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Inscripcione::class)
                ->where('estados.estado','=','FALTA')
                ->orWhere('estados.estado','=','FALTANOTIFICADA')
  				->where('inscripciones.vigente',1)
                ->select('inscripciones.id')
                ->get();
            $InscripcionesSinFalta=Inscripcione::whereNotIn('inscripciones.id',$InscripcionesConFalta)
                ->where('inscripciones.vigente',1)
                ->select('inscripciones.id')		
                ->get();

            $PersonasSinFalta=Persona::join('estudiantes','estudiantes.persona_id','personas.id')
                ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
                ->join('userables','userables.userable_id','inscripciones.id')
                ->join('users','users.id','userables.user_id')
                ->where('userables.userable_type',Inscripcione::class)
                ->whereIn('inscripciones.id',$InscripcionesSinFalta)
                ->select('personas.id','nombre','apellidop','apellidom','telefono','users.name','personas.foto')
                ->get();

            return DataTables::of($PersonasSinFalta)
            ->addColumn('btn','estudiantes.actionsinfalta')
            ->rawColumns(['btn'])
            ->toJson(); 
    }
    
    public function estudiantesFinalizando(){
        $inscripcionesFinalizadas=Mensajeable::where('mensajeable_type',Inscripcione::class)
            ->where('mensaje_id',idMensaje('FINALIZANDOINSCRIPCION'))
            ->select('mensajeable_id')
            ->get();

        $finalizan=Persona::join('estudiantes', 'estudiantes.persona_id','personas.id')
        ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
        ->join('userables','userables.userable_id','inscripciones.id')
        ->join('users','users.id','userables.user_id')
        ->where('userables.userable_type',Inscripcione::class)
        ->where('inscripciones.vigente',1)
        ->where('inscripciones.condonado',0)
        ->whereNotIn('inscripciones.id',$inscripcionesFinalizadas)
        ->select('personas.id','inscripciones.id as inscripcione_id','nombre','apellidop','apellidom','fechafin','telefono','personas.foto','users.name as usuario')
        ->orderBy('fechafin','asc')
        ->get();
        //  dd($finalizan);

        return DataTables::of($finalizan)
            ->addColumn('btn','estudiantes.actionfinalizando')
            ->rawColumns(['btn'])
            ->toJson(); 
    }
    public function estudiantesEmpezando(){
        $inscripcionesEmpezados=Mensajeable::where('mensajeable_type',Inscripcione::class)
            ->where('mensaje_id',idMensaje('EMPEZANDOINSCRIPCION'))
            ->select('mensajeable_id')
            ->get();

        $finalizan=Persona::join('estudiantes', 'estudiantes.persona_id','personas.id')
        ->join('inscripciones','inscripciones.estudiante_id','estudiantes.id')
        ->join('userables','userables.userable_id','inscripciones.id')
        ->join('users','users.id','userables.user_id')
        ->where('userables.userable_type',Inscripcione::class)
        ->where('inscripciones.vigente',1)
        ->where('inscripciones.condonado',0)
        ->whereNotIn('inscripciones.id',$inscripcionesEmpezados)
        ->select('personas.id','inscripciones.id as inscripcione_id','nombre','apellidop','apellidom','fechaini','telefono','personas.foto','users.name as usuario')
        ->orderBy('fechaini','asc')
        ->get();
        //  dd($finalizan);

        return DataTables::of($finalizan)
            ->addColumn('btn','estudiantes.actionempezando')
            ->rawColumns(['btn'])
            ->toJson(); 
    }

    
    public function faltonesView()
    {
        return view('estudiantes.faltones');
    }
    public function sinfaltaView()
    {
        return view('estudiantes.sinfalta');
    }
    public function recordatorioView()
    {
        return view('estudiantes.recordatorio');
    }
  
    public function finalizandoView()
    {
        return view('estudiantes.finalizando');
    }
    public function empezandoView()
    {
        return view('estudiantes.empezando');
    }
  
    public function store(Request $request)
    {

    }
    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }
    public function ultimoAnio(Estudiante $estudiante)
    {
        if(!is_null($estudiante->grados()->first())){ 
            return $estudiante->grados()->orderBy('anio', 'desc')->get()->first()->pivot->anio;
        }else{
            return null;
        }
    }

    public function yaEstaGestionado(Estudiante $estudiante)
    {
        $ultimoAnio=$this->ultimoAnio($estudiante);
        //dd($ultimoAnio == Carbon::now()->isoFormat('Y'));
        if (is_null($ultimoAnio)) {
            return false;
        } else {
            if ($ultimoAnio == Carbon::now()->isoFormat('Y')) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    public function informarEstadoGeneral(){
        
        return 1;
    }

    public function listar(){
        $estudiantes=Persona::join('estudiantes','estudiantes.persona_id','=','personas.id')
        ->select('personas.id','estudiantes.id as estudiante_id','nombre','apellidop','apellidom','foto');

        return datatables()->of($estudiantes)
            ->addColumn('btn', function($row){
                $asistenciaDisponible = false;
                $asistenciaMensaje = 'No disponible';
                $asistenciaProgramacionId = null;
                $asistenciaUrl = null;
                $asistenciaTipo = null;

                $inscripciones = Inscripcione::where('estudiante_id', $row->estudiante_id)
                    ->where('estado_id', estado('CORRIENDO'))
                    ->where('vigente', 1)
                    ->get();

                $inscripcionIds = $inscripciones->pluck('id');
                $programacionesHoy = $inscripcionIds->isNotEmpty()
                    ? Programacion::whereIn('inscripcione_id', $inscripcionIds)
                        ->where('fecha', Carbon::now()->toDateString())
                        ->sinCompensar()->get()
                    : collect();

                $matriculaciones = Matriculacion::join('computacions', 'computacions.id', '=', 'matriculacions.computacion_id')
                    ->where('computacions.persona_id', $row->id)
                    ->where('matriculacions.estado_id', estado('CORRIENDO'))
                    ->where('matriculacions.vigente', 1)
                    ->select('matriculacions.*')
                    ->get();

                $matriculacionIds = $matriculaciones->pluck('id');
                $programacionesComHoy = $matriculacionIds->isNotEmpty()
                    ? Programacioncom::whereIn('matriculacion_id', $matriculacionIds)
                        ->where('fecha', Carbon::now()->toDateString())
                        ->sinCompensar()->get()
                    : collect();

                $hayProgramacion = $programacionesHoy->isNotEmpty() || $programacionesComHoy->isNotEmpty();
                $hayIndefinido = $programacionesHoy->contains('estado_id', estado('INDEFINIDO'))
                    || $programacionesComHoy->contains('estado_id', estado('INDEFINIDO'));
                $hayPresente = $programacionesHoy->contains('estado_id', estado('PRESENTE'))
                    || $programacionesComHoy->contains('estado_id', estado('PRESENTE'));

                $asistenciaDisponible = auth()->user()->can('Crear Clases') || auth()->user()->can('Listar Clases')
                    || auth()->user()->can('Crear Clasescom') || auth()->user()->can('Listar Clasescom');
                $asistenciaTipo = 'tabs';
                $asistenciaUrl = route('marcado.presente.tabs', $row->id);
                $asistenciaMensaje = !$hayProgramacion ? 'Sin clase hoy: ver clases y agregar'
                    : ($hayPresente ? 'Marcar salida o agregar clase' : 'Ver y marcar asistencia');

                return view('persona.action', [
                    'id' => $row->id,
                    'asistencia_disponible' => $asistenciaDisponible,
                    'asistencia_programacion_id' => $asistenciaProgramacionId,
                    'asistencia_mensaje' => $asistenciaMensaje,
                    'asistencia_url' => $asistenciaUrl,
                    'asistencia_tipo' => $asistenciaTipo,
                    'asistencia_hoy' => $hayProgramacion,
                ])->render();
            })
            ->rawColumns(['btn','foto'])
            ->toJson();
    }
    
    public function referencia(){
        $referencias=Persona::select('id','nombre','apellidop','apellidom','foto');
        return datatables()->of($referencias)
        ->addColumn('btn', 'persona.actionmodal')
        ->rawColumns(['btn','foto'])
        ->toJson();
    }


}






