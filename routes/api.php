<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Persona;
use App\Models\Docente;
use App\Models\Pais;
use App\Models\Ciudad;
use App\Models\Zona;
use App\Models\User;
use App\Models\Menu;
use App\Models\Grado;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Colegio;
use App\Models\Modalidad;
use App\Models\Nivel;
use App\Models\Clase;
use App\Models\Inscripcione;
use App\Models\Motivo;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\Estado;
use App\Models\Feriado;
use App\Http\Controllers\ComentarioController;
use App\Models\Tipomotivo;
use App\Support\SedeDB as DB;
use App\Http\Controllers\DocenteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});


/* esta parte hacia salir error al hacer las busquedas */

// Route::get('estudiantes',function(){
//     $persona=Persona::join('estudiantes','estudiantes.persona_id','=','personas.id')
//                     ->select('personas.id','nombre','apellidop','apellidom','foto');
//     return datatables()->of($persona)
//         ->addColumn('btn','persona.action')
//         ->rawColumns(['btn','foto'])
//         ->toJson();
// });

Route::get('computaciones',function(){
    $computaciones=Persona::join('computacions','computacions.persona_id','=','personas.id')
        ->select('computacions.id','nombre','apellidop','apellidom','foto');
    return datatables()->of($computaciones)
        ->addColumn('btn','computacion.action')
        ->rawColumns(['btn','foto'])
        ->toJson();
});

Route::get('administrativos',function(){
    $administrativos=Persona::join('administrativos','administrativos.persona_id','=','personas.id')
        ->select('personas.id','administrativos.id as admin_id','nombre','apellidop','apellidom','foto');
    return datatables()->of($administrativos)
        ->addColumn('btn','administrativo.action')
        ->rawColumns(['btn','foto'])
        ->toJson();
});

Route::get('presentes', function () {
    $clases =  Clase::join('programacions', 'clases.programacion_id', '=', 'programacions.id')
    ->join('inscripciones', 'programacions.inscripcione_id', '=', 'inscripciones.id')
    ->join('estudiantes', 'inscripciones.estudiante_id', '=', 'estudiantes.id')
    ->join('personas', 'estudiantes.persona_id', '=', 'personas.id')
    // ->where('clases.estado','=','PRESENTE')
    // ->where('clases.fecha','=',Carbon\Carbon::now()->isoFormat('Y-M-D'))
    ->select('clases.id','nombre','apellidop','apellidom', 'clases.horainicio', 'clases.horafin', 'clases.docente_id', 'clases.materia_id', 'clases.aula_id', 'clases.tema_id', 'personas.foto')->get();
    return datatables()->of($clases)
        ->addColumn('btn', 'clase.action_marcar')
        ->rawColumns(['btn', 'foto'])
        ->toJson();
});

// Route::get('motivos', function () {
//     return datatables()->of(Motivo::all())
//         ->addColumn('btn', 'motivo.action')
//         ->rawColumns(['btn'])
//         ->toJson();
// });
Route::get('tipomotivos', function () {
    return datatables()->of(Tipomotivo::all())
        ->addColumn('btn', 'tipomotivo.action')
        ->rawColumns(['btn'])
        ->toJson();
});
Route::get('grados', function () {
    $grados=Grado::join('nivels','nivels.id','=','grados.nivel_id')
                ->select('grados.id','grado','nivel')->get();

    return datatables()->of($grados)
        ->addColumn('btn', 'grado.action')
        ->rawColumns(['btn'])
        ->toJson();
});

// Route::get('referencias',function(){
//     return datatables()->of(Persona::select('id','nombre','apellidop','apellidom','foto'))
//         ->addColumn('btn', 'persona.actionmodal')
//         ->rawColumns(['foto','btn'])
//         ->toJson();
// });

// Route::get('paises',function(){
//     return datatables()->of(Pais::all())
//         ->addColumn('btn','pais.action')
//         ->rawColumns(['btn'])
//         ->toJson();
// });
// Route::get('personas', function () {
//     $persona=Persona::select('id','nombre','apellidop','apellidom','foto');
//     return datatables()->of($persona)
//         ->addColumn('btn', 'persona.actiontodos')
//         ->rawColumns(['btn'])
//         ->toJson();
// })->name('personas.todos');

Route::get('usuarios', function () {
    return datatables()->of(User::all())
        ->addColumn('btn', 'user.action')
        ->rawColumns(['btn'])
        ->toJson();
})->name('usuarios');

Route::get('apoderados', function () {
    $persona = Persona::select('id', 'nombre','apellidop','apellidom', 'foto');
    return datatables()->of($persona)
        ->addColumn('btn', 'persona.actionapoderados')
        ->rawColumns(['btn'])
        ->toJson();
})->name('apoderados.todos');

Route::get('ciudades',function(){
    $ciudades=Ciudad::select('ciudads.id','ciudad','nombrepais')->join('pais','pais.id','=','ciudads.pais_id')
    ->get();
    return datatables()->of($ciudades)
        ->addColumn('btn','ciudad.action')
        ->rawColumns(['btn'])
        ->toJson();
});
Route::get('zonas',function(){
    $zonas=Zona::join('ciudads','ciudads.id','=','zonas.ciudad_id')
        ->select('zonas.id','zonas.zona','ciudad')
        ->get();
    return datatables()->of($zonas)
        ->addColumn('btn','zona.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('usuarios',function(){
    return  datatables()->of(User::select('id','name','email','foto')->get())
        ->addColumn('btn','user.action')
        ->rawColumns(['btn','foto'])
        ->toJson();
});

Route::get('menus', function () {
    return datatables()->of(Menu::get())
        ->addColumn('btn', 'user.action')
        ->rawColumns(['btn','icono'])
        ->toJson();
});


Route::get('asignaturas', function () {
    $asignaturas=Asignatura::select('asignaturas.id','asignatura','carrera','costo','totalhoras')->join('carreras','carreras.id','=','asignaturas.carrera_id')->get();
    return datatables()->of($asignaturas)
        ->addColumn('btn', 'asignatura.action')
        ->rawColumns(['btn'])
        ->toJson();
});
Route::get('carreras', function () {
    return datatables()->of(Carrera::all())
        ->addColumn('btn', 'carrera.action')
        ->rawColumns(['btn'])
        ->toJson();
});
Route::get('estados', function () {
    return datatables()->of(Estado::all())
        ->addColumn('btn', 'estado.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('docentes/turnos', [DocenteController::class, 'docentesPorTurno']);

Route::get('departamentos', function () {
    return datatables()->of(Departamento::get())
        ->addColumn('btn', 'departamento.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('provincias', function () {

    return datatables()->of(Provincia::select('provincias.id','provincia','departamento','nombrepais')
        ->join('departamentos','departamentos.id','=','provincias.departamento_id')
        ->join('pais','pais.id','=','departamentos.pais_id')->get())
        ->addColumn('btn', 'provincia.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('municipios', function () {
    return datatables()->of( Municipio::select('municipios.id','municipio','departamento','provincia','nombrepais')
        ->join('provincias','provincias.id','=','municipios.provincia_id')
        ->join('departamentos','departamentos.id','=','provincias.departamento_id')
        ->join('pais','pais.id','=','departamentos.pais_id')
        ->get())
        ->addColumn('btn', 'municipio.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('colegios', function () {
    return datatables()->of(Colegio::select('colegios.id', 'nombre', 'director','telefono','celular')->get())
        ->addColumn('btn', 'colegio.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::middleware('throttle:300,1')->get('modalidades', function () {
    return datatables()->of(Modalidad::get())
        ->addColumn('btn', 'modalidad.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('niveles', function () {
    return datatables()->of(Nivel::get())
        ->addColumn('btn', 'nivel.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('inscripciones', function () {
    return datatables()->eloquent(Inscripcione::with('estado')->withSucursalOrigen())
        ->addColumn('btn', 'inscripcione.action')
        ->addColumn('estado_sucursal',fn($inscripcion)=>view('inscripcione.estado-sucursal',compact('inscripcion'))->render())
        ->rawColumns(['btn','estado_sucursal'])
        ->toJson();
});
Route::get('feriados', function () {
    $feriados= Feriado::get();
    return datatables()->of($feriados)
        ->addColumn('btn', 'feriado.action')
        ->rawColumns(['btn'])
        ->toJson();
});

Route::get('pais/{id}/ciudades','CiudadController@city_of_country');
Route::get('ciudad/{id}/zonas','ZonaController@zona_of_city');
Route::get('temas/{materia_id}','TemaController@tema_of_materia');

Route::get('pais/{id}/departamentos','PaisController@departamento_de_pais');

Route::get('departamento/{id}/provincias', 'ProvinciaController@provincia_of_departamento');
//Route::get('provincia/{id}/municipios', 'MunicipioController@municipio_of_provincia');

Route::get('departamento/{id}/provincias','ProvinciaController@provincia_of_departamento');
Route::get('provincia/{id}/municipios', 'MunicipioController@municipio_of_provincia');

Route::post('/send-message', [ComentarioController::class, 'sendMessage']);


// Route::middleware(['throttle:api'])->post('/guardar-comentario', [ComentarioController::class, 'store']);
//Route::middleware(['throttle:api'])->post('/guardar-comentario', [ComentarioController::class, 'store']);



