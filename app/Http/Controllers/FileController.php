<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\File;
use App\Models\Tipofile;
use App\Http\Requests\FileGuardarRequest;
use App\Http\Requests\FileUpdateRequest;
use Carbon\Carbon;

use Illuminate\Support\Facades\Auth;
use App\Models\User;

use Illuminate\Support\Facades\Storage;
// use Illuminate\Http\File as Filecillo;

use Illuminate\Support\Str;

class FileController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:Listar Archivos')->only("index","show");
        $this->middleware('can:Crear Archivos')->only("create","store");
        $this->middleware('can:Editar Archivos')->only("edit","update","seleccionarEvento");
        $this->middleware('can:Eliminar Archivos')->only("destroy");
    }

    public function index()
    {
        return view('file.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipofiles=Tipofile::all();
        return  view('file.create',compact('tipofiles'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FileGuardarRequest $request)
    {
        $file=new File();
        $file->descripcion =$request->descripcion;
        
        if ($request->hasFile('file')){
            $fileArchivo=$request->file('file');
            //$nombreArchivo=Str::random(10).$request->file('file')->getClientOriginalName();
            
            $nombreArchivo=Str::random(20).'.'.$request->file('file')->getClientOriginalExtension();
            Storage::disk('public')->put('files/'.$nombreArchivo, \File::get($fileArchivo));
            $file->file =$nombreArchivo;
            $file->tipofile =$request->file('file')->getClientOriginalExtension();
        }
        $file->save();
        $file->usuarios()->attach(Auth::user()->id);

        return view('file.index');
    }


    public function download($file_id){
        $file= File::where('id',$file_id)->firstOrFail();
        $pathToFile = storage_path('app/public/files/'.$file->file);

        if (! file_exists($pathToFile)) {
            $legacyPath = storage_path('app/public/files\\'.$file->file);
            $pathToFile = file_exists($legacyPath) ? $legacyPath : null;
        }

        if ($pathToFile === null || ! file_exists($pathToFile)) {
            abort(404, 'Archivo no encontrado.');
        }

        return response()->download($pathToFile);
    }
    
    
    

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $file=File::findOrFail($id);
        $user=$file->usuarios->first();
        return view('file.show',compact('file','user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $file=File::findOrFail($id);
        return view('file.edit',compact('file'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(FileUpdateRequest $request, $id)
    {
        $file=File::findOrFail($id);
        if ($request->hasFile('file')) {
            $direccion = storage_path('app/public/files/'.$file->file);
            $direccionLegacy = storage_path('app/public/files\\'.$file->file);
            $nombreArchivo=$file->file;
            if (file_exists($direccion)) {
                Storage::delete('public/files/'. $file->file);
            }
            if (file_exists($direccionLegacy)) {
                @unlink($direccionLegacy);
            }
            $fileArchivo=$request->file('file');
            $nombreArchivo=Str::random(20).'.'.$request->file('file')->extension();
            Storage::disk('public')->put('files/'.$nombreArchivo, \File::get($fileArchivo));
            $file->file=$nombreArchivo;
            $file->tipofile =$request->file('file')->extension();
        }
        $file->descripcion=$request->descripcion;
        $file->save();
        return view('file.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        $direccion = storage_path('app/public/files/'.$file->file);
        $direccionLegacy = storage_path('app/public/files\\'.$file->file);
        $nombreArchivo=$file->file;
        
        if (file_exists($direccion)) {
            Storage::delete('public/files/'. $file->file);
        }
        if (file_exists($direccionLegacy)) {
            @unlink($direccionLegacy);
        }
        $file->delete();
        return response()->json(['mensaje' => 'Registro Eliminado correctamente']);
    }

    public function listar(){
        $files=File::
                select('id','descripcion','tipofile','updated_at');
        return datatables()->of($files)
        ->addColumn('btn', 'file.action')
        ->rawColumns(['btn','descripcion'])
        ->toJson();
    }

    
}
