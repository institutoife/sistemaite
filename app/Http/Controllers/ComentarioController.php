<?php

namespace App\Http\Controllers;

use App\Models\Comentario;
use App\Models\Como;
use App\Models\Persona;
use App\Models\Ciudad;
use App\Models\Pais;
use App\Models\Zona;
use App\Models\Interest;
use App\Http\Requests\StoreComentarioRequest;
use App\Http\Requests\UpdateComentarioRequest;
use App\Http\Requests\DeleteRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Stevebauman\Purify\Facades\Purify;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class ComentarioController extends Controller
{



    // public function __construct()
    // {
    //     $this->middleware('can:Listar Comentarios')->only('index','show');
    //     $this->middleware('can:Crear Comentarios')->only('guardarComentarioDesdeSistema');
    //     $this->middleware('can:Editar Comentarios')->only('edit','darbaja','daralta','update','estudiantizarComentario');
    //     $this->middleware('can:Eliminar Comentarios')->only('destroy');

    //     $this->middleware('auth')->except('store');
    // }

    public function index()
    {
       return view('comentario.index'); 
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $comos=Como::get();
        $interests=Interest::get();
        return view("comentario.create",compact('comos','interests'));
    }

    /* */
    public function store(Request $request)
    {
        // Validar datos del formulario
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:40', // Coincide con el tamaño definido en la migración
            'telefono' => 'required|string|max:12', // Coincide con el tamaño definido en la migración
            'comentario' => 'required|string|max:400', // Coincide con el tamaño definido en la migración
            'recaptcha_token' => 'required|recaptchav3:submit_form', // Validación de reCAPTCHA
            'como_id' => 'required|exists:comos,id', // Validar que exista el ID en la tabla 'comos'
            'persona_id' => 'nullable|exists:personas,id', // Validar que exista el ID en la tabla 'personas'
        ]);
    
        // Guardar el comentario en la base de datos
        $comentario = new Comentario();
        $comentario->nombre = $validatedData['nombre'];
        $comentario->telefono = $validatedData['telefono'];
        $comentario->comentario = $validatedData['comentario'];
        $comentario->vigente = true; // Marcar como vigente por defecto
        $comentario->como_id = $validatedData['como_id'];
        $comentario->persona_id = $validatedData['persona_id'] ?? null; // Puede ser nulo
        $comentario->save();
    
        // Retornar respuesta JSON
        return response()->json([
            'message' => 'Comentario enviado exitosamente.',
            'data' => $comentario->only(['id', 'nombre', 'telefono', 'comentario', 'vigente', 'como_id', 'persona_id', 'created_at', 'updated_at'])
        ]);
    }
    
    // $validatedData = $request->validate([
    //     'nombre' => 'required|string|max:40',
    //     'telefono' => 'required|string|max:12',
    //     'comentario' => 'required|string|max:400',
    //     'recaptcha_token' => 'required|string',
    // ]);

    
    // $recaptchaResponse = Http::post('https://www.google.com/recaptcha/api/siteverify', [
    //     'secret' => env('RECAPTCHA_SECRET'),
    //     'response' => $validatedData['recaptcha_token'],
    // ]);

    // $recaptchaData = $recaptchaResponse->json();
    // if (!$recaptchaData['success']) {
    //     return response()->json(['message' => 'Validación de reCAPTCHA fallida'], 422);
    // }

    
    // $comentario = Comentario::create([
    //     'nombre' => $validatedData['nombre'],
    //     'telefono' => $validatedData['telefono'],
    //     'comentario' => $validatedData['comentario'],
    //     'como_id' => 6,
        
    //     'vigente' => true,
    // ]);

    // return response()->json([
    //     'message' => 'Comentario guardado exitosamente',
    //     'comentario' => $comentario,
    // ], 201);

    public function sendMessage(Request $request)
    {
        // try {
        //     $validator = Validator::make($request->all(), [
        //         'telefono' => 'required|numeric',
        //         'comentario' => 'required|max:255',
        //         'recaptcha' => 'required',
        //     ]);
        
        //     if ($validator->fails()) {
        //         return response()->json([
        //             'status' => 'error',
        //             'errors' => $validator->errors()
        //         ], 422);
        //     }
        
        //     // Lógica para enviar el mensaje o continuar el proceso
        //     return response()->json(['status' => 'success']);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => $e->getMessage()
        //     ], 500);
        // }

        
        // Validar los datos
        
    try {
        $validator = Validator::make($request->all(), [
            'telefono' => 'required|numeric',
            'comentario' => 'required|max:255',
            'recaptcha' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false]);
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }
        
        // Verificar reCAPTCHA
        $recaptchaSecret = env('RECAPTCHA_SECRET');
        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $recaptchaSecret,
            'response' => $request->recaptcha,
        ]);

        if (!$recaptchaResponse->json('success')) {
            return response()->json(['success' => false, 'message' => 'reCAPTCHA falló.']);
        }

        $comentario = Comentario::create([
            'nombre' => "anonymous",
            'telefono' => $request->telefono,
            'comentario' => $request->comentario,
            'como_id' => 6,
            'persona_id' => null,
            'vigente' => true,
        ]);

       
        // Enviar mensaje de WhatsApp
        $whatsappURL = "https://api.whatsapp.com/send?phone={$request->telefono}&text=" . urlencode("Gracias por tu mensaje: {$request->comentario}");
        return response()->json(['success' => true, 'whatsapp_url' => $whatsappURL]);
    }catch (\Exception $e) {
        return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage()
                ], 500);
    }
}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreComentarioRequest  $request
     * @return \Illuminate\Http\Response
    
     */
    /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% Guarda desde la web %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    // public function guardarComentario()

    public function guardarComentario(Request $request)
    {
        
        // Validar datos del formulario
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:40', // Coincide con el tamaño definido en la migración
            'telefono' => 'required|string|max:12', // Coincide con el tamaño definido en la migración
            'comentario' => 'required|string|max:400', // Coincide con el tamaño definido en la migración
        ]);
        
                

                // Guardar el comentario en la base de datos
                $comentario = new Comentario();
                $comentario->nombre = $validatedData['nombre'];
                $comentario->telefono = $validatedData['telefono'];
                $comentario->comentario = $validatedData['comentario'];
                $comentario->vigente = true; // Marcar como vigente por defecto
                $comentario->como_id = 6;
                $comentario->persona_id = $validatedData['persona_id'] ?? null; // Puede ser nulo
                $comentario->save();
            
                // Retornar respuesta JSON
                return response()->json([
                    'success' => true,
                    'message' => 'Comentario enviado exitosamente.',
                    'data' => $comentario->only(['id', 'nombre', 'telefono', 'comentario', 'vigente', 'como_id', 'persona_id', 'created_at', 'updated_at'])
                ]);
    }    
    /**%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% Guarda desde sistema %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
    public function guardarComentarioDesdeSistema(StoreComentarioRequest $request)
    {
            $comentario = new Comentario();
            $comentario->nombre = $request->nombre;
            $comentario->telefono = $request->telefono;
            $comentario->vigente = 1;
            $comentario->como_id = $request->como_id;
            $comentario->comentario = $request->comentario;
            $comentario->save();
            $comentario->interests()->sync(array_values($request->interests));
            return redirect()->route('comentario.index');
    }

    public function estudiantizarComentario(Comentario $comentario){
        $persona=new Persona();
        $ciudades=Ciudad::get();
        $paises=Pais::get();
        $zonas=Zona::get();
        $interests=Interest::all();

        $comos=Como::all();
        
        $interests_currents=$comentario->interests; 
        $ids=[];
        foreach ($interests_currents as $interest) {
            $ids[] = $interest->id;
        }
        $interests_faltantes = Interest::whereNotIn('id', $ids)->get();

        //$interests_currents=
        return view('comentario.estudiantizarComentario',compact('ciudades','paises','zonas','interests','interests_currents','interests_faltantes','comos','comentario'));
    }
    
    public function crearContactoComentario($comentario_id){
        $comentario=Comentario::findOrFail($comentario_id);
        $nombre_archivo='comentarios/'.$comentario->id.'.vcf';
        Storage::append($nombre_archivo, 'BEGIN:VCARD');
        Storage::append($nombre_archivo, 'VERSION:3.0');
        Storage::append($nombre_archivo, 'N:;'.$comentario->id.";;".$comentario->nombre.";");
        Storage::append($nombre_archivo, 'FN:'.$comentario->nombre.' '.$comentario->id);
        $telefono = isset($comentario->telefono) ? $comentario->telefono : '0';
        Storage::append($nombre_archivo, "TEL;VALUE=uri;PREF=1;TYPE=voice,work:".$comentario->telefono);
        Storage::append($nombre_archivo, "URL:wa.me/591".$telefono);
        Storage::append($nombre_archivo, "LANG;TYPE=work;PREF=2:es");
        Storage::append($nombre_archivo, "NOTE:COMENTARIO:".$comentario->comentario);
        Storage::append($nombre_archivo, "NOTE:COMENTARIO:".$comentario->como->como);
        Storage::append($nombre_archivo, "NOTE:INTERESES:".$comentario->interests);
        Storage::append($nombre_archivo, "NOTE:FECHA REGISTRO:".$comentario->created_at->format('d-m-Y'));
        Storage::append($nombre_archivo, "NOTE:HORA REGISTRO:".$comentario->created_at->format('h:i:s'));
        Storage::append($nombre_archivo, 'END:VCARD');
        $contacto=Storage::disk('public')->put($nombre_archivo,'Contents');
        $url=storage_path("app\\comentarios\\".$comentario->id.".vcf");
        return response()->download($url);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Comentario  $comentario
     * @return \Illuminate\Http\Response
     */
    public function show(Comentario $comentario)
    {
        $interests=$comentario->interests;
        return view('comentario.show', compact('comentario','interests'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Comentario  $comentario
     * @return \Illuminate\Http\Response
     */
    public function edit(Comentario $comentario)
    {
        $comos=Como::get();
        $interests_currents=$comentario->interests; 
        $ids=[];
        foreach ($interests_currents as $interest) {
            $ids[] = $interest->id;
        }
        $interests_faltantes = Interest::whereNotIn('id', $ids)->get();
        return view('comentario.edit', compact('comentario','comos','interests_faltantes','interests_currents'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateComentarioRequest  $request
     * @param  \App\Models\Comentario  $comentario
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateComentarioRequest $request, Comentario $comentario)
    {
        $comentario->nombre = $request->nombre;
        $comentario->telefono = $request->telefono;
        $comentario->comentario = $request->comentario;
        $comentario->como_id = $request->como_id;
        $comentario->save();
        $comentario->interests()->sync(array_keys($request->interests));
        return redirect()->route("comentario.show",$comentario);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Comentario  $comentario
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comentario $comentario)
    {
        $comentario->interests()->detach();
        $comentario->delete();
        return response()->json(['mensaje'=>"El registro fue eliminado correctamente"]);
    }

    public function comentarioGet($comentario_id){
        $comentario=Comentario::findOrFail($comentario_id);
        $comentario->comentario=rtrim(ltrim(trim(str_replace(' ', '%20', strip_tags($comentario->comentario)))));
        $interests=$comentario->interests;
        $como=$comentario->como;
        $data=['comentario'=>$comentario,'interests'=>$interests,'como'=>$como];
        return response()->json($data);
    }
    
    public function listar(){
        $comentarios=Comentario::join('comos','comos.id','comentarios.como_id')
        ->select('comentarios.id','comentarios.nombre','comentarios.comentario','comos.como','telefono','vigente')
        ->get();
        return datatables()->of($comentarios)
        ->addColumn('btn', 'comentario.action')
        ->rawColumns(['btn','comentario'])
        ->toJson();
    }

    public function listarInterests($comentario_id){
        $interests=Comentario::findOrFail($comentario_id)->interests;
        return response()->json($interests);
    }
    public function darbaja(Request $request)
    {
        $comentario=Comentario::findOrFail($request->comentario_id);
        $comentario->vigente=0;
        $comentario->save();
        return response()->json(['mensaje'=>"Se dió de baja el registro correctamente"]);
    }
   
    public function daralta(Request $request)
    {
        $comentario=Comentario::findOrFail($request->comentario_id);
        $comentario->vigente=1;
        $comentario->save();
        return response()->json(['mensaje'=>"Se dió de alta el registro correctamente"]);
    }
}
