<?php

namespace App\Http\Controllers;
use Illuminate\Support\Arr;
use App\Models\Persona;
use App\Models\Ciudad;
use App\Models\Estudiante;
use App\Models\Zona;
use App\Models\Administrativo;
use App\Models\Cliservicio;
use App\Models\Apoderado;
use App\Models\Docente;
use App\Models\Comentario;
use App\Models\Mensaje;
use App\Models\Proveedor;
use App\Models\Estado;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\PersonaStoreRequest;
use App\Http\Requests\PersonaUpdateRequest;
use App\Http\Requests\PersonaApoderadaRequestStore;
use App\Http\Requests\PersonaRapidingoGuardarRequest;
use App\Http\Requests\RequestStoreSoloContacto;
use App\Models\Inscripcione;
use App\Models\Matriculacion;
use App\Models\Observacion;
use App\Models\Computacion;
use App\Models\Interest;
use App\Models\Programacioncom;
use App\Models\Programacion;
use App\Models\Pais;
use App\Models\Como;
use App\Models\Felicitado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

use Intervention\Image\Facades\Image;
use Yajra\DataTables\Contracts\DataTable as DataTable; 
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CredencialesPadreNotification;
class PersonaController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Personas')->only("index","tomarfoto","show","verPotencial","contactos");
        $this->middleware('can:Crear Personas')->only("guardarSoloContacto","create","CrearContacto","guardarNuevoPapel","guardarfotojpg","guardarfoto","store","storeContacto","crearRapido","guardarRapidingo","crearSoloContacto");
        $this->middleware('can:Editar Personas')->only("editarSoloContacto","actualizarSoloContacto","edit","actualizarVolvera","actualizarVuelveFecha","configurar_papeles","update","actualizarRapidingo","editarRapidingo","unsuscribe");
    }

    private function syncPersonaWithGoogle(Persona $persona): void
    {
        app(\App\Services\GoogleContactsService::class)->markPending($persona, Auth::id());
    }

    public function index()
    {
        return view('persona.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $crmProspecto=$this->crmOrigen();
        $persona=$crmProspecto?new Persona(['nombre'=>$crmProspecto->nombre,'telefono'=>$crmProspecto->telefono,'papelinicial'=>request('crm_papel','contacto')]):null;
        $ciudades=Ciudad::get();
        $paises=Pais::get();
        $zonas=Zona::get();
        $interests=Interest::all();
        $comos=Como::all();
        $gcontactController  = app(GContactController::class);
        $tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php
        //dd($tiempoToken.", ".session('GContactTokenExpiration'));
        return view('persona.crear',compact('ciudades','paises','zonas','interests','comos','tiempoToken','persona','crmProspecto'));
    }
   

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PersonaStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
        
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
        }else {
            $persona->foto = "estudiantes/sinfoto.jpg";
        }
        $persona->como_id = $request->como_id;
        $persona->habilitado = 1;
        $persona->papelinicial = $request->papel;
        $persona->telefono=$request->telefono;
        $persona->persona_id = $request->persona_id;
        $persona->pais_id = 1;
        $persona->ciudad_id = 6;
        $persona->zona_id = $request->zona_id;
        $persona->save();
        $this->crmVincularNuevaPersona($request,$persona);

        $this->syncPersonaWithGoogle($persona);
        


        if(isset($request->comentario_id)){
            $comentario=Comentario::findOrFail($request->comentario_id);
            $comentario->persona_id=$persona->id;
            $comentario->vigente=0;
            $comentario->save();
        }

        $this->CrearContacto($persona->id);
        $persona->interests()->sync(array_keys($request->interests));
        if (in_array($request->papel, ['estudiante', 'computacion'], true)) {
            $correoBase = Str::slug($persona->nombre.$persona->apellidop, '');
            $correoGenerado = strtolower($correoBase.$persona->id)."@ife.bo";
            $plainPassword = Str::random(10);

            $user = new User();
            $user->email = $correoGenerado;
            $user->name = ucfirst(strtolower($persona->nombre))." ".ucfirst(strtolower($persona->apellidop));
            $user->persona_id = $persona->id;
            $user->password = Hash::make($plainPassword);
            $user->foto = "estudiantes/sinperfil.png";
            $user->save();

            $user->assignRole('Estudiante');
        }
        //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A    P E R S O N A   %%%%%%%%%%%%%%%%*/
        $persona->usuarios()->attach(Auth::user()->id);
        //dd($request->papel);
        switch ($request->papel) {
            case 'estudiante':
                $computacion=new Computacion();
                $computacion->persona_id=$persona->id;
                $computacion->save();

                $estudiante=new Estudiante();
                $estudiante->persona_id=$persona->id;
                $estudiante->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  E S T U D I A N T E   %%%%%%%%%%%%%%%%*/
                $estudiante->usuarios()->attach(Auth::user()->id);
                $computacion->usuarios()->attach(Auth::user()->id);
                
                $observacion=new Observacion();
                $observacion->observacion=$request->observacion;
                $observacion->activo=1;
                $observacion->observable_id=$persona->id;
                $observacion->observable_type="App\Models\Persona";
                $observacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
                $observacion->usuarios()->attach(Auth::user()->id);
                break;
            case 'computacion':
                $estudiante=new Estudiante();
                $estudiante->persona_id=$persona->id;
                $estudiante->save();

                $computacion=new Computacion();
                $computacion->persona_id=$persona->id;
                $computacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  E S T U D I A N T E   %%%%%%%%%%%%%%%%*/
                $computacion->usuarios()->attach(Auth::user()->id);
                $estudiante->usuarios()->attach(Auth::user()->id);
                
                $observacion=new Observacion();
                $observacion->observacion=$request->observacion;
                $observacion->activo=1;
                $observacion->observable_id=$persona->id;
                $observacion->observable_type="App\Models\Persona";
                $observacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
                $observacion->usuarios()->attach(Auth::user()->id);
                break;         
            case 'docente':
                $docente = new Docente();
                $docente->nombrecorto=$request->nombre.$request->apellidop;
                $docente->fecha_inicio=Carbon::now()->format('Y-m-d');
                $docente->dias_prueba = 3;
                $docente->sueldo =2000;
                $docente->mododocente_id = 1;
                $docente->perfil = "SIN PERFIL";
                $docente->estado_id =11;
                $docente->persona_id = $persona->id;
                $docente->save();
                
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  D O C E N T E   %%%%%%%%%%%%%%%%*/
                $docente->usuarios()->attach(Auth::user()->id);
                $observacion = new Observacion();
                $observacion->observacion = $request->observacion;
                $observacion->activo = 1;
                $observacion->observable_id = $persona->id;
                $observacion->observable_type = "App\Models\Persona";
                $observacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A  O B S E R V A C I O N   %%%%%%%%%%%%%%%%*/
                $observacion->usuarios()->attach(Auth::user()->id);

                break;
            case 'cliservicio':
                $cliservicio = new Cliservicio();
                $cliservicio->persona_id = $persona->id;
                $cliservicio->save();

                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   C L I S E R V I C I O   %%%%%%%%%%%%%%%%*/
                $cliservicio->usuarios()->attach(Auth::user()->id);
                break;
            case 'clicopy':
                // $clicopy = new Clicopy();
                // $clicopy->persona_id = $persona->id;
                // $clicopy->save();
                // //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   C L I C O P Y   %%%%%%%%%%%%%%%%*/
                // $clicopy->usuarios()->attach(Auth::user()->id);
                break;

            case 'administrativo':
                $administrativo = new Administrativo();
                $administrativo->cargo_id=1;
                $administrativo->diasprueba=3;
                $administrativo->estado_id=11;
                $administrativo->sueldo=2000;
                $administrativo->fechaingreso=Carbon::now()->format("Y-m-d");
                $administrativo->persona_id = $persona->id;
                $administrativo->save();
                $observacion=new Observacion();
                $observacion->observacion=$request->observacion;
                $observacion->activo=1;
                $observacion->observable_id=$persona->id;
                $observacion->observable_type="App\Models\Persona";
                $observacion->save();
                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   A D M I N I S T V R V A V T I V O  %%%%%%%%%%%%%%%%*/
                $administrativo->usuarios()->attach(Auth::user()->id);
                break;
            case 'proveedor':
                $proveedor = new Proveedor();
                $proveedor->persona_id = $persona->id;
                $proveedor->save();

                //**%%%%%%%%%%%%%%%%%%%%  B  I  T  A  C  O  R  A   P R O V E E D O R    %%%%%%%%%%%%%%%%*/
                $proveedor->usuarios()->attach(Auth::user()->id);
                break;
            default:
                # code...
                break;
        }
        return redirect()->route('tomar.foto.persona',['persona'=>$persona]);
        });
    }

    public function storeContacto(PersonaApoderadaRequestStore $request,$id){
        $persona=Persona::findOrFail($id);
        $apoderado = new Persona();
        $apoderado->nombre = $request->nombre;
        $apoderado->apellidop = $request->apellidop;
        $apoderado->apellidom = $request->apellidom;
        $apoderado->genero=$request->genero;
        $apoderado->fechanacimiento = $request->fechanacimiento;
        if ($request->hasFile('foto')) {
            $apoderado->foto = $request->file('foto')->store('apoderados', 'public');
        }
        $apoderado->pais_id = $persona->pais_id;
        $apoderado->ciudad_id = $persona->ciudad_id;
        $apoderado->zona_id = $persona->zona_id;
        $apoderado->habilitado = 0;
        $apoderado->votos = 0;

        $apoderado->telefono=$request->telefono;
        $apoderado->papelinicial = 'apoderado';
        $apoderado->save();

        $this->syncPersonaWithGoogle($apoderado);

        $this->CrearContacto($apoderado->id);

        $existingUser = User::where('persona_id', $apoderado->id)->first();
        if (!$existingUser) {
            $correoBase = Str::slug($apoderado->nombre.$apoderado->apellidop, '');
            $correoGenerado = strtolower($correoBase.$apoderado->id)."@ife.bo";
            $plainPassword = Str::random(10);

            $user = new User();
            $user->email = $correoGenerado;
            $user->name = ucfirst(strtolower($apoderado->nombre))." ".ucfirst(strtolower($apoderado->apellidop));
            $user->persona_id = $apoderado->id;
            $user->password = Hash::make($plainPassword);
            $user->foto = "estudiantes/sinperfil.png";
            $user->save();

            $padreRole = Role::firstOrCreate(['name' => 'Padre']);
            // Seguridad: el acceso del padre se gestiona por validacion padre-hijo en backend.
            // No otorgamos permisos globales de listado para evitar acceso horizontal.
            $padreRole->syncPermissions([]);
            $user->assignRole($padreRole);

            $notificables = User::role(['Admin','Secretaria'])->get();
            if ($notificables->isNotEmpty()) {
                Notification::send($notificables, new CredencialesPadreNotification($user, $plainPassword, $persona, route('telefonos.persona', ['persona' => $persona])));
            }
        }

        $apoderado->usuarios()->attach(Auth::user()->id);
        $observacion = new Observacion();
        $observacion->observacion = "Se registró a sistema como un apoderado";
        $observacion->activo = 1;
        $observacion->observable_id = $apoderado->id;
        $observacion->observable_type = "App\Models\Persona";
        $observacion->save();

        $observacion->usuarios()->attach(Auth::user()->id);

        $persona->apoderados()->attach($apoderado->id, ['telefono' => $request->telefono, 'parentesco' => $request->parentesco]);
        $this->syncPersonaWithGoogle($apoderado);
        $apoderados = $persona->apoderados;
        $this->CrearContacto($persona->id);
        return redirect()->route('telefonos.persona',['persona'=>$persona,'apoderados'=>$apoderados])->with('mensaje','Contacto Creado Corectamente');

    }

    /*$%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% METODOS DE RAPIDINGO INICIO %%%%%%%%%%%%%%%%%%%%%%% */
     public function crearRapido()
    {
        $interests=Interest::get();
        $comos=Como::get();
        $estados=Estado::orderBy('id','desc')->get();

        //$gcontactController  = app(GContactController::class);
        //$tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php

        return view('persona.rapidingo.crearrapido',compact('interests','comos','estados'));
    }
    
    public function guardarRapidingo(PersonaRapidingoGuardarRequest $request){
        
        $persona=new Persona();
        $persona->nombre = $request->nombre;
        $persona->apellidop = $request->apellidop;
        $persona->telefono=$request->telefono;
        $persona->genero=$request->genero;
        $persona->como_id = $request->como_id;
        $persona->vuelvefecha=$request->vuelvefecha;
        $persona->habilitado = $request->estado_id;
        $persona->votos = 1;
        $persona->papelinicial = 'estudiante';
        $persona->save();
        


        $this->syncPersonaWithGoogle($persona);


        $this->CrearContacto($persona->id);

        $persona->interests()->sync(array_keys($request->interests));
        $persona->usuarios()->attach(Auth::user()->id);
        $observacion = new Observacion();
        $observacion->observacion = $request->observacion;
        $observacion->activo = 1;
        $observacion->observable_id = $persona->id;
        $observacion->observable_type = "App\Models\Persona";
        $observacion->save();
        $observacion->usuarios()->attach(Auth::user()->id);
        return redirect()->route('telefonos.crear',['persona'=>$persona])->with('mensaje','Contacto Creado Corectamente');
        
    }
    public function actualizarRapidingo(Request $request,Persona $persona){
        $request->validate(['telefono' => 'nullable|max:10']);
        //dd($persona);
        $persona->nombre = $request->nombre;
        $persona->apellidop = $request->apellidop;
        if ($request->has('telefono')) {
            $persona->telefono = $request->telefono;
        }
        $persona->genero=$request->genero;
        $persona->como_id = $request->como_id;
        $persona->vuelvefecha=$request->vuelvefecha;
         $persona->habilitado = $request->estado_id;
        $persona->votos = 1;
        $persona->papelinicial = 'estudiante';
        $persona->save();
        
        $this->syncPersonaWithGoogle($persona);

        //dd($persona);
        $this->CrearContacto($persona->id);

        $persona->interests()->sync(array_keys($request->interests));
        
        $observacion =$persona->observaciones->first();
        $observacion->observacion = $request->observacion;
        $observacion->save();
        return redirect()->route('telefonos.crear',['persona'=>$persona])->with('mensaje','Contacto Creado Corectamente');
    }

    public function editarRapidingo(Persona $persona){
        // $p=Persona::findOrFail();
        $observacion=$persona->observaciones->first()->observacion;
        $interests_currents=$persona->interests; 
        $ids=[];
        foreach ($interests_currents as $interest) {
            $ids[] = $interest->id;
        }
        $interests_faltantes = Interest::whereNotIn('id', $ids)->get();
        $comos=Como::get();
        $estados=Estado::orderBy('id','desc')->get();


        $gcontactController  = app(GContactController::class);
        $tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php

        return view('persona.rapidingo.editarrapido',compact('tiempoToken','persona','comos','interests_currents','interests_faltantes','observacion','estados'));
    }
    public function potenciales(){
        $potenciales= Persona::join('interest_persona','interest_persona.persona_id','personas.id')
        ->join('interests','interests.id','interest_persona.interest_id')
        ->orWhere('habilitado','=',0)
        ->orWhere('habilitado','>',10)
        ->where('votos',1)
        ->select('personas.id','personas.nombre','personas.apellidop','apellidom','interests.interest','volvera','vuelvefecha')  
        ->get();

        return DataTables::of($potenciales)
                ->addColumn('btn','persona.rapidingo.actionpotenciales')
                ->rawColumns(['btn'])
                ->toJson();
    }
    public function verPotencial(Request $request){
        $potencial= Persona::findOrFail($request->persona_id);
        $observaciones=Observacion::join('personas','personas.id','observacions.observable_id')
        ->join('userables','userables.userable_id','observacions.id')
        ->join('users','users.id','userables.user_id') 
        ->where('userables.userable_type',Observacion::class)
        ->where('observacions.observable_type',Persona::class)
        ->where('personas.id',$request->persona_id)
        ->select('observacions.id','observacion','name')
        ->get();
        $apoderados=$potencial->apoderados;
        $interests=$potencial->interests;
        $autorPotencial=$potencial->usuarios->first()->name;

        $data=['intereses'=>$interests,'potencial'=>$potencial,'observaciones'=>$observaciones,'apoderados'=>$apoderados,'autorPotencial'=>$autorPotencial];
        return response()->json($data);
    }

    public function unsuscribe(Request $request){
        $persona= Persona::findOrFail($request->persona_id);
        $persona->votos=0;
        $persona->save();
        return response()->json(["mensaje"=>"El cambio se realizo correctamente"]);
    }
    public function ImprimirPotencial($persona_id){
        $inscripcion=Persona::findOrFail($inscripcione_id);
        $programacion = Persona::join('materias', 'programacions.materia_id', '=', 'materias.id')
            ->join('aulas', 'programacions.aula_id', '=', 'aulas.id')
            ->join('docentes', 'programacions.docente_id', '=', 'docentes.id')
            ->join('personas', 'personas.id', '=', 'docentes.persona_id')
            ->select('programacions.fecha', 'hora_ini','programacions.habilitado', 'hora_fin', 'horas_por_clase', 'personas.nombre', 'materias.materia', 'aulas.aula', 'programacions.habilitado')
            ->orderBy('fecha', 'asc')
            ->where('inscripcione_id', '=', $inscripcione_id)->get();
        $estudiante = Estudiante::findOrFail($inscripcion->estudiante_id);
        $persona = $estudiante->persona;
        $colegio=Colegio::find($estudiante->grados->last()->pivot->colegio_id);
        $usuario=$inscripcion->usuarios->first();
        $modalidad=$inscripcion->modalidad;
        $nivel=Nivel::findOrFail($estudiante->grados->last()->nivel_id);
        $grado=Grado::findOrFail($estudiante->grados->last()->pivot->grado_id);
        $dompdf = PDF::loadView('programacion.reporte', compact('grado','nivel','modalidad','usuario','programacion','persona','estudiante','persona','colegio','inscripcion'));
        /**entrae a la persona al cual corresponde esta inscripcion */
        $fecha_actual = Carbon::now();
        $fecha_actual->isoFormat('DD-MM-YYYY-HH:mm:ss');
        $dompdf->setPaper('letter','portrait');
        return $dompdf->download($persona->id . '_' . $fecha_actual . '_' . $persona->nombre . '_' . $persona->apellidop . '.pdf');
    }
    /*$%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% METODOS DE RAPIDINGO %%%%%%%%%%%%%%%%%%%%%%% */

    /*$%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% METODOS DE CONTACTO UNICAMENTE %%%%%%%%%%%%%%%%%%%%%%% */
    public function crearSoloContacto()
    {
        $crmProspecto=$this->crmOrigen();
        $persona=$crmProspecto?new Persona(['nombre'=>$crmProspecto->nombre,'telefono'=>$crmProspecto->telefono]):null;
        $comos=Como::get();
        $gcontactController  = app(GContactController::class);
        $tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php
        
        return view('persona.contacto.crearcontacto',compact('comos','tiempoToken','crmProspecto','persona'));
    }
    public function guardarSoloContacto(RequestStoreSoloContacto $request){
        return DB::transaction(function () use ($request) {
        // dd($request->all()   );
        $persona=new Persona();
        $persona->nombre=$request->nombre;
        $persona->apellidop=$request->apellidop;
        $persona->telefono=$request->telefono;
        $persona->papelinicial='contacto';
        $persona->como_id=$request->como_id;
        
        $persona->save();
        $this->crmVincularNuevaPersona($request,$persona);
        $this->syncPersonaWithGoogle($persona);
        $persona->usuarios()->attach(Auth::user()->id);
        $this->CrearContacto($persona->id);
        $observacion = new Observacion();
        $observacion->observacion = $request->observacion;
        $observacion->activo = 1;
        $observacion->observable_id = $persona->id;
        $observacion->observable_type = "App\Models\Persona";
        $observacion->save();
        $observacion->usuarios()->attach(Auth::user()->id);
        return $request->filled('crm_prospecto_id')?redirect()->route('prospecto.view',$request->crm_prospecto_id):redirect()->route('personas.contactos.view');
        });
    }
    private function crmOrigen()
    {
        if (!request()->filled('crm_prospecto')) { return null; }
        request()->validate(['crm_prospecto'=>'required|integer|exists:prospectos,id','crm_papel'=>'sometimes|in:contacto,estudiante,computacion']);
        $record=\App\Models\Prospecto::findOrFail(request('crm_prospecto'));
        $this->authorize('update',$record);
        abort_unless(app(\App\Services\CrmService::class)->ready()&&!$record->persona_id&&$record->crm_status==='activo',422,'Revise la oportunidad antes de registrar otra Persona.');
        return $record;
    }
    private function crmVincularNuevaPersona(Request $request, Persona $persona): void
    {
        if (!$request->filled('crm_prospecto_id')) { return; }
        $record=\App\Models\Prospecto::findOrFail($request->crm_prospecto_id);
        $this->authorize('update',$record);
        app(\App\Services\CrmService::class)->linkNewContact($record,$persona,(int)$request->crm_prospecto_version,(int)$request->user()->id);
    }
    public function actualizarSoloContacto(Request $request, Persona $persona){
        $request->validate(['telefono' => 'nullable|max:10']);
        
        $persona->nombre=$request->nombre;
        $persona->apellidop=$request->apellidop;
        if ($request->has('telefono')) {
            $persona->telefono = $request->telefono;
        }
        $persona->como_id=$request->como_id;
        $persona->save();

        $this->syncPersonaWithGoogle($persona);
        
        $this->CrearContacto($persona->id);
        $observacion =$persona->observaciones->first();
        $observacion->observacion = $request->observacion;
        $observacion->save();
        return redirect()->route('personas.contactos.view');
    }
    public function contactos(){
        return view('persona.contacto.index');
    }
    public function editarSoloContacto(Persona $persona){
        $observacion=$persona->observaciones->first()->observacion;
        $comos=Como::get();

        $gcontactController  = app(GContactController::class);
        $tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php

        return view('persona.contacto.editarcontacto',compact('tiempoToken','persona','observacion','comos'));
    }
    public function listarContactos(){
        $contactos=Persona::where('papelinicial','contacto')
            ->select('id','nombre','apellidop','apellidom','telefono','created_at','updated_at')
            ->orderBy('created_at','desc')
            ->get();
          
        return datatables()->of($contactos)
        ->addColumn('btn', 'persona.contacto.action')
        ->rawColumns(['btn'])
        ->toJson();
    }

    /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%  fin metodo de contacto %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% */
    /**
     * Display the specified resource.
     *
     * @param  \App\Persona  $persona
     * @return \Illuminate\Http\Response
     */
    public function show(Persona $persona)
    {   
        // dd(isset($persona->zona_id));
        if(!isset($persona->zona_id)){
            return redirect()->route('personas.edit',$persona);
        }
        $pais=Pais::findOrFail($persona->pais_id);
        $ciudad = Ciudad::findOrFail($persona->ciudad_id);
        $zona = Zona::findOrFail($persona->zona_id);
        
        $observacion = Observacion::where('observable_id', $persona->id)
        ->where('observable_type', Persona::class)->get();
        //dd($persona);
        //dd($observacion);
        if(count($observacion)>0){
            $observacion=$observacion->first();
        }   

        $recomendado=Persona::find($persona->persona_id); 
        $apoderados=$persona->apoderados;

        $calificaciones=Persona::join('calificacions','personas.id','calificacions.persona_id')
            ->join('users','users.id','calificacions.user_id')
            ->where('calificacions.persona_id',$persona->id)
            ->select('calificacion','users.foto','calificacions.created_at')
            ->get();
        $calificado=$persona->calificaciones->where('user_id',Auth::user()->id)->count();
        $promedio=round($persona->calificaciones->avg('calificacion'),1);

        if($persona->calificaciones->where('user_id',Auth::user()->id)->first()!=null)
            $micalificacion=$persona->calificaciones->where('user_id',Auth::user()->id)->first()->calificacion;
        else 
            $micalificacion=null;
        $user=$persona->usuarios->first();
        $observaciones=$persona->observaciones;
        return view('persona.mostrar',compact('persona','pais','ciudad','zona','observacion','recomendado','apoderados','calificado','promedio','calificaciones','micalificacion','user','observaciones'));
    }

    public function personaMostrarAjax(Request $request){
        $persona = Persona::findOrFail($request->persona_id);
        $pais=Pais::findOrFail($persona->pais_id);
        $ciudad=Ciudad::findOrFail($persona->ciudad_id);
        $zona=Zona::findOrFail($persona->zona_id);
        $edad=$persona->fechanacimiento->diffForHumans();
        $creado=$persona->created_at->diffForHumans();
        $actualizado=$persona->updated_at->diffForHumans();
        $data=['persona'=>$persona, 'pais'=>$pais, 'ciudad'=>$ciudad, 'zona'=>$zona,'edad'=>$edad,'creado'=>$creado, 'actualizado'=>$actualizado];
        return response()->json($data);
    }
    public function personaMostrarAjaxInscripcion(Request $request){
        $persona = Inscripcione::findOrFail($request->inscripcion_id)->estudiante->persona;
        $pais=Pais::findOrFail($persona->pais_id);
        $ciudad=Ciudad::findOrFail($persona->ciudad_id);
        $zona=Zona::findOrFail($persona->zona_id);
        $edad=$persona->fechanacimiento->diffForHumans();
        $creado=$persona->created_at->diffForHumans();
        $actualizado=$persona->updated_at->diffForHumans();
        $data=['persona'=>$persona, 'pais'=>$pais, 'ciudad'=>$ciudad, 'zona'=>$zona,'edad'=>$edad,'creado'=>$creado, 'actualizado'=>$actualizado];
        return response()->json($data);
    }

    public function personaMostrarAjaxMatriculacion(Request $request){
        //return response()->json($request->all());
        $persona = Matriculacion::findOrFail($request->matriculacion_id)->computacion->persona;
        $pais=Pais::findOrFail($persona->pais_id);
        $ciudad=Ciudad::findOrFail($persona->ciudad_id);
        $zona=Zona::findOrFail($persona->zona_id);
        $edad=$persona->fechanacimiento->diffForHumans();
        $creado=$persona->created_at->diffForHumans();
        $actualizado=$persona->updated_at->diffForHumans();
        $data=['persona'=>$persona, 'pais'=>$pais, 'ciudad'=>$ciudad, 'zona'=>$zona,'edad'=>$edad,'creado'=>$creado, 'actualizado'=>$actualizado];
        return response()->json($data);
    }



    public function edit(Persona $persona)
    {
        $ciudades = Ciudad::get();
        $paises = Pais::get();
        $zonas = Zona::get();
        $comos = Como::get();
        $observacion = Observacion::where('observable_id', $persona->id)
            ->where('observable_type', Persona::class)->get()->first();
        if($observacion!=null){
            $observacion=$observacion->observacion;
        }
        $interests_currents=$persona->interests; 
        $ids=[];
        foreach ($interests_currents as $interest) {
            $ids[] = $interest->id;
        }
        $interests_faltantes = Interest::whereNotIn('id', $ids)->get();
        //dd($interests_faltantes);
        
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
        $estados=Estado::get();

        $gcontactController  = app(GContactController::class);
        $tiempoToken = tiempoEnSegundos($gcontactController->getTokenExpiration());  // metodo esta en Helper.php
        
        return view("persona.editar",compact("tiempoToken",'persona','estados','paises','comos','ciudades','zonas','observacion','interests_currents','interests_faltantes'));
    } 

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Persona  $persona
     * @return \Illuminate\Http\Response
     */
    public function update(PersonaUpdateRequest $request, Persona $persona)
    {
        
        $observacion=$persona->observaciones;
        $persona->nombre = $request->nombre;
        $persona->apellidop = $request->apellidop;
        $persona->apellidom = $request->apellidom;
        $persona->fechanacimiento = $request->fechanacimiento;
        $persona->direccion = $request->direccion;
        $persona->carnet = $request->carnet;
        $persona->expedido = $request->expedido;
        $persona->genero = $request->genero;
        $persona->habilitado = $request->estado_id;
        $persona->votos=1;
        
        if ($request->hasFile('foto')) {
            if (Storage::disk('public')->exists($persona->foto)) {
                Storage::disk('public')->delete($persona->foto);
            }
            $foto = $request->file('foto');
            $nombreImagen = 'estudiantes/' . Str::random(20) . '.jpg';
            $imagen = Image::make($foto)->encode('jpg', 75);
            $imagen->resize(300, 300, function ($constraint) {
                $constraint->upsize();
            });
            $fotillo = Storage::disk('public')->put($nombreImagen, $imagen->stream());
            $persona->foto = $nombreImagen;
        }
        $persona->como_id = $request->como_id;
        $persona->papelinicial = $request->papel;
        if ($request->has('telefono')) {
            $persona->telefono = $request->telefono;
        }
        $persona->persona_id = $request->persona_id;
        // $persona->pais_id = $request->pais_id;
        // $persona->ciudad_id = $request->ciudad_id;
        $persona->zona_id = $request->zona_id;
        $persona->save();

        $this->syncPersonaWithGoogle($persona);


        $persona->interests()->sync(array_keys($request->interests));
        $observacion_actual = Observacion::where('observable_id',$persona->id)
        ->where('observable_type',Persona::class)->get()->first();

        if($observacion_actual!=null)
        {
            $observacion_actual->observacion = $request->observacion;
            $observacion_actual->save();
            $observacion=$observacion_actual;
        }else{
            $observacion = new Observacion();
            $observacion->observacion = $request->observacion;
            $observacion->activo = 1;
            $observacion->observable_id = $persona->id;
            $observacion->observable_type = Persona::class;
            $observacion->save();
        }
        
        //dd($persona->resourseName);
        /**%%%%%%%%%%%%%%%%%%%%% google contact editar inicio %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        // $gcontacController = app()->make(GContactController::class);
        // $etag=$gcontacController->updateContact($persona->nombre,$persona->apellidop,$persona->apellidom,$persona->telefono,$persona->resourseName,$persona->etag);
        // $persona->etag=$etag;
        // $persona->save();
        /**%%%%%%%%%%%%%%%%%%%%% google contact editar Fin %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
        return redirect()->Route('personas.show', ['persona' => $persona]);
    }

    public function guardarfoto(Request $request, Persona $persona){
        $image = $request->imagen;  // your base64 encoded
        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace(' ', '+', $image);
        $imageName = 'estudiantes/'.Str::random(20) . '.' . 'png';
        Storage::disk('public')->put($imageName, base64_decode($image));
        //Storage::put(storage_path() . '/public/App/' . $imageName, base64_decode($image));
        $persona->foto=$imageName;
        $persona->save();
        $user=$persona->user;
        $user->foto=$persona->foto;
        $user->save();

        return redirect()->Route('telefonos.crear',['persona'=>$persona]);
    }
    public function guardarfotojpg(Request $request, Persona $persona){
        //dd($request->all());
        
        if ($request->hasFile('foto')) {
            $foto = $request->file('foto');
            $nombreImagen = 'estudiantes/' . Str::random(20) . '.jpg';
            /** las convertimos en jpg y la redimensionamos */
            $imagen = Image::make($foto)->encode('jpg', 100);
            $imagen->resize(800, 800, function ($constraint) {
                $constraint->upsize();
            });
            /* las guarda en en la carpeta estudiantes  */
            $fotillo = Storage::disk('public')->put($nombreImagen, $imagen->stream());
            
            $persona->foto = $nombreImagen;
            $persona->save();
        }else{
            $persona->foto = "estudiantes/sinfoto.jpg";
        }

        return redirect()->Route('telefonos.crear',['persona'=>$persona]);
    }

    public function tomarfoto(Persona $persona){
        return view('persona.tomarfoto', compact('persona'));
    }
    
    public function configurar_papeles($persona_id){

        $persona= Persona::findOrFail($persona_id);
        //dd($persona);
        $papelesActuales=collect([]);
        $papelesFaltantes=collect([]);
        
        if($persona->estudiante == null){
            $papelesFaltantes->push('estudiante');
        }else{
            $papelesActuales->push('estudiante');
        }
        if($persona->computacion == null){
            $papelesFaltantes->push('computacion');
        }else{
            $papelesActuales->push('computacion');
        }
        if($persona->docente == null){
            $papelesFaltantes->push('docente');
        }else{
            $papelesActuales->push('docente');
        }
        if($persona->administrativo == null){
            $papelesFaltantes->push('administrativo');
        }else{
            $papelesActuales->push('administrativo');
        }
        
        if($persona->cliservicio == null){
            $papelesFaltantes->push('cliservicio');
        }else{
            $papelesActuales->push('cliservicio');
        }
        //d($persona);

        return view('persona.papeles',compact('papelesActuales','papelesFaltantes','persona'));
    }

    public function guardarNuevoPapel(Request $request,$persona_id){
        $persona=Persona::findOrFail($persona_id);
        if(isset($request->papelesFalta))
            $cuantas_papeles=count($request->papelesFalta);
        else 
            $cuantas_papeles=0;
        $i=0;
        $c="";
        $nuevosPapeles=$request->papelesFalta;
        while($i<$cuantas_papeles){
            if($nuevosPapeles[$i]=='estudiante'){
                Estudiante::create([
                    'persona_id'=>$persona->id,
                ]);
            }
            if($nuevosPapeles[$i]=='computacion'){
                Computacion::create([
                    'persona_id'=>$persona->id,
                ]);
            }
            if($nuevosPapeles[$i]=='docente'){
                
                $docente= new Docente();
                $docente->nombrecorto=$persona->nombre." ". $persona->apellidop;
                $docente->fecha_inicio= Carbon::now()->format('Y-m-d');
                $docente->dias_prueba=2;
                $docente->sueldo=0;
                $docente->perfil="Esto es un perfil inicial";
                $docente->mododocente_id=1;
                $docente->estado_id=estado("HABILITADO");
                $docente->persona_id=$persona->id;
                $docente->save();
            }
            if($nuevosPapeles[$i]=='administrativo'){
                $administrativo=new Administrativo();
                $administrativo->cargo_id=1;
                $administrativo->fechaingreso=Carbon::now()->format('Y-m-d');
                $administrativo->diasprueba=3;
                $administrativo->estado_id=11;
                $administrativo->sueldo=2000;
                $administrativo->persona_id=$persona->id;
                $administrativo->save();
            
            }
            if($nuevosPapeles[$i]=='cliservicio'){
                Cliservicio::create([
                    'persona_id'=>$persona->id,
                    'requerimiento'=>"Esto es un requerimiento",
                ]);
            }
            $i=$i+1;
        }
        return redirect()->route('personas.index');
    }
    
    public function ultimaObservacion(Request $request){
        $observacion=Persona::findOrFail($request->persona_id)->observaciones->last();
        $usuario=$observacion->usuarios->first();
        $data=['observacion'=>$observacion,'usuario'=>$usuario];
        return response()->json($data);
    }
    public function primeraObservacion(Request $request){
        $observacion=Persona::findOrFail($request->persona_id)->observaciones->first();
        $usuario=$observacion->usuarios->first();
        $data=['observacion'=>$observacion,'usuario'=>$usuario];
        return response()->json($data);
    }
    public function ultimaPrimeraObservacion(Request $request){
        $observacionFirst=Persona::findOrFail($request->persona_id)->observaciones->first();
        $usuarioFirst=$observacionFirst->usuarios->first();
        $observacionLast=Persona::findOrFail($request->persona_id)->observaciones->last();
        $usuarioLast=$observacionLast->usuarios->first();
        $data=['observacionFirst'=>$observacionFirst,
                'usuarioFirst'=>$usuarioFirst,
                'observacionLast'=>$observacionLast,
                'usuarioLast'=>$usuarioLast];
        return response()->json($data);
    }

     public function ultimaInscripcion(Request $request){
        $inscripcion=Persona::findOrFail($request->persona_id)->estudiante->inscripciones->last();
        $materia=$inscripcion->materia;
        $usuario=$inscripcion->usuarios->first();
        $motivo=$inscripcion->motivo;
        $empezo=$inscripcion->fechaini->diffForHumans();    
        $finaliza=$inscripcion->fechafin->diffForHumans();    
        $proximo_pago=$inscripcion->fecha_proximo_pago->diffForHumans();    
        $creado=$inscripcion->created_at->diffForHumans();
        $actualizado=$inscripcion->updated_at->diffForHumans();
        $data=[
            'inscripcion'=>$inscripcion,
            'usuario'=>$usuario,
            'materia'=>$materia,
            'motivo'=>$motivo,
            'empezo'=>$empezo,
            'finaliza'=>$finaliza,
            'proximo_pago'=>$proximo_pago,
            'creado'=>$creado, 
            'actualizado'=>$actualizado,
        ];

        return response()->json($data);
    }
    public function ultimaProgramacion(Request $request){
        $inscripcion=Persona::findOrFail($request->persona_id)->estudiante->inscripciones->last();
        // $inscripcion=Persona::findOrFail(2)->estudiante->inscripciones->last();
        
        $programaciones=Programacion::join('docentes','docentes.id','=','programacions.docente_id')
                    ->join('aulas','aulas.id','=','programacions.aula_id')
                    ->join('inscripciones','inscripciones.id','=','programacions.inscripcione_id')
                    ->join('materias','materias.id','=','programacions.materia_id')
                    ->join('estados','estados.id','=','programacions.estado_id')
                    ->where('inscripcione_id',$inscripcion->id)
                    ->select('programacions.id','fecha','estados.estado','materia','docentes.nombrecorto','programacions.hora_ini','programacions.hora_fin','horas_por_clase','aulas.aula','programacions.habilitado')->get();
        return response()->json($programaciones);
    }

    // $programaciones = Programacion::join('aulas', 'programacions.aula_id', '=', 'aulas.id')
    //     ->join('docentes', 'programacions.docente_id', '=', 'docentes.id')
    //     ->join('materias', 'programacions.docente_id', '=', 'docentes.id')
    //     ->join('estados','estados.id','programacions.estado_id')
    //     ->where('programacions.inscripcione_id', '=', $inscripcion->id)
    //     ->select('programacions.fecha','nombre','hora_ini','estados.estado','programacions.habilitado','materias.materia', 'hora_fin', 'horas_por_clase', 'aulas.aula')
    //     ->orderBy('fecha', 'asc')->get();

    public function ultimaMatriculacion(Request $request){
        
        $matriculacion=Persona::findOrFail($request->persona_id)->computacion->matriculaciones->last();
        $asignatura=$matriculacion->asignatura;
        $usuario=$matriculacion->usuarios->first();
        $motivo=$matriculacion->motivo;
        $empezo=$matriculacion->fechaini->diffForHumans();    
        $finaliza=$matriculacion->fechafin->diffForHumans();    
        $proximo_pago=$matriculacion->fecha_proximo_pago->diffForHumans();    
        $creado=$matriculacion->created_at->diffForHumans();
        $actualizado=$matriculacion->updated_at->diffForHumans();

        $data=[
            'matriculacion'=>$matriculacion,
            'usuario'=>$usuario,
            'asignatura'=>$asignatura,
            'motivo'=>$motivo,
            'empezo'=>$empezo,
            'finaliza'=>$finaliza,
            'proximo_pago'=>$proximo_pago,
            'creado'=>$creado, 
            'actualizado'=>$actualizado,
        ];

        return response()->json($data);
    }

    public function ultimaProgramacioncom(Request $request){
         $matriculacion=Persona::findOrFail($request->persona_id)->computacion->matriculaciones->last();
         $programacioncoms = Programacioncom::join('aulas', 'programacioncoms.aula_id', '=', 'aulas.id')
            ->join('docentes', 'programacioncoms.docente_id', '=', 'docentes.id')
            ->join('estados','estados.id','programacioncoms.estado_id')
            ->select('programacioncoms.fecha','nombrecorto', 'habilitado','horaini','estados.estado', 'horafin', 'horas_por_clase', 'aulas.aula')
            ->orderBy('fecha', 'asc')
            ->where('programacioncoms.matriculacion_id', '=', $matriculacion->id)->get();
        return response()->json($programacioncoms);
    }

    public function enviarMensaje(Request $request){
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;
        $mensaje= saludo()."%0A".nombre($request->persona_id,1)."%0A".strip_tags(Mensaje::findOrFail(4)->mensaje);
        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje];
        return response()->json($data);
    }
    public function enviarMensajeParaComponente(Request $request){
        $persona_id=$request->persona_id;
        // $persona_id=2;
        $persona=Persona::findOrFail($persona_id);
        $apoderados= $persona->apoderados;
        $mensaje= saludo()."%0A".nombre($persona_id,1)."%0A".strip_tags(Mensaje::findOrFail(4)->mensaje);

        $normalizarTelefono = function ($telefono) {
            $digits = preg_replace('/\D+/', '', (string) $telefono);
            if ($digits === '') {
                return null;
            }
            if (Str::startsWith($digits, '591')) {
                return $digits;
            }
            if (strlen($digits) === 8) {
                return '591'.$digits;
            }
            return $digits;
        };

        $contactos = collect();
        $numerosYaAgregados = [];

        $telefonoPersonaWa = $normalizarTelefono($persona->telefono);
        if ($telefonoPersonaWa) {
            $numerosYaAgregados[] = $telefonoPersonaWa;
            $contactos->push([
                'id' => 'persona-'.$persona->id,
                'nombre' => $persona->nombre,
                'apellidop' => $persona->apellidop,
                'parentesco' => 'ESTUDIANTE',
                'telefono' => $telefonoPersonaWa,
                'telefono_wa' => $telefonoPersonaWa,
                'updated_at' => optional($persona->updated_at)->format('Y-m-d H:i:s'),
            ]);
        }

        foreach ($apoderados as $apoderado) {
            $telefonosCandidatos = [
                optional($apoderado->pivot)->telefono,
                $apoderado->telefono,
            ];

            foreach ($telefonosCandidatos as $telefonoCandidato) {
                $telefonoWa = $normalizarTelefono($telefonoCandidato);
                if (!$telefonoWa || in_array($telefonoWa, $numerosYaAgregados, true)) {
                    continue;
                }
                $numerosYaAgregados[] = $telefonoWa;
                $contactos->push([
                    'id' => 'apoderado-'.$apoderado->id.'-'.$telefonoWa,
                    'nombre' => $apoderado->nombre,
                    'apellidop' => $apoderado->apellidop,
                    'parentesco' => optional($apoderado->pivot)->parentesco ?: 'APODERADO',
                    'telefono' => $telefonoWa,
                    'telefono_wa' => $telefonoWa,
                    'updated_at' => optional($apoderado->updated_at)->format('Y-m-d H:i:s'),
                ]);
            }
        }

        return DataTables::of($contactos)
            ->addColumn('btn','persona.deudores.actionwhatsapp')
            ->rawColumns(['btn'])
            ->toJson();
    }

    public function Saludar(Request $request){
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;
        $mensaje= saludo()."%0A".nombre($request->persona_id,1)."%0A";
        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje];
        return response()->json($data);
    }
    public function enviarGenericoSaludoMensaje(Request $request){
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;
        $mensaje= saludo()."%0A".nombre($request->persona_id,$request->modo_nombre)."%0A".strip_tags(Mensaje::findOrFail($request->mensaje_id)->mensaje);;
        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje];
        return response()->json($data);
    }
    public function enviarMensajePersonal(Request $request){
        $persona=Persona::findOrFail($request->persona_id);
        $mensaje= saludo()."%0A".nombre($request->persona_id,1)."%0A".strip_tags(Mensaje::findOrFail(4)->mensaje);
        $data=['persona'=>$persona,'mensaje'=>$mensaje];
        return response()->json($data);
    }

    public function enviarCredencialesWhatsapp(Persona $persona)
    {
        if (!Auth::user()->hasRole(['Admin','Secretaria'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $userEstudiante = User::where('persona_id', $persona->id)->first();
        if (!$userEstudiante) {
            return response()->json(['message' => 'No existe usuario para el estudiante'], 404);
        }

        $userPadre = null;
        $personaPadre = null;
        foreach ($persona->apoderados as $apoderado) {
            $userPadre = User::where('persona_id', $apoderado->id)->first();
            if ($userPadre) {
                $personaPadre = $apoderado;
                break;
            }
        }

        if (!$userPadre || !$personaPadre) {
            return response()->json(['message' => 'No existe usuario para el padre/tutor'], 404);
        }

        $telefonoBase = $personaPadre->telefono ?: $persona->telefono;
        $telefono = preg_replace('/\D+/', '', (string) $telefonoBase);
        if ($telefono === '') {
            return response()->json(['message' => 'El estudiante o el padre no tienen teléfono registrado'], 422);
        }

        if (Str::startsWith($telefono, '591')) {
            $telefonoWa = $telefono;
        } elseif (strlen($telefono) === 8) {
            $telefonoWa = '591'.$telefono;
        } else {
            $telefonoWa = $telefono;
        }

        $plainPasswordEstudiante = Str::random(10);
        $userEstudiante->password = Hash::make($plainPasswordEstudiante);
        $userEstudiante->save();

        $plainPasswordPadre = Str::random(10);
        $userPadre->password = Hash::make($plainPasswordPadre);
        $userPadre->save();

        $appUrl = URL::to('/');
        $mensaje = "Hola, se generaron las credenciales para el Portal IFE.%0A%0A".
            "ESTUDIANTE:%0A".
            "Usuario: {$userEstudiante->email}%0A".
            "Contraseña: {$plainPasswordEstudiante}%0A%0A".
            "PADRE/TUTOR:%0A".
            "Usuario: {$userPadre->email}%0A".
            "Contraseña: {$plainPasswordPadre}%0A%0A".
            "Acceso: {$appUrl}%0A%0A".
            "Desde la app podrás ver asistencias, pagos, faltas, horarios de entrada/salida y más detalles.";

        $whatsappUrl = "https://wa.me/{$telefonoWa}?text={$mensaje}";

        return response()->json(['whatsapp_url' => $whatsappUrl]);
    }
    
    public function enviarMensajeCumpleanero(Request $request){
        // $mensaje=saludo()."%0A".nombre($request->persona_id,2)."%0A".strip_tags(Mensaje::findOrFail(1)->mensaje);
        $mensaje=saludo()."%0AEstimado(a) ".nombre($request->persona_id,2)."%0A".strip_tags(Mensaje::findOrFail(1)->mensaje)."%0A"."*Tus amigos de IFE*";
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;
        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje];
        return response()->json($data);
    }
    public function enviarMensajeFaltones(Request $request){
        $observacion = new Observacion();
        $observacion->observacion=Auth::user()->name .": Informó sobre la falta de esta clase en fecha y  hora: ". Carbon::now()->format('Y-m-d H:i');
        $observacion->activo=1;
        $observacion->observable_id=$request->programacion_id;
        $observacion->observable_type=Programacion::class;
        $observacion->save();

        $programacion=Programacion::findOrFail($request->programacion_id);
        $programacion->estado_id=estado("FALTANOTIFICADA");
        $programacion->save();

        $observacion->usuarios()->attach(Auth::user()->id);
        $mensaje=saludo()."%0A".nombre($request->persona_id,2)."%0A".strip_tags(Mensaje::findOrFail(2)->mensaje)."%0A*Detalle de la clase:*%0A".$this->FormatearProgramacion($programacion);
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;
        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje,'programacion'=>$programacion];
        return response()->json($data);
    }

    public function FormatearProgramacion($programacion){
        $texto="*Fecha:* ".$programacion->fecha->format('d-m-Y')."%0A";
        $texto.="*Horario:* ".$programacion->hora_ini->isoFormat('hh:mm:ss').' - '.$programacion->hora_fin->isoFormat('hh:mm:ss')."%0A";
        $texto.="*Docente:* ".$programacion->docente->nombre."%0A";
        $texto.="*Materia:* ".$programacion->materia->materia."%0A";
        $texto.="*Estado de Clase:* ".$programacion->estado->estado."%0A";
        return $texto;
    }
    public function FormatearProgramacioncom($programacioncom){
        $texto="*Fecha:* ".$programacioncom->fecha->format('d-m-Y')."%0A";
        $texto.="*Horario:* ".$programacioncom->horaini->isoFormat('hh:mm:ss').' - '.$programacioncom->horafin->isoFormat('hh:mm:ss')."%0A";
        $texto.="*Estado de Clase:* ".$programacioncom->estado->estado."%0A";
        return $texto;
    }
    public function enviarMensajeFaltonesComputacion(Request $request){
        $observacion = new Observacion();
        $observacion->observacion=Auth::user()->name .": Informo sobre la falta de esta clase en fecha y  hora: ". Carbon::now()->format('Y-m-d H:i');
        $observacion->activo=1;
        $observacion->observable_id=$request->programacioncom_id;
        $observacion->observable_type=Programacioncom::class;
        $observacion->save();
        $observacion->usuarios()->attach(Auth::user()->id);
        $programacioncom=Programacioncom::findOrFail($request->programacioncom_id);
        $programacioncom->estado_id=estado("FALTANOTIFICADA");
        $programacioncom->save();
        $mensaje=strip_tags(Mensaje::findOrFail(3)->mensaje)."%0A*Detalle de la clase:*%0A".$this->FormatearProgramacioncom($programacioncom);
        $persona=Persona::findOrFail($request->persona_id);
        $apoderados= $persona->apoderados;


        $data=['persona'=>$persona,'apoderados'=>$apoderados,'mensaje'=>$mensaje,'programacioncom'=>$programacioncom];
        return response()->json($data);
    }

    public function CrearContacto($persona_id){
        $persona=Persona::find($persona_id);
        $nombre_archivo='contactos/'.$persona->nombre.$persona->id.'.vcf';
        Storage::append($nombre_archivo, 'BEGIN:VCARD');
        Storage::append($nombre_archivo, 'VERSION:3.0');
        $apellidoMaterno = isset($persona->apellidom) ? $persona->apellidom : '-';
        Storage::append($nombre_archivo, 'N:'.$persona->apellidop.';'.$persona->nombre.';'.$apellidoMaterno.";;");
        Storage::append($nombre_archivo, 'FN:'.$persona->apellidop.' '.$persona->nombre.' '.$apellidoMaterno);
        $foto = isset($persona->foto) ? $persona->foto : '-';
        Storage::append($nombre_archivo, "PHOTO;VALUE=uri".URL::to('/').Storage::url($foto));
        if (isset($persona->fechanacimiento)){
            Storage::append($nombre_archivo, 'BDAY:'.$persona->fechanacimiento->isoFormat('YYYY-MM-DD'));
        }
        $genero = isset($persona->genero) ? $persona->genero : '';
        if($genero=="MUJER")
            Storage::append($nombre_archivo, 'GENDER:F');
        else{
            Storage::append($nombre_archivo, 'GENDER:M');
        }
        $telefono = isset($persona->telefono) ? $persona->telefono : '0';
        Storage::append($nombre_archivo, "TEL;VALUE=uri;PREF=1;TYPE=voice,work:".$persona->telefono);
        $whatsapp = ($telefono!=0) ? $telefono : 'No tiene numero';
        Storage::append($nombre_archivo, "URL:wa.me/591".$whatsapp);

        $zona = isset($persona->zona) ? $persona->zona->zona : '-';
        $direccion = isset($persona->direccion) ? $persona->direccion : '-';
        $como_id = isset($persona->como_id) ? $persona->como_id : '-';
        
        Storage::append($nombre_archivo, "NOTE:CODIGO:".$persona->id);

        if(isset($persona->como_id)){
            $como=$persona->como;
            Storage::append($nombre_archivo, "NOTE:Genero:".$genero.'\nDireccion:'.$zona.' '.$direccion.'\nComo eneteró:'.$como->como);
        }
        $apoderados=$persona->apoderados;
            foreach ($apoderados as $apoderado) {
                Storage::append($nombre_archivo, "TEL;VALUE=uri;PREF=1;TYPE=voice,home:".$apoderado->telefono);
                Storage::append($nombre_archivo, "NOTE:Nombre:".$apoderado->nombre.' '.$apoderado->apellidop.' '.$apoderado->apellidom.'\nPARENTESCO:'.$apoderado->pivot->parentesco.'\nTelefono:'.$apoderado->telefono);
                Storage::append($nombre_archivo, "URL:wa.me/591".$apoderado->telefono);
            }
        if (isset($persona->estudiante->inscripciones)){
            $inscripciones=$persona->estudiante->inscripciones;
            foreach ($inscripciones as $inscripcion) {
                Storage::append($nombre_archivo, "NOTE:Modalidad:".$inscripcion->modalidad->modalidad.'\nObservacion:'.$inscripcion->objetivo.'\nVigente:'.$inscripcion->vigente.'\nMotivo:'.$inscripcion->motivo->motivo);
            }
        }    
        if(isset($persona->computacion->matriculaciones)){
            $matriculaciones=$persona->computacion->matriculaciones;
            foreach ($matriculaciones as $matriculacion) {
                Storage::append($nombre_archivo, "NOTE:Asignatura:".$matriculacion->asignatura->asignatura.'\nVigente:'.$matriculacion->vigente);
            }
        }
        Storage::append($nombre_archivo, "LANG;TYPE=work;PREF=2:es");
        Storage::append($nombre_archivo, "TITLE:".$persona->papelinicial);
        
        $roles="";
        if($persona->isEstudiante()){
            $roles.="ESTUDIANTE,";
        }
        if($persona->isDocente()){
            $roles.="DOCENTE,";
        }
        if($persona->isAdministrativo()){
            $roles.="ADMINISTRATIVO,";
        }
        if($persona->isComputacion()){
            $roles.="COMPUTACION,";
        }
        Storage::append($nombre_archivo, "ROLE:".$roles);
        Storage::append($nombre_archivo, "LOGO:".$persona->foto);
        Storage::append($nombre_archivo, "ORG:".$persona->empresa);
        $intereses=$persona->interests;
        $categorias="";
        foreach ($intereses as $categoria) {
            $categorias.=$categoria->interest.",";
        }
        Storage::append($nombre_archivo, "NOTE:INTERESES:".$categorias);
        $observacioninicial=$persona->observaciones->first();
        $observacionfinal=$persona->observaciones->last();
        if (isset($observacioninicial->observacion)){
            Storage::append($nombre_archivo, "NOTE:".(strip_tags($observacioninicial->observacion)));
            if($observacioninicial->id!=$observacionfinal->id)
            Storage::append($nombre_archivo, "NOTE:".(strip_tags($observacionfinal->observacion)));
        }
        Storage::append($nombre_archivo, 'END:VCARD');
        $contacto=Storage::disk('public')->put($nombre_archivo,'Contents');
    }
    
    public function descargarContacto($persona){
        $una_una_persona=Persona::findOrFail($persona);
        // $url=storage_path("app//contactos//".$una_una_persona->nombre.$una_una_persona->id.".vcf");
        $url=storage_path("app/contactos/".$una_una_persona->nombre.$una_una_persona->id.".vcf");
        //dd($url);
        if(!is_null($url)){
            if (file_exists($url)){
                if (unlink($url)) {
                    $this->CrearContacto($persona);
                }         
            }else{
                $this->CrearContacto($persona);
            }
        }
       
        return response()->download($url);
    }

    public function actualizarVuelveFecha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'vuelvefecha' => ['required','min:5','max:30'],  /* https://laraveles.com/foro/viewtopic.php?id=6764 */
        ]);
        if ($validator->passes()) {
            $persona=Persona::findOrFail($request->persona_id);
            $persona->vuelvefecha = $request->vuelvefecha;
            $persona->save();
            return response()->json(['persona' => $persona]);
        } else {
            return response()->json(['error' => $validator->errors()->all()]);
        }
    }
    public function actualizarVolvera(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'volvera' => ['numeric','required','min:1','max:5'],  /* https://laraveles.com/foro/viewtopic.php?id=6764 */
        ]);
        if ($validator->passes()) {
            $persona=Persona::findOrFail($request->persona_id);
            $persona->volvera = $request->volvera;
            $persona->save();
            return response()->json(['persona' => $persona]);
        } else {
            return response()->json(['error' => $validator->errors()->all()]);
        }
    }
    public function felicitado(Request $request){
        $felicitado=new Felicitado();
        $felicitado->anio=Carbon::now()->year;
        $felicitado->persona_id=$request->persona_id;
        $felicitado->save();
        return response()->json($felicitado);
    }
    public function select(){
        $personas=Persona::all();
        return response()->json($personas);
    }
    public function personaGet($potencial_id){
        $persona=Persona::findOrFail($potencial_id);
        $observacion=rtrim(ltrim(trim(str_replace(' ', '%20', strip_tags($persona->observaciones->first()->observacion)))));
        $interests=$persona->interests;
        $como=$persona->como;
        $user=$persona->usuarios->first()->name;
        $data=['persona'=>$persona,'interests'=>$interests,'como'=>$como,'observacion'=>$observacion,'user'=>$user];
        return response()->json($data);
    }
    public function getPersona(Persona $persona){
        return response()->json($persona);
    }

    public function DescargarTodosContacto($inicio, $incremento){
        
            $ultimo_id=Persona::get()->last()->id;
            if($incremento+$inicio>$ultimo_id)
                $incremento=$ultimo_id-$inicio;
            $personas=Persona::whereBetween('id',[$inicio,$inicio+$incremento])->get();
            // $nombre_archivo='contactos/todos'.Carbon::now()->isoFormat("_hh_mm_ss_DD_MMMM_YYYY").'.vcf';
            $nombre_archivo='contactos/todos/'.$inicio."_".$inicio+$incremento.'.vcf';
            foreach ($personas as $persona) {
                Storage::append($nombre_archivo, 'BEGIN:VCARD');
                Storage::append($nombre_archivo, 'VERSION:3.0');
                $apellidoMaterno = isset($persona->apellidom) ? $persona->apellidom : '-';
                Storage::append($nombre_archivo, 'N:'.$persona->id." ".$persona->apellidop.';'.$persona->nombre.';'.$apellidoMaterno.";;");
                Storage::append($nombre_archivo, 'FN:'.$persona->apellidop.$persona->id." ".$persona->nombre.' '.$apellidoMaterno);
                $foto = isset($persona->foto) ? $persona->foto : '-';
                //echo $foto;
                if(isset($persona->foto)){
                    $image_path = public_path('storage/'.$persona->foto);
                    $image_data = file_get_contents($image_path);
                    $base64_image = base64_encode($image_data);
                    Storage::append($nombre_archivo, "PHOTO;TYPE=JPEG;ENCODING=b:".$base64_image);
                }else{
                    Storage::append($nombre_archivo, "PHOTO;TYPE=JPEG;ENCODING=b:"."sin imagen");
                    //dd($persona->foto);
                }

                
                if (isset($persona->fechanacimiento)){
                    Storage::append($nombre_archivo, 'BDAY:'.$persona->fechanacimiento->isoFormat('YYYY-MM-DD'));
                }
                $genero = isset($persona->genero) ? $persona->genero : '';
                if($genero=="MUJER")
                    Storage::append($nombre_archivo, 'GENDER:F');
                else{
                    Storage::append($nombre_archivo, 'GENDER:M');
                }
                $telefono = isset($persona->telefono) ? $persona->telefono : '0';
                Storage::append($nombre_archivo, "TEL;VALUE=uri;PREF=1;TYPE=voice,work:".$persona->telefono);
                $whatsapp = ($telefono!=0) ? $telefono : 'No tiene numero';
                Storage::append($nombre_archivo, "URL:wa.me/591".$whatsapp);

                $zona = isset($persona->zona) ? $persona->zona->zona : '-';
                $direccion = isset($persona->direccion) ? $persona->direccion : '-';
                $como_id = isset($persona->como_id) ? $persona->como_id : '-';
                
                Storage::append($nombre_archivo, "NOTE:CODIGO:".$persona->id);

                if(isset($persona->como_id)){
                    $como=$persona->como;
                    Storage::append($nombre_archivo, "NOTE:Genero:".$genero.'\nDireccion:'.$zona.' '.$direccion.'\nComo eneteró:'.$como->como);
                }
                $apoderados=$persona->apoderados;
                    foreach ($apoderados as $apoderado) {
                        Storage::append($nombre_archivo, "TEL;VALUE=uri;PREF=1;TYPE=voice,home:".$apoderado->telefono);
                        Storage::append($nombre_archivo, "NOTE:Nombre:".$apoderado->nombre.' '.$apoderado->apellidop.' '.$apoderado->apellidom.'\nPARENTESCO:'.$apoderado->pivot->parentesco.'\nTelefono:'.$apoderado->telefono);
                        Storage::append($nombre_archivo, "URL:wa.me/591".$apoderado->telefono);
                    }
                if (isset($persona->estudiante->inscripciones)){
                    $inscripciones=$persona->estudiante->inscripciones;
                    foreach ($inscripciones as $inscripcion) {
                        Storage::append($nombre_archivo, "NOTE:Modalidad:".$inscripcion->modalidad->modalidad.'\nObservacion:'.$inscripcion->objetivo.'\nVigente:'.$inscripcion->vigente.'\nMotivo:'.$inscripcion->motivo->motivo);
                    }
                }    
                if(isset($persona->computacion->matriculaciones)){
                    $matriculaciones=$persona->computacion->matriculaciones;
                    foreach ($matriculaciones as $matriculacion) {
                        Storage::append($nombre_archivo, "NOTE:Asignatura:".$matriculacion->asignatura->asignatura.'\nVigente:'.$matriculacion->vigente);
                    }
                }
                Storage::append($nombre_archivo, "LANG;TYPE=work;PREF=2:es");
                Storage::append($nombre_archivo, "TITLE:".$persona->papelinicial);
                
                $roles="";
                if($persona->isEstudiante()){
                    $roles.="ESTUDIANTE,";
                }
                if($persona->isDocente()){
                    $roles.="DOCENTE,";
                }
                if($persona->isAdministrativo()){
                    $roles.="ADMINISTRATIVO,";
                }
                if($persona->isComputacion()){
                    $roles.="COMPUTACION,";
                }
                Storage::append($nombre_archivo, "ROLE:".$roles);
                Storage::append($nombre_archivo, "LOGO:".$persona->foto);
                Storage::append($nombre_archivo, "ORG:".$persona->empresa);
                $intereses=$persona->interests;
                $categorias="";
                foreach ($intereses as $categoria) {
                    $categorias.=$categoria->interest.",";
                }
                Storage::append($nombre_archivo, "NOTE:INTERESES:".$categorias);
                $observacioninicial=$persona->observaciones->first();
                $observacionfinal=$persona->observaciones->last();
                if (isset($observacioninicial->observacion)){
                    $textoConEtiquetas =$observacioninicial->observacion ;
                    $textoSinEtiquetas = $this->eliminarEtiquetas($textoConEtiquetas);
                    Storage::append($nombre_archivo, "NOTE:".(strip_tags($textoSinEtiquetas)));
                    if($observacioninicial->id!=$observacionfinal->id)
                        $textoConEtiquetas =$observacionfinal->observacion ;
                        $textoSinEtiquetas = $this->eliminarEtiquetas($textoConEtiquetas);
                        Storage::append($nombre_archivo, "NOTE:".(strip_tags($textoSinEtiquetas)));
                }
               
                
                Storage::append($nombre_archivo, 'END:VCARD');
            }
            $contacto=Storage::disk('public')->put($nombre_archivo,'Contents');
        // $url=storage_path("app/contactos/".$nombre_archivo);
        // return response()->download($url);
    }
    function eliminarEtiquetas($texto) {
        // Eliminar todas las etiquetas HTML
        $textoSinEtiquetas = strip_tags($texto);
        
        // Reemplazar saltos de línea y tabulaciones por comas y espacios
        $textoFormateado = preg_replace('/[\n\r\t]/', ', ', $textoSinEtiquetas);
        
        // Reemplazar múltiples espacios por uno solo
        $textoFormateado = preg_replace('/\s+/', ' ', $textoFormateado);
        
        return $textoFormateado;
    }

    public function mostrarArchivos(Request $request) {
        $inicio=$request->inicio;
        $incremento = $request->incremento;
        $this->DescargarTodosContacto($inicio,$incremento);
        $directorio = storage_path("app/contactos/todos");
        $archivos = scandir($directorio);
        return view("persona.contacto.archivos", ['archivos' => $archivos]);
    }
    public function descargarArchivo(Request $request) {
        $archivo = $request->input('archivo');
        $ruta_archivo = storage_path("app/contactos/todos/".$archivo) ; // reemplazar con la ruta completa a la carpeta
        return response()->download($ruta_archivo);
    }
    public function eliminar(Request $request)
    {
        $archivo = $request->input('archivo');
        Storage::delete(storage_path('app/contactos/todos/').$archivo); // Eliminar el archivo utilizando la clase Storage
        return response()->json(['mensaje' => "El archivo $archivo ha sido eliminado"]); // Retornar una respuesta en formato JSON
    }

   
    public function listar(){

        $persona=Persona::select('id','nombre','apellidop','apellidom','foto');
            return datatables()->of($persona)
                ->addColumn('btn', 'persona.actiontodos')
                ->rawColumns(['btn'])
                ->toJson();
      
    }
    
}
