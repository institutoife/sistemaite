<?php
namespace App\Http\Controllers;
use App\Models\Docente;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\Pais;
use App\Models\Ciudad;
use App\Models\Estado;
use App\Models\Zona;
use App\Models\Como;
use App\Models\Interest;
use App\Models\Observacion;
use App\Models\User;
use App\Models\Mododocente;
use App\Models\Estudiante;
use App\Models\Programacion;
use App\Models\Programacioncom;
use App\Models\Inscripcione;
use App\Models\Carrera;
use App\Models\Clase;
use App\Models\Clasecom;
use App\Models\Computacion;
use App\Models\Dia;
use App\Models\DocenteTurno;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PersonaController;
use Barryvdh\DomPDF\Facade as PDF;
use Spatie\Permission\Models\Role;

use App\Http\Requests\DocenteUpdateRequest;
use App\Http\Requests\PersonaStoreRequest;

class DocenteController extends Controller
{
    








    public function __construct()
    {
        $this->middleware('can:Listar Docentes')->only('index','show');
        $this->middleware('can:Crear Docentes')->only('create','store','GuardarConfigurarNiveles','configurar_niveles');
        $this->middleware('can:Editar Docentes')->only('edit','update');
        $this->middleware('can:Editar Docentes')->only('turnos','guardarTurnos');
        $this->middleware('can:Crear Usuarios')->only('crearUsuarioDesdeDocente');
        $this->middleware('can:Eliminar Docentes')->only('destroy');
        $this->middleware('can:Consultas Docentes de clases')->only("misEstudiatescomActuales","misEstudiatesProgramados","misEstudiatescomProgramados","misEsperados","misEsperadoscom");
    }

    public function index()
    {
        return view('docente.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $paises=Pais::get();
        $ciudades=Ciudad::get();
        $zonas=Zona::get();
        $interests=Interest::all();
        $comos=Como::all();
        //$docente=$persona->docente;
        $ciudades = Ciudad::get();
        $estados = Estado::get();
        $mododocentes = Mododocente::get();
        return view('docente.create',compact('mododocentes','estados','paises', 'zonas', 'ciudades','interests','comos'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PersonaStoreRequest $request)
    {

        if(($request->nombrecorto=="")||($request->fecha_inicio=="")||
            ($request->dias_prueba=="")||($request->sueldo=="")||
            ($request->mododocente_id=="")||($request->estado_id=="")||($request->perfil==""))
        {
            return back()->withInput()->with(['mensaje'=>"Faltan llenar datos"]);
        }

        $persona=new Persona();
        $persona->nombre = $request->nombre;
        $persona->apellidop = $request->apellidop;
        $persona->apellidom = $request->apellidom;
        $persona->fechanacimiento = $request->fechanacimiento;
        $persona->direccion = $request->direccion;
        $persona->carnet = $request->carnet;
        $persona->expedido = $request->expedido;
        $persona->genero = $request->genero;
        if ($request->hasFile('foto')){
            $foto=$request->file('foto');
            $nombreImagen='estudiantes/'.Str::random(20).'.jpg';
            $imagen= Image::make($foto)->encode('jpg',75);
            $imagen->resize(300,300,function($constraint){
                $constraint->upsize();
            });
            $fotillo = Storage::disk('public')->put($nombreImagen, $imagen->stream());
            $persona->foto = $nombreImagen;
        }
        $persona->como_id = $request->como_id;
        $persona->habilitado = 1;
        $persona->papelinicial = $request->papel;
        $persona->telefono=$request->telefono;
        $persona->persona_id = $request->persona_id;
        $persona->pais_id = $request->pais_id;
        $persona->ciudad_id = $request->ciudad_id;
        $persona->zona_id = $request->zona_id;
        $persona->save();
        $ObjetoPersona=new PersonaController();
        $ObjetoPersona->CrearContacto($persona->id);
        $persona->interests()->sync(array_keys($request->interests));

        $user = new User();
        $user->email =strtolower(Str::substr($persona->nombre, 1, 2).$persona->apellidop.$persona->id)."@ife.bo" ;
        $user->name = ucfirst(strtolower($persona->nombre).$persona->id);
        $user->persona_id = $persona->id;
        // $user->password = Crypt::encryptString($user->name."*");
        $user->password = Hash::make($user->name."*");
        $user->foto = "estudiantes/sinperfil.png";
        $user->save();
        $user->assignRole('Estudiante');
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A    P E R S O N A   %%%%%%%%%%%%%%%%*/
        $persona->usuarios()->attach(Auth::user()->id);
        $docente = new Docente();

        

        $docente->nombrecorto=$request->nombrecorto;
        $docente->fecha_inicio=$request->fecha_inicio;
        $docente->dias_prueba = $request->dias_prueba;
        $docente->sueldo = $request->sueldo;
        $docente->mododocente_id = $request->mododocente_id;
        $docente->perfil = $request->perfil;
        $docente->estado_id = $request->estado_id;
        $docente->persona_id = $persona->id;
        $docente->save();
        
        $docente->usuarios()->attach(Auth::user()->id);
        $observacion = new Observacion();
        $observacion->observacion = $request->observacion;
        $observacion->activo = 1;
        $observacion->observable_id = $persona->id;
        $observacion->observable_type = "App\Models\Persona";
        $observacion->save();
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
        $observacion->usuarios()->attach(Auth::user()->id);
        return redirect()->route('home');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $docente = Docente::findOrFail($id);
            $persona = $docente->persona;
            if (!$persona) {
                return response()->view('errors.custom', ['mensaje' => 'No se encontró la persona asociada al docente.'], 404);
            }
            $user = $persona->usuarios->first();
            $pais = Pais::find($persona->pais_id);
            $ciudad = Ciudad::find($persona->ciudad_id);
            $zona = Zona::find($persona->zona_id);
            $observacion = Observacion::where('observable_id', $persona->id)
                ->where('observable_type', Persona::class)->get()->first();
            $observaciones = $persona->observaciones;
            $recomendado = Persona::find($persona->persona_id);
            $apoderados = $persona->apoderados;

            $calificaciones = Persona::join('calificacions', 'personas.id', 'calificacions.persona_id')
                ->join('users', 'users.id', 'calificacions.user_id')
                ->where('calificacions.persona_id', $persona->id)
                ->select('calificacion', 'users.foto', 'calificacions.created_at')
                ->get();
            $calificado = $persona->calificaciones->where('user_id', Auth::user()->id)->count();
            $promedio = round($persona->calificaciones->avg('calificacion'), 1);
            if ($persona->calificaciones->where('user_id', Auth::user()->id)->first() != null)
                $micalificacion = $persona->calificaciones->where('user_id', Auth::user()->id)->first()->calificacion;
            else
                $micalificacion = null;

            return view('docente.show', compact('docente', 'persona', 'pais', 'ciudad', 'zona', 'observacion', 'recomendado', 'apoderados', 'calificado', 'promedio', 'calificaciones', 'micalificacion', 'user', 'observaciones'));
        } catch (\Exception $e) {
            return response()->view('errors.custom', ['mensaje' => 'Error al mostrar el docente: ' . $e->getMessage()], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Docente $docente)
    {
        
        $persona=$docente->persona;
        $docente=$persona->docente;
        $ciudades = Ciudad::get();
        $paises = Pais::get();
        $zonas = Zona::get();
        $estados = Estado::get();
        $mododocentes = Mododocente::get();
        $comos=Como::get();
        $observacion = Observacion::where('observable_id', $persona->id)
            ->where('observable_type', Persona::class)->get()->first();
        if($observacion!=null){
            $observacion=$observacion->observacion;
        }
        //dd($observacion);
        $interests_currents=$persona->interests; 
        $ids=[];
        foreach ($interests_currents as $interest) {
            $ids[] = $interest->id;
        }
        $interests_faltantes = Interest::whereNotIn('id', $ids)->get();
        //dd($interests_currents);
        
        switch ($persona->papelinicial) {

            case 'estudiante':
                $estudiante=$persona->estudiante;
                break;
            case 'docente':
                $docente = $persona->docente;
                break;
            case 'cliservicio':
                $cliservicio = $persona->cliservicio;
                
                break;
            case 'clicopy':
                $clicopy = $persona->clicopy;
                break;

            case 'administrativo':
                $administrativo = $persona->administrativo;
                break;
            case 'proveedor':
                $proveedor = $persona->proveedor;
                break;
            default:
                # code...ife.bo
                break;
        }
        // dd($comos);
        return view('docente.edit',compact('docente','comos','persona','mododocentes','estados','paises','ciudades','zonas','observacion','interests_currents','interests_faltantes'));
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(DocenteUpdateRequest $request,Docente $docente)
    {
        /*DESDE */
        //dd($request->all());
        if(($request->nombrecorto=="")||($request->fecha_inicio=="")||
            ($request->dias_prueba=="")||($request->sueldo=="")||
            ($request->mododocente_id=="")||($request->estado_id=="")||($request->perfil==""))
        {
            return back()->withInput()->with(['mensaje'=>"Faltan llenar datos"]);
        }

        $persona=$docente->persona;
        $persona->nombre = $request->nombre;
        $persona->apellidop = $request->apellidop;
        $persona->apellidom = $request->apellidom;
        $persona->fechanacimiento = $request->fechanacimiento;
        $persona->direccion = $request->direccion;
        $persona->carnet = $request->carnet;
        $persona->expedido = $request->expedido;
        $persona->genero = $request->genero;
        if ($request->hasFile('foto')){
            $foto=$request->file('foto');
            $nombreImagen='estudiantes/'.Str::random(20).'.jpg';
            $imagen= Image::make($foto)->encode('jpg',75);
            $imagen->resize(300,300,function($constraint){
                $constraint->upsize();
            });
            $fotillo = Storage::disk('public')->put($nombreImagen, $imagen->stream());
            $persona->foto = $nombreImagen;
        }
        $persona->como_id = $request->como_id;
        $persona->habilitado = 1;
        $persona->papelinicial = $request->papel;
        if ($request->has('telefono')) {
            $persona->telefono = $request->telefono;
        }
        $persona->persona_id = $request->persona_id;
        $persona->pais_id = $request->pais_id;
        $persona->ciudad_id = $request->ciudad_id;
        $persona->zona_id = $request->zona_id;
        $persona->save();
        $persona->interests()->sync(array_keys($request->interests));
        /*HASTA*/
        $docente->nombrecorto=$request->nombrecorto;
        $docente->fecha_inicio=$request->fecha_inicio;
        $docente->dias_prueba = $request->dias_prueba;
        $docente->sueldo =$request->sueldo;
        $docente->mododocente_id = $request->mododocente_id;
        $docente->perfil = $request->perfil;
        $docente->estado_id =$request->estado_id;
        $docente->save();
        $docente->usuarios()->attach(Auth::user()->id);

        $observacion = new Observacion();
        $observacion->observacion = $request->observacion;
        $observacion->activo = 1;
        $observacion->observable_id = $persona->id;
        $observacion->observable_type = "App\Models\Persona";
        $observacion->save();
        
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
        $observacion->usuarios()->attach(Auth::user()->id);

        return redirect()->Route('docentes.show',$docente->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $docente = Persona::findOrFail($id)->docente;
        $docente->delete();
        return response()->json(['message' => 'Registro Eliminado', 'status' => 200]);
    }

    public function configurar_niveles($persona){
        $Persona= Persona::findOrFail($persona);
        $idsNivelesDocente=Arr::pluck($Persona->docente->niveles, 'id');
        $nivelesTodos=Arr::pluck(Nivel::select('id')->get(),'id');
        $nivelesFaltantes=collect($nivelesTodos)->diff($idsNivelesDocente);
        $nivelesFaltantes=Nivel::whereIn('id',$nivelesFaltantes)->get();
        $nivelesDocente=Nivel::whereIn('id',$idsNivelesDocente)->get();
        return view('docente.niveles',compact('nivelesDocente','nivelesFaltantes','Persona'));
    }

    public function GuardarConfigurarNiveles(Request $request,$docente) {
        $docente = Persona::findOrFail($docente)->docente;
        $docente->niveles()->sync(array_keys($request->niveles));
        return redirect()->route('docentes.gestionar.niveles',$docente->persona->id);
    } 

    public function turnos($docente)
    {
        $docente = Docente::with('persona')->findOrFail($docente);
        $dias = Dia::orderBy('id')->get();
        $turnos = DocenteTurno::where('docente_id', $docente->id)
            ->orderBy('dia_id')
            ->orderBy('hora_inicio')
            ->get()
            ->groupBy('dia_id');

        return view('docente.turnos', compact('docente', 'dias', 'turnos'));
    }

    public function guardarTurnos(Request $request, $docente)
    {
        $docente = Docente::findOrFail($docente);

        $turnosInput = $request->input('turnos', []);
        $normalizedTurnos = [];

        foreach ($turnosInput as $diaId => $items) {
            foreach ($items as $index => $item) {
                $normalizedTurnos[$diaId][$index] = [
                    'hora_inicio' => isset($item['hora_inicio']) && $item['hora_inicio'] !== '' ? $item['hora_inicio'] : null,
                    'hora_fin' => isset($item['hora_fin']) && $item['hora_fin'] !== '' ? $item['hora_fin'] : null,
                ];
            }
        }

        $request->merge(['turnos' => $normalizedTurnos]);

        $request->validate([
            'turnos.*.*.hora_inicio' => 'nullable|date_format:H:i',
            'turnos.*.*.hora_fin' => 'nullable|date_format:H:i',
        ]);

        $turnosInput = $normalizedTurnos;
        $errors = [];

        foreach ($turnosInput as $diaId => $items) {
            foreach ($items as $index => $item) {
                $inicio = $item['hora_inicio'] ?? null;
                $fin = $item['hora_fin'] ?? null;

                if (!$inicio && !$fin) {
                    continue;
                }

                if (!$inicio || !$fin) {
                    $errors["turnos.$diaId.$index"] = 'Debe completar hora inicio y fin.';
                    continue;
                }

                if ($fin <= $inicio) {
                    $errors["turnos.$diaId.$index"] = 'La hora fin debe ser mayor que hora inicio.';
                }
            }
        }

        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $removeIds = $request->input('remove_turnos', []);
        if (!empty($removeIds)) {
            DocenteTurno::where('docente_id', $docente->id)
                ->whereIn('id', $removeIds)
                ->delete();
        }

        foreach ($turnosInput as $diaId => $items) {
            foreach ($items as $item) {
                $inicio = $item['hora_inicio'] ?? null;
                $fin = $item['hora_fin'] ?? null;

                if (!$inicio || !$fin || $fin <= $inicio) {
                    continue;
                }

                DocenteTurno::create([
                    'docente_id' => $docente->id,
                    'dia_id' => (int) $diaId,
                    'hora_inicio' => $inicio,
                    'hora_fin' => $fin,
                ]);
            }
        }

        return redirect()->route('docentes.turnos', $docente->id)
            ->with('mensaje', 'Turnos actualizados.');
    }

    public function docentesPorTurno(Request $request)
    {
        $dias = $request->input('dias', '');
        $horaInicio = $request->input('hora_inicio');
        $horaFin = $request->input('hora_fin');
        $modoDocente = strtoupper(trim((string) $request->input('mododocente', '')));

        if ($horaInicio && strlen($horaInicio) === 5) {
            $horaInicio = $horaInicio . ':00';
        }

        if ($horaFin && strlen($horaFin) === 5) {
            $horaFin = $horaFin . ':00';
        }

        $diasList = collect(explode(',', $dias))
            ->map(function ($dia) {
                return (int) trim($dia);
            })
            ->filter(function ($dia) {
                return $dia > 0;
            })
            ->values();

        if ($diasList->isEmpty() || !$horaInicio || !$horaFin) {
            return response()->json([]);
        }

        $docenteIds = DocenteTurno::select('docente_id')
            ->whereIn('dia_id', $diasList)
            ->where('hora_inicio', '<=', $horaInicio)
            ->where('hora_fin', '>=', $horaFin)
            ->groupBy('docente_id')
            ->havingRaw('COUNT(DISTINCT dia_id) = ?', [$diasList->count()])
            ->pluck('docente_id');

        $docentesQuery = Docente::with('persona')
            ->whereIn('id', $docenteIds)
            ->where('estado_id', estado('HABILITADO'));

        if ($modoDocente !== '') {
            $docentesQuery->whereHas('mododocente', function ($query) use ($modoDocente) {
                $query->whereRaw('UPPER(TRIM(mododocente)) LIKE ?', ['%' . $modoDocente . '%']);
            });
        }

        $docentes = $docentesQuery
            ->orderBy('nombrecorto')
            ->get();

        $conteos = DB::table('sesions')
            ->select('docente_id', DB::raw('COUNT(*) as cantidad'))
            ->whereIn('docente_id', $docenteIds)
            ->whereIn('dia_id', $diasList)
            ->where('horainicio', $horaInicio)
            ->where('horafin', $horaFin)
            ->groupBy('docente_id')
            ->pluck('cantidad', 'docente_id');

        $respuesta = $docentes->map(function ($docente) use ($conteos) {
            $persona = $docente->persona;
            $nombre = $persona ? ($persona->nombre . ' ' . $persona->apellidop) : $docente->nombrecorto;
            $cantidad = (int) ($conteos[$docente->id] ?? 0);
            return [
                'id' => $docente->id,
                'nombre' => $nombre,
                'cantidad' => $cantidad,
            ];
        })->values();

        return response()->json($respuesta);
    }

    // Reporte PDF de turnos de docentes habilitados
    public function turnosPdf(Request $request)
    {
        $docentes = Docente::whereHas('persona', function($q) {
            $q->where('estado_id', estado('HABILITADO'));
        })->with(['persona', 'turnos' => function($q) {
            $q->with('dia');
        }])->get();

        if ($request->has('download')) {
            $pdf = PDF::loadView('docente.turnos_pdf_export', ['docentes' => $docentes, 'pdf' => true]);
            return $pdf->download('turnos_docentes.pdf');
        }
        return view('docente.turnos_pdf', compact('docentes'));
    }

   
    public function EstudiantesDeUnDocente($estudiante_id){
        $estudiantes=Docente::findOrFail($estudiante_id)
        ->estudiantes
        ->get();
        
        return datatables()->of($estudiantes)
                ->addColumn('btn', 'docente.action_estudiantes')
                ->rawColumns(['btn', 'foto'])
                ->toJson();
        
    }
    public function listarDocentes(){
        $docentes=Persona::join('docentes','docentes.persona_id','=','personas.id')
        ->leftJoin('users','users.persona_id','=','personas.id')
        ->join('mododocentes','mododocentes.id','docentes.mododocente_id')
        ->select(
            'docentes.id',
            'personas.id as persona_id',
            'personas.nombre',
            'personas.apellidop',
            'personas.apellidom',
            'personas.foto as foto',
            'docentes.perfil',
            'mododocente',
            'personas.telefono as telefono',
            'users.id as user_id',
            'users.email as user_email'
        )->get();

        return datatables()->of($docentes)
            ->addColumn('btn','docente.action')
            ->rawColumns(['btn','perfil'])
            ->toJson();    
    }

    public function crearUsuarioDesdeDocente(Docente $docente)
    {
        $persona = $docente->persona;
        if (!$persona) {
            return redirect()->route('docentes.index')
                ->with('warning', 'No se pudo crear el usuario porque el docente no tiene persona relacionada.');
        }

        $usuarioExistente = User::where('persona_id', $persona->id)->first();
        if ($usuarioExistente) {
            return redirect()->route('users.edit', $usuarioExistente->id)
                ->with('warning', 'Este docente ya tiene usuario. Se abrió la edición del usuario existente.');
        }

        $baseUsuario = Str::ascii(trim(($persona->nombre ?? '') . ($persona->apellidop ?? '') . ($persona->apellidom ?? '')));
        $baseUsuario = Str::lower(preg_replace('/[^a-z0-9]/', '', $baseUsuario));
        if ($baseUsuario === '') {
            $baseUsuario = 'docente';
        }
        $username = $this->generarUsernameUnico($baseUsuario, $persona->id);
        $email = $this->generarEmailUnico($username, $persona->id);
        $passwordPlano = $this->generarPasswordTemporal();

        $usuario = new User();
        $usuario->persona_id = $persona->id;
        $usuario->name = $username;
        $usuario->email = $email;
        $usuario->password = Hash::make($passwordPlano);
        if (!empty($persona->foto)) {
            $usuario->foto = $persona->foto;
        }
        $usuario->save();

        $guard = config('auth.defaults.guard', 'web');
        Role::firstOrCreate(['name' => 'Docente', 'guard_name' => $guard]);
        $usuario->assignRole('Docente');

        return redirect()->route('users.edit', $usuario->id)
            ->with('success', 'Usuario docente creado correctamente.')
            ->with('generated_credentials', [
                'name' => $usuario->name,
                'email' => $usuario->email,
                'password' => $passwordPlano,
            ]);
    }

    private function generarUsernameUnico(string $base, int $personaId): string
    {
        $candidato = substr($base, 0, 40) . $personaId;
        $candidato = substr($candidato, 0, 64);
        $i = 1;
        while (User::where('name', $candidato)->exists()) {
            $sufijo = (string) $i;
            $candidato = substr(substr($base, 0, 40) . $personaId, 0, 64 - strlen($sufijo)) . $sufijo;
            $i++;
        }

        return $candidato;
    }

    private function generarEmailUnico(string $username, int $personaId): string
    {
        $dominio = '@ife.bo';
        $maxLocal = 64 - strlen($dominio);
        $local = substr(Str::lower($username), 0, max(1, $maxLocal));
        $base = $local . $dominio;
        $email = $base;
        $i = 1;
        while (User::where('email', $email)->exists()) {
            $sufijo = '.' . $personaId . '.' . $i;
            $localConSufijo = substr($local, 0, max(1, $maxLocal - strlen($sufijo))) . $sufijo;
            $email = $localConSufijo . $dominio;
            $i++;
        }

        return $email;
    }

    private function generarPasswordTemporal(): string
    {
        return Str::upper(Str::random(3)) . '-' . Str::lower(Str::random(3)) . '-' . rand(100, 999) . '!';
    }



    public function docentesEstado($estado='HABILITADO'){
        // dd($estado);
        $docenteshabilitados=Docente::where('docentes.estado_id',estado($estado))->select('id','nombrecorto')->get();
        return $docenteshabilitados;
    }
   
    public function misestudiantes(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        $esDocente = $user->hasRole(['Docente']);
        $esSupervisor = $user->hasRole(['Admin', 'Secretaria']);

        abort_unless($esDocente || $esSupervisor, 403);

        $docentes = collect();
        $docenteSeleccionadoId = null;

        if ($esDocente) {
            $docenteSeleccionadoId = optional(optional($user->persona)->docente)->id;
            abort_unless($docenteSeleccionadoId, 403, 'El usuario no tiene perfil docente asociado.');
        } else {
            $docentes = Docente::with('persona:id,nombre,apellidop,apellidom')
                ->orderBy('nombrecorto')
                ->get();

            if ($docentes->isEmpty()) {
                $docenteSeleccionadoId = null;
            } else {
                $docenteSeleccionadoId = (int) $request->get('docente_id', 0);
                if ($docenteSeleccionadoId <= 0) {
                    $docenteSeleccionadoId = $docentes->first()->id;
                }
            }

            abort_unless(
                $docenteSeleccionadoId === null || $docentes->contains('id', $docenteSeleccionadoId),
                403
            );
        }

        $hoy = Carbon::now()->format('Y-m-d');
        $ahora = Carbon::now();

        $clases = collect();
        $clasescom = collect();
        if ($docenteSeleccionadoId) {
            $clases = Clase::with([
                'docente:id,nombrecorto',
                'materia:id,materia',
                'aula:id,aula',
                'programacion:id,inscripcione_id,docente_id,materia_id,aula_id,fecha,hora_ini,hora_fin',
                'programacion.inscripcione:id,estudiante_id,objetivo,vigente',
                'programacion.inscripcione.estudiante:id,persona_id',
                'programacion.inscripcione.estudiante.persona:id,nombre,apellidop,apellidom,foto',
                'programacion.observaciones' => function ($query) {
                    $query->where('activo', 1)->latest();
                },
            ])
                ->whereDate('fecha', $hoy)
                ->where('docente_id', $docenteSeleccionadoId)
                ->where('estado_id', estado('PRESENTE'))
                ->whereHas('programacion.inscripcione', function ($query) {
                    $query->where('vigente', 1);
                })
                ->orderBy('horainicio')
                ->get();

            $clasescom = Clasecom::with([
                'docente:id,nombrecorto',
                'aula:id,aula',
                'programacioncom:id,matriculacion_id,docente_id,aula_id,fecha,horaini,horafin',
                'programacioncom.matriculacion:id,computacion_id,asignatura_id',
                'programacioncom.matriculacion.computacion:id,persona_id',
                'programacioncom.matriculacion.computacion.persona:id,nombre,apellidop,apellidom,foto',
                'programacioncom.matriculacion.asignatura:id,asignatura',
                'programacioncom.observaciones' => function ($query) {
                    $query->where('activo', 1)->latest();
                },
            ])
                ->whereDate('fecha', $hoy)
                ->where('docente_id', $docenteSeleccionadoId)
                ->where('estado_id', estado('PRESENTE'))
                ->orderBy('horainicio')
                ->get();
        }

        $cantidadEnHorarioProgramacion = $clases->filter(function ($item) use ($ahora) {
            $inicio = Carbon::parse($item->horainicio);
            $fin = Carbon::parse($item->horafin);
            return $ahora->between($inicio, $fin, true);
        })->count();

        $cantidadEnHorarioProgramacioncom = $clasescom->filter(function ($item) use ($ahora) {
            $inicio = Carbon::parse($item->horainicio);
            $fin = Carbon::parse($item->horafin);
            return $ahora->between($inicio, $fin, true);
        })->count();

        $cantidadEnHorario = $cantidadEnHorarioProgramacion + $cantidadEnHorarioProgramacioncom;
        $totalAsignados = $clases->count() + $clasescom->count();

        return view('persona.misestudiantes', [
            'clases' => $clases,
            'clasescom' => $clasescom,
            'docentes' => $docentes,
            'docenteSeleccionadoId' => $docenteSeleccionadoId,
            'esSupervisor' => $esSupervisor,
            'cantidadEnHorario' => $cantidadEnHorario,
            'totalAsignados' => $totalAsignados,
            'hoy' => $hoy,
        ]);
    }

    public function alumnosDeHoy()
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole(['Docente']), 403);

        $docenteId = optional(optional($user->persona)->docente)->id;
        abort_unless($docenteId, 403, 'El usuario no tiene docente asociado.');

        $hoy = Carbon::now()->format('Y-m-d');
        $estadoPresenteId = (int) estado('PRESENTE');
        $estadoFinalizadoId = (int) estado('FINALIZADO');
        $estadoIndefinidoId = (int) estado('INDEFINIDO');

        $programaciones = Programacion::with([
            'materia:id,materia',
            'aula:id,aula',
            'estado:id,estado',
            'inscripcione:id,estudiante_id',
            'inscripcione.estudiante:id,persona_id',
            'inscripcione.estudiante.persona:id,nombre,apellidop,apellidom,foto',
            'clases' => function ($query) use ($hoy) {
                $query->whereDate('fecha', $hoy);
            },
        ])
            ->whereDate('fecha', $hoy)
            ->where('docente_id', $docenteId)
            ->orderBy('hora_ini')
            ->get()
            ->map(function ($item) use ($estadoPresenteId, $estadoFinalizadoId, $estadoIndefinidoId) {
                $persona = optional(optional(optional($item->inscripcione)->estudiante)->persona);
                $estadosClase = $item->clases->pluck('estado_id')->map(function ($id) {
                    return (int) $id;
                });
                $estadoLabel = 'Indefinido';
                $estadoBadge = 'warning';

                if ($estadosClase->contains($estadoPresenteId) || (int) $item->estado_id === $estadoPresenteId) {
                    $estadoLabel = 'Presentes';
                    $estadoBadge = 'success';
                } elseif ($estadosClase->contains($estadoFinalizadoId) || (int) $item->estado_id === $estadoFinalizadoId) {
                    $estadoLabel = 'Finalizado';
                    $estadoBadge = 'secondary';
                } elseif ($estadosClase->contains($estadoIndefinidoId) || (int) $item->estado_id === $estadoIndefinidoId) {
                    $estadoLabel = 'Indefinido';
                    $estadoBadge = 'warning';
                }

                return [
                    'tipo' => 'Nivelacion',
                    'nombre' => trim(($persona->nombre ?? '') . ' ' . ($persona->apellidop ?? '') . ' ' . ($persona->apellidom ?? '')),
                    'hora_inicio' => Carbon::parse($item->hora_ini)->format('H:i'),
                    'hora_fin' => Carbon::parse($item->hora_fin)->format('H:i'),
                    'aula' => optional($item->aula)->aula ?? '-',
                    'detalle' => optional($item->materia)->materia ?? '-',
                    'estado_label' => $estadoLabel,
                    'estado_badge' => $estadoBadge,
                ];
            });

        $programacionesCom = Programacioncom::with([
            'aula:id,aula',
            'estado:id,estado',
            'matriculacion:id,computacion_id,asignatura_id',
            'matriculacion.asignatura:id,asignatura',
            'matriculacion.computacion:id,persona_id',
            'matriculacion.computacion.persona:id,nombre,apellidop,apellidom,foto',
            'clasescom' => function ($query) use ($hoy) {
                $query->whereDate('fecha', $hoy);
            },
        ])
            ->whereDate('fecha', $hoy)
            ->where('docente_id', $docenteId)
            ->orderBy('horaini')
            ->get()
            ->map(function ($item) use ($estadoPresenteId, $estadoFinalizadoId, $estadoIndefinidoId) {
                $persona = optional(optional(optional($item->matriculacion)->computacion)->persona);
                $estadosClase = $item->clasescom->pluck('estado_id')->map(function ($id) {
                    return (int) $id;
                });
                $estadoLabel = 'Indefinido';
                $estadoBadge = 'warning';

                if ($estadosClase->contains($estadoPresenteId) || (int) $item->estado_id === $estadoPresenteId) {
                    $estadoLabel = 'Presentes';
                    $estadoBadge = 'success';
                } elseif ($estadosClase->contains($estadoFinalizadoId) || (int) $item->estado_id === $estadoFinalizadoId) {
                    $estadoLabel = 'Finalizado';
                    $estadoBadge = 'secondary';
                } elseif ($estadosClase->contains($estadoIndefinidoId) || (int) $item->estado_id === $estadoIndefinidoId) {
                    $estadoLabel = 'Indefinido';
                    $estadoBadge = 'warning';
                }

                return [
                    'tipo' => 'Computacion',
                    'nombre' => trim(($persona->nombre ?? '') . ' ' . ($persona->apellidop ?? '') . ' ' . ($persona->apellidom ?? '')),
                    'hora_inicio' => Carbon::parse($item->horaini)->format('H:i'),
                    'hora_fin' => Carbon::parse($item->horafin)->format('H:i'),
                    'aula' => optional($item->aula)->aula ?? '-',
                    'detalle' => optional(optional($item->matriculacion)->asignatura)->asignatura ?? 'Computacion',
                    'estado_label' => $estadoLabel,
                    'estado_badge' => $estadoBadge,
                ];
            });

        $alumnos = $programaciones
            ->concat($programacionesCom)
            ->sortBy('hora_inicio')
            ->values();

        return view('persona.alumnos_hoy', [
            'hoy' => $hoy,
            'programaciones' => $programaciones,
            'programacionesCom' => $programacionesCom,
            'alumnos' => $alumnos,
        ]);
    }

    public function estudiantesinscritos() 
    {
        
        $carrera = Carrera::with('computacion')->get();
        
        return view('persona.estudiantesinscritos',compact("carrera"));

    }

}


